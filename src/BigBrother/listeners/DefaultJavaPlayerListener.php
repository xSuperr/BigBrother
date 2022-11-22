<?php

declare(strict_types=1);

namespace BigBrother\listeners;

use BigBrother\BigBrother;
use BigBrother\network\JavaNetworkSession;
use BigBrother\network\listeners\ClosureJavaPacketListener;
use BigBrother\player\JavaPlayerManger;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializerContext;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use const http\Client\Curl\PROXY_HTTP;

final class DefaultJavaPlayerListener implements JavaPlayerListener
{

    private BigBrother $plugin;

    public function __construct(BigBrother $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onPlayerAdd(Player $player): void
    {
        $session = $player->getNetworkSession();
        assert($session instanceof JavaNetworkSession);

        $runtimeId = $player->getId();
        $session->registerSpecificPacketListener(PlayStatusPacket::class, new ClosureJavaPacketListener(function (ClientboundPacket $packet, NetworkSession $session) use ($runtimeId): void {
            assert($packet instanceof PlayStatusPacket);
            assert($session instanceof JavaNetworkSession);
            if ($packet->status === PlayStatusPacket::PLAYER_SPAWN) {
                $pk = new PlayerPositionAndLookPacket();//for loading screen
                $pk->x = $session->getPlayer()->getPosition()->getX();
                $pk->y = $session->getPlayer()->getPosition()->getY();
                $pk->z = $session->getPlayer()->getPosition()->getZ();
                $pk->yaw = 0;
                $pk->pitch = 0;
                $pk->flags = 0;
                $session->putRawPacket($pk);
                $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(static function () use ($session, $runtimeId): void {
                    if ($session->isConnected()) {
                        $packet = new SetLocalPlayerAsInitializedPacket();
                        $packet->actorRuntimeId = $runtimeId;

                        $serializer = PacketSerializer::encoder(new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary()));
                        $packet->encode($serializer);
                        $session->handleDataPacket($packet, ProtocolInfo::CURRENT_PROTOCOL, $serializer->getBuffer()); // TODO: ProtocolID
                    }
                }), 40);
            }
        }));

        $session->registerSpecificPacketListener(RespawnPacket::class, new ClosureJavaPacketListener(function (ClientboundPacket $packet, NetworkSession $session): void {
            $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($session): void {
                if ($session->isConnected()) {
                    /** @var Player $player */
                    $player = $session->getPlayer();
                    $player->respawn();
                    JavaPlayerManger::getInstance()->getJavaPlayer($player);
                }
            }), 40);
        }));

        $session->registerSpecificPacketListener(ChangeDimensionPacket::class, new ClosureJavaPacketListener(function (ClientboundPacket $packet, NetworkSession $session): void {
            $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($session): void {
                if ($session->isConnected()) {
                    $player = $session->getPlayer();
                    if ($player !== null) {
                        $packet = PlayerActionPacket::create(
                            $player->getId(),
                            PlayerAction::DIMENSION_CHANGE_ACK,
                            BlockPosition::fromVector3($player->getPosition()->floor()),
                            BlockPosition::fromVector3(Vector3::zero()),
                            0
                        );

                        $serializer = PacketSerializer::encoder(new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary()));
                        $packet->encode($serializer);
                        $session->handleDataPacket($packet, ProtocolInfo::CURRENT_PROTOCOL, $serializer->getBuffer()); // TODO: ProtocolID
                    }
                }
            }), 40);
        }));
    }

    public function onPlayerRemove(Player $player): void
    {
        // not necessary to unregister listeners because they'll automatically
        // be gc-d as nothing holds ref to player object?
    }
}