<?php

declare(strict_types=1);

namespace BigBrother\translate;

use BigBrother\BigBrother;
use BigBrother\network\JavaNetworkSession;
use BigBrother\network\packet\outbound\ChangeDifficultyPacket;
use BigBrother\network\packet\outbound\LoginPacket;
use BigBrother\network\packet\outbound\OutboundJavaPacket;
use BigBrother\network\packet\outbound\PluginMessagePacket;
use BigBrother\network\packet\outbound\SetDefaultSpawnPositionPacket;
use BigBrother\network\packet\outbound\SynchronizePlayerPosition;
use BigBrother\utils\Utils;
use Closure;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;

class Translator{
    use SingletonTrait;

    private array $translators = [];
    private array $ignored = [
        ProtocolInfo::AVAILABLE_ACTOR_IDENTIFIERS_PACKET,
        ProtocolInfo::RESOURCE_PACKS_INFO_PACKET, // TODO: Resource packs?
        ProtocolInfo::BIOME_DEFINITION_LIST_PACKET, // This is done in LoginPacket registry
    ];

    protected static function make(): self
    {
        $self = new self;

        $self->addTranslator(ProtocolInfo::PLAY_STATUS_PACKET, function (JavaNetworkSession $session, ClientboundPacket $packet): null|array|OutboundJavaPacket {
            /** Used for the Downloading Terrain screen when joining/respawning */
            if ($packet instanceof PlayStatusPacket && $packet->status === PlayStatusPacket::PLAYER_SPAWN) {
                $pk = new SynchronizePlayerPosition();
                $pk->x = $session->getPlayer()->getPosition()->getX();
                $pk->y = $session->getPlayer()->getPosition()->getY();
                $pk->z = $session->getPlayer()->getPosition()->getZ();
                $pk->yaw = 0;
                $pk->pitch = 0;
                $pk->flags = 0;

                return $pk;
            }

            return null;
        });

        $self->addTranslator(ProtocolInfo::START_GAME_PACKET, function (JavaNetworkSession $session, ClientboundPacket $packet):  null|array|OutboundJavaPacket {
            if ($packet instanceof StartGamePacket) {
                $pks = [];

                $pk = new LoginPacket();

                $pk->entityId = $packet->actorRuntimeId;
                $pk->hardcore = Server::getInstance()->isHardcore();
                $pk->gamemode = $packet->playerGamemode;
                $pk->previousGamemode = $packet->playerGamemode;
                $pk->worldCount = 1; // TODO: This?
                $pk->worldNames = ["minecraft:world"];
                $pk->registry = Utils::loadCompoundFromFile(BigBrother::getInstance()->getDataFolder() . 'network_codec.nbt');
                $pk->dimension = ""; // TODO: Convert Dimension?

                $pk->worldName = "minecraft:world";
                $pk->hashedSeed = 0;
                $pk->maxPlayers = Server::getInstance()->getMaxPlayers();
                $pk->viewDistance = 4;//TODO: Config
                $pk->simulationDistance = 16;//TODO: Config
                $pk->reducedDebugInfo = false;
                $pk->enableRespawnScreen = true;
                $pk->debug = true;
                $pk->flat = false;
                $pks[] = $pk;

                $pk = new PluginMessagePacket();
                $pk->channel = "minecraft:brand";
                $pk->data[] = $packet->serverSoftwareVersion;
                $pks[] = $pk;

                $levelSettings = $packet->levelSettings;

                $pk = new ChangeDifficultyPacket();
                $pk->difficulty = $levelSettings->difficulty;
                $pks[] = $pk;

                $spawnPosition = $levelSettings->spawnPosition;

                $pk = new SetDefaultSpawnPositionPacket();
                $pk->location = new Vector3($spawnPosition->getX(), $spawnPosition->getY(), $spawnPosition->getZ());
                $pk->angle = 0; // TODO: Spawn Angle?
                $pks[] = $pk;




                return $pks;
            }

            return null;
        });

        return $self;
    }

    private function addTranslator(int $pid, Closure $function): void
    {
        $this->translators[$pid] = $function;
    }

    /** @return null|OutboundJavaPacket[]|OutboundJavaPacket */
    public function translate(JavaNetworkSession $session, ClientboundPacket $packet): null|array|OutboundJavaPacket
    {
        if (in_array($packet->pid(), $this->ignored)) return null;

        $translator = $this->translators[$packet->pid()] ?? null;

        if ($translator === null) {
            throw new \Exception("Translator not found for packet: " . $packet->getName() . ":" . $packet->pid());
        }

        return $translator($session, $packet);
    }
}