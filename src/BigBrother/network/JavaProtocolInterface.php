<?php

declare(strict_types=1);

namespace BigBrother\network;

use BigBrother\BigBrother;
use BigBrother\network\binary\JavaBinaryStream;
use BigBrother\network\packet\inbound\EncryptionResponsePacket;
use BigBrother\network\packet\inbound\InboundJavaPacket;
use BigBrother\network\packet\inbound\LoginStartPacket;
use BigBrother\thread\InfoThread;;
use BigBrother\utils\InfoManager;
use Exception;
use pocketmine\network\mcpe\compression\ZlibCompressor;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializerContext;
use pocketmine\network\mcpe\StandardPacketBroadcaster;
use pocketmine\network\NetworkInterface;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Binary;
use pocketmine\utils\MainLogger;
use SplObjectStorage;
use Throwable;
use BigBrother\network\packet\JavaPacket;

class JavaProtocolInterface implements NetworkInterface
{
    protected BigBrother $plugin;
    protected Server $server;
    protected InfoThread $thread;

    protected SplObjectStorage $sessions;

    /** @var JavaNetworkSession[] */
    protected array $players = [];
    private int $threshold;

    public function __construct(BigBrother $plugin, Server $server, int $threshold, int $port, string $ip, string $motd = "Minecraft: PE server", ?string $icon = null)
    {
        $this->plugin = $plugin;
        $this->server = $server;
        $this->threshold = $threshold;
        $this->thread = new InfoThread($server->getLogger(), $server->getLoader(), $port, $ip, $motd, $icon);
        $this->sessions = new SplObjectStorage();
    }

    /**
     * @override
     */
    public function start(): void
    {
        $this->thread->start();
    }

    /**
     * @override
     */
    public function emergencyShutdown()
    {
        $this->thread->pushMainToThreadPacket(chr(InfoManager::PACKET_EMERGENCY_SHUTDOWN));
    }

    /**
     * @override
     */
    public function shutdown(): void
    {
        $this->thread->pushMainToThreadPacket(chr(InfoManager::PACKET_SHUTDOWN));
        $this->thread->join();
    }

    /**
     * @override
     */
    public function setName(string $name): void
    {
        $info = Server::getInstance()->getQueryInformation();
        $value = [
            "MaxPlayers" => $info->getMaxPlayerCount(),
            "OnlinePlayers" => $info->getPlayerCount(),
        ];
        $buffer = chr(InfoManager::PACKET_SET_OPTION) . chr(strlen("name")) . "name" . json_encode($value);
        $this->thread->pushMainToThreadPacket($buffer);
    }

    /**
     * @override
     */
    public function close(Player $player, string $reason = "unknown reason")
    {
        if (isset($this->sessions[$player->getNetworkSession()])) {
            /** @var int $identifier */
            $identifier = $this->sessions[$player->getNetworkSession()];
            $this->sessions->detach($player->getNetworkSession());
            $this->thread->pushMainToThreadPacket(chr(InfoManager::PACKET_CLOSE_SESSION) . Binary::writeInt($identifier));
        }
    }

    public function setCompression(JavaNetworkSession $player)
    {
        if (isset($this->sessions[$player])) {
            /** @var int $target */
            $target = $this->sessions[$player];
            $data = chr(infoManager::PACKET_SET_COMPRESSION) . Binary::writeInt($target) . Binary::writeInt($this->threshold);
            $this->thread->pushMainToThreadPacket($data);
        }
    }

    public function enableEncryption(JavaNetworkSession $player, string $secret)
    {
        if (isset($this->sessions[$player])) {
            /** @var int $target */
            $target = $this->sessions[$player];
            $data = chr(InfoManager::PACKET_ENABLE_ENCRYPTION) . Binary::writeInt($target) . $secret;
            $this->thread->pushMainToThreadPacket($data);
        }
    }

    public function putRawPacket(JavaNetworkSession $player, JavaPacket $packet)
    {
        if (isset($this->sessions[$player])) {
            /** @var int $target */
            $target = $this->sessions[$player];
            $this->sendPacket($target, $packet);
        }
    }

    protected function sendPacket(int $target, JavaPacket $packet)
    {

        try {
            $data = chr(InfoManager::PACKET_SEND_PACKET) . Binary::writeInt($target) . $packet->write();
            $this->thread->pushMainToThreadPacket($data);
        } catch (Throwable $t) {
        }
    }

    public function putBufferPacket(JavaNetworkSession $player, int $pid, string $buffer)
    {
        if (isset($this->sessions[$player])) {
            $target = (int)$this->sessions[$player];
            $this->sendBufferPacket($target, $pid, $buffer);
        }
    }

    protected function sendBufferPacket(int $target, int $pid, string $buffer)//for testing only
    {
        $data = chr(InfoManager::PACKET_SEND_PACKET) . Binary::writeInt($target) . JavaBinaryStream::writeJavaVarInt($pid) . $buffer;
        $this->thread->pushMainToThreadPacket($data);
    }

    /**
     * @override
     */
    public function tick(): void
    {
        while (is_string($buffer = $this->thread->readThreadToMainPacket())) {
            $offset = 1;
            $pid = ord($buffer[0]);

            if ($pid === InfoManager::PACKET_SEND_PACKET) {
                $id = Binary::readInt(substr($buffer, $offset, 4));
                $offset += 4;
                if (isset($this->sessionsPlayers[$id])) {
                    $payload = substr($buffer, $offset);
                    try {
                        $this->handlePacket($this->sessionsPlayers[$id], $payload);
                    } catch (Exception $e) {
                        $logger = $this->server->getLogger();
                        if ($logger instanceof MainLogger) {
                            $logger->debug("DesktopPacket 0x" . bin2hex($payload));
                            $logger->logException($e);
                        }
                    }
                }
            } elseif ($pid === InfoManager::PACKET_OPEN_SESSION) {
                $id = Binary::readInt(substr($buffer, $offset, 4));
                $offset += 4;
                if (isset($this->sessionsPlayers[$id])) {
                    continue;
                }
                $len = ord($buffer[$offset++]);
                $address = substr($buffer, $offset, $len);
                $offset += $len;
                $port = Binary::readShort(substr($buffer, $offset, 2));

                $compressor = ZlibCompressor::getInstance();
                assert($compressor instanceof ZlibCompressor);

                $session = new JavaNetworkSession(Server::getInstance(), Server::getInstance()->getNetwork()->getSessionManager(), PacketPool::getInstance(), new JavaPacketSender(), new StandardPacketBroadcaster(Server::getInstance(), ProtocolInfo::CURRENT_PROTOCOL), $compressor, $address, $port, $this->plugin); // TODO: ProtocolId for packet broadcaster
                Server::getInstance()->getNetwork()->getSessionManager()->add($session);
                $this->sessions->attach($session, $id);
                $this->sessionsPlayers[$id] = $session;

                /*$player = new DesktopPlayer($this, $identifier, $address, $port, $this->plugin);
                $this->sessions->attach($player, $id);
                $this->sessionsPlayers[$id] = $player;
                $this->plugin->getServer()->addPlayer($player);*/
                //TODO
            } elseif ($pid === InfoManager::PACKET_CLOSE_SESSION) {
                $id = Binary::readInt(substr($buffer, $offset, 4));
                if (!isset($this->sessionsPlayers[$id])) {
                    continue;
                }
                $player = $this->sessionsPlayers[$id];
                $player->disconnect("");
                Server::getInstance()->getNetwork()->getSessionManager()->remove($player);
                $this->closeSession($id);
            }
        }
    }

    /**
     * @param JavaNetworkSession $player
     * @param string $payload
     */
    protected function handlePacket(JavaNetworkSession $player, string $payload)
    {
        $pid = ord($payload[0]);
        $offset = 1;

        $status = $player->status;

        if ($status === 1) {
            $pk = JavaPacketPool::getInstance()->getPacketInbound($pid);
            if($pk == null){
                echo "[Receive][Interface] 0x" . bin2hex(chr($pid)) . " Not implemented\n";
                return;
            }
            $pk->read($payload, $offset);
            $this->receivePacket($player, $pk);
        } elseif ($status === 0) {
            if ($pid === 0x00) {
                $pk = new LoginStartPacket();
                $pk->read($payload, $offset);
                $player->handleAuthentication($pk, true);
            } elseif ($pid === 0x01) {
                $pk = new EncryptionResponsePacket();
                $pk->read($payload, $offset);
                $player->processAuthentication($pk);
            } else {
                $player->disconnect("Unexpected packet $pid");
            }
        }
    }

    protected function receivePacket(JavaNetworkSession $player, InboundJavaPacket $packet)
    {
        $packets = $packet->fromJava($player);

        // TODO: ProtocolIds
        if ($packets !== null) {
            if (is_array($packets)) {
                foreach ($packets as $packet) {
                    $player->handleDataPacket($packet, ProtocolInfo::CURRENT_PROTOCOL, PacketSerializer::encoder(new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary()))->getBuffer());
                }
            } else {
                $player->handleDataPacket($packets, ProtocolInfo::CURRENT_PROTOCOL, PacketSerializer::encoder(new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary()))->getBuffer());
            }
        }
    }

    /**
     * @param int $identifier
     */
    public function closeSession(int $identifier)
    {
        if (isset($this->sessionsPlayers[$identifier])) {
            $player = $this->sessionsPlayers[$identifier];
            unset($this->sessionsPlayers[$identifier]);
            $player->disconnect("Connection closed", true);
        }
    }
}