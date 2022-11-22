<?php

namespace BigBrother\player;

use BigBrother\BigBrother;
use BigBrother\listeners\JavaPlayerListenerManager;
use BigBrother\network\JavaNetworkSession;
use InvalidArgumentException;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializerContext;
use pocketmine\player\Player;
use pocketmine\player\XboxLivePlayerInfo;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use Ramsey\Uuid\Uuid;
use ReflectionMethod;
use ReflectionProperty;

class JavaPlayerManger
{
    use SingletonTrait;

    /** @var JavaPlayer[] */
    private array $javaPlayers = [];

    public function getJavaPlayerList(): array
    {
        return $this->javaPlayers;
    }

    public function getJavaPlayer(Player $player): ?JavaPlayer
    {
        return $this->javaPlayers[$player->getUniqueId()->getBytes()] ?? null;
    }

    public function addJavaPlayer($uuid, $xuid, $gamertag, Skin $skin, JavaNetworkSession $j): void
    {
        $this->addPlayer(new JavaPlayerInfo(Uuid::fromString($uuid), $xuid, $gamertag, $skin, $data["extra_data"] ?? []), $j);
    }

    public function addPlayer(JavaPlayerInfo $info, JavaNetworkSession $jn): Player
    {
        $server = Server::getInstance();
        $session = $jn;

        $rp = new ReflectionProperty(NetworkSession::class, "info");
        $rp->setAccessible(true);
        $rp->setValue($session, new XboxLivePlayerInfo($info->xuid, $info->username, $info->uuid, $info->skin, "en_US", $info->extra_data));

        $rp = new ReflectionMethod(NetworkSession::class, "onServerLoginSuccess");
        $rp->setAccessible(true);
        $rp->invoke($session);

        $packet = new ResourcePackClientResponsePacket();
        $packet->status = ResourcePackClientResponsePacket::STATUS_COMPLETED;
        $serializer = PacketSerializer::encoder(new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary()));
        $packet->encode($serializer);
        $session->handleDataPacket($packet, ProtocolInfo::CURRENT_PROTOCOL, $serializer->getBuffer()); // TODO: ProtocolID

        $pk = new RequestChunkRadiusPacket();
        $pk->radius = 4;
        $serializer = PacketSerializer::encoder(new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary()));
        $pk->encode($serializer);
        $session->handleDataPacket($pk, ProtocolInfo::CURRENT_PROTOCOL,$serializer->getBuffer()); // TODO: ProtocolID

        $pk = new KeepAlivePacket();
        $pk->keepAliveId = mt_rand();
        $jn->putRawPacket($pk);

        $player = $session->getPlayer();
        assert($player !== null);
        $this->javaPlayers[$player->getUniqueId()->getBytes()] = new JavaPlayer($session, BigBrother::getInstance());

        foreach (JavaPlayerListenerManager::getInstance()->getListeners() as $listener) {
            $listener->onPlayerAdd($player);
        }

        if (!$player->isAlive()) {
            $player->respawn();
        }

        return $player;
    }

    public function removePlayer(Player $player, bool $disconnect = true): void
    {
        if (!$this->isJavaPlayer($player)) {
            throw new InvalidArgumentException("Invalid Player supplied, expected a java player, got " . $player->getName());
        }

        if (!isset($this->javaPlayers[$id = $player->getUniqueId()->getBytes()])) {
            return;
        }

        $this->javaPlayers[$id]->destroy();
        unset($this->javaPlayers[$id]);

        if ($disconnect) {
            $player->disconnect("disconnected");
        }

        foreach (JavaPlayerListenerManager::getInstance()->getListeners() as $listener) {
            $listener->onPlayerRemove($player);
        }
    }

    public function isJavaPlayer(Player $player): bool
    {
        return isset($this->javaPlayers[$player->getUniqueId()->getBytes()]);
    }
}