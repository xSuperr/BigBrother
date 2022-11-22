<?php

declare(strict_types=1);

namespace BigBrother\network;

use BigBrother\BigBrother;
use BigBrother\network\binary\JavaBinaryStream;
use BigBrother\network\listeners\JavaPacketListener;
use BigBrother\network\listeners\JavaSpecificPacketListener;
use BigBrother\network\packet\inbound\EncryptionResponsePacket;
use BigBrother\network\packet\inbound\LoginStartPacket;
use BigBrother\network\packet\JavaPacket;
use BigBrother\network\packet\outbound\EncryptionRequestPacket;
use BigBrother\network\packet\outbound\LoginSuccessPacket;
use BigBrother\network\packet\outbound\OutboundJavaPacket;
use BigBrother\player\JavaPlayer;
use BigBrother\player\JavaPlayerManger;
use BigBrother\player\ProfileCacheManager;
use BigBrother\translate\Translator;
use BigBrother\utils\JavaSkinImage;
use Closure;
use pocketmine\color\Color;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\PacketBroadcaster;
use pocketmine\network\mcpe\PacketSender;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\NetworkSessionManager;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\Internet;
use pocketmine\world\World;
use Ramsey\Uuid\Nonstandard\Uuid;
use function Sodium\randombytes_buf;

class JavaNetworkSession extends NetworkSession
{
    public int $status = 0;
    public BigBrother $loader;
    public string $username = "";
    public string $uuid = "";
    public string $formattedUUID = "";
    private string $secret;
    private ?string $checkToken = null;
    private ?string $publicKey = null;
    private ?string $signature = null;
    private ?int $timestamp = null;
    private string $serverID;

    public $bigBrother_breakPosition;
    /** @var array */
    protected $bigBrother_properties = [];
    /** @var string[] */
    private $entityList = [];

    /** @var JavaPacketListener[] */
    private array $packetListeners = [];
    private ?JavaSpecificPacketListener $specificPacketListener;

    public function __construct(Server $server, NetworkSessionManager $manager, PacketPool $packetPool, PacketSender $sender, PacketBroadcaster $broadcaster, Compressor $compressor, string $ip, int $port, BigBrother $loader)
    {
        parent::__construct($server, $manager, $packetPool, $sender, $broadcaster, $compressor, $ip, $port);
        $this->loader = $loader;
        $this->bigBrother_breakPosition = [new Vector3(0, 0, 0), 0];
    }

    public function registerSpecificPacketListener(string $packet, JavaPacketListener $listener): void
    {
        if ($this->specificPacketListener === null) {
            $this->specificPacketListener = new JavaSpecificPacketListener();
            $this->registerPacketListener($this->specificPacketListener);
        }
        $this->specificPacketListener->register($packet, $listener);
    }

    public function registerPacketListener(JavaPacketListener $listener): void
    {
        $this->packetListeners[spl_object_id($listener)] = $listener;
    }

    public function unregisterSpecificPacketListener(string $packet, JavaPacketListener $listener): void
    {
        if ($this->specificPacketListener !== null) {
            $this->specificPacketListener->unregister($packet, $listener);
            if ($this->specificPacketListener->isEmpty()) {
                $this->unregisterPacketListener($this->specificPacketListener);
                $this->specificPacketListener = null;
            }
        }
    }

    public function unregisterPacketListener(JavaPacketListener $listener): void
    {
        unset($this->packetListeners[spl_object_id($listener)]);
    }

    public function stopUsingChunk(int $chunkX, int $chunkZ): void
    {
        $pk = new UnloadChunkPacket();
        $pk->chunkX = $chunkX;
        $pk->chunkZ = $chunkZ;
        $this->putRawPacket($pk);
    }

    public function putRawPacket(JavaPacket $packet)
    {
        $this->loader->getInterface()->putRawPacket($this, $packet);
    }

    public function putBufferPacket(int $pid, string $buffer)//for test ing
    {
        $this->loader->getInterface()->putBufferPacket($this, $pid, $buffer);
    }

    public function respawn() : void
    {
        $pk = new PlayerPositionAndLookPacket();
        $pk->x = $this->getPlayer()->getPosition()->getX();
        $pk->y = $this->getPlayer()->getPosition()->getY();
        $pk->z = $this->getPlayer()->getPosition()->getZ();
        $pk->yaw = 0;
        $pk->pitch = 0;
        $pk->flags = 0;
        $this->putRawPacket($pk);

        $ch = new \ReflectionProperty($this->getPlayer(), "usedChunks");
        $ch->setAccessible(true);
        $usedChunks = $ch->getValue($this->getPlayer());

        foreach($usedChunks as $index => $d){//reset chunks
            World::getXZ($index, $chunkX, $chunkZ);
            $ref = new \ReflectionMethod($this->getPlayer(), "unloadChunk");
            $ref->setAccessible(true);
            $ref->invoke($this->getPlayer(), $chunkX, $chunkZ);
        }

        $ch->setValue($this->getPlayer(), []);
    }

    public function addToSendBuffer(ClientboundPacket $packet): void
    {
        parent::addToSendBuffer($packet);
        foreach ($this->packetListeners as $listener) {
            $listener->onPacketSend($packet, $this);
        }
        $packets = Translator::getInstance()->translate($this, $packet);
        if ($packets !== null) {
            /** @var int $target */
            if (is_array($packets)) {
                foreach ($packets as $packet) {
                    $this->putRawPacket($packet);
                }
            } else {
                $this->putRawPacket($packets);
            }
        }
    }

    public function syncViewAreaRadius(int $distance) : void{
        $pk = new UpdateViewDistancePacket();
        $pk->viewDistance = $distance * 2;
        $this->putRawPacket($pk);
    }

    public function syncViewAreaCenterPoint(Vector3 $newPos, int $viewDistance) : void
    {
        $pk = new UpdateViewPositionPacket();
        $pk->chunkX = $newPos->getX() >> 4;
        $pk->chunkZ = $newPos->getZ() >> 4;
        $this->putRawPacket($pk);

        $pk = new UpdateViewDistancePacket();
        $pk->viewDistance = $viewDistance * 2;
        $this->putRawPacket($pk);
    }

    public function syncAvailableCommands() : void{
        $buffer = "";
        $commands = Server::getInstance()->getCommandMap()->getCommands();
        $commandData = [];
        foreach($commands as $command){
            if(isset($commandData[$command->getName()]) || !$command->testPermissionSilent($this->getPlayer())){
                continue;
            }
            $commandData[] = $command;
        }
        $commandCount = count($commandData);
        $buffer .= JavaBinaryStream::writeJavaVarInt($commandCount * 2 + 1);
        $buffer .= JavaBinaryStream::writeByte(0);
        $buffer .= JavaBinaryStream::writeJavaVarInt($commandCount);
        for ($i = 1; $i <= $commandCount * 2; $i++) {
            $buffer .= JavaBinaryStream::writeJavaVarInt($i++);
        }
        $i = 1;
        foreach($commandData as $command){
            $buffer .= JavaBinaryStream::writeByte(1 | 0x04);
            $buffer .= JavaBinaryStream::writeJavaVarInt(1);
            $buffer .= JavaBinaryStream::writeJavaVarInt($i + 1);
            $buffer .= JavaBinaryStream::writeJavaVarInt(strlen($command->getName())) . $command->getName();
            $i++;

            $buffer .= JavaBinaryStream::writeByte(2 | 0x04 | 0x10);
            $buffer .= JavaBinaryStream::writeJavaVarInt(1);
            $buffer .= JavaBinaryStream::writeJavaVarInt($i);
            $buffer .= JavaBinaryStream::writeJavaVarInt(strlen("arg")). "arg";
            $buffer .= JavaBinaryStream::writeJavaVarInt(strlen("brigadier:string")) . "brigadier:string";
            $buffer .= JavaBinaryStream::writeJavaVarInt(0);
            $buffer .= JavaBinaryStream::writeJavaVarInt(strlen("minecraft:ask_server")) . "minecraft:ask_server";
            $i++;
        }
        $buffer .= JavaBinaryStream::writeJavaVarInt(0);
        $this->putBufferPacket(OutboundJavaPacket::DECLARE_COMMANDS_PACKET, $buffer);
    }

    /*public function startUsingChunk(int $chunkX, int $chunkZ, Closure $onCompletion): void
    {
        $task = new chunktask($chunkX, $chunkZ, $this->getPlayer()->getWorld()->getChunk($chunkX, $chunkZ), $this);
        Server::getInstance()->getAsyncPool()->submitTask($task);
        var_dump("Chunktask -> execute");
    }*/

    public function bigBrother_getProperties(): array
    {
        return $this->bigBrother_properties;
    }

    public function processAuthentication(EncryptionResponsePacket $packet): void
    {
        $this->secret = $this->loader->decryptBinary($packet->sharedSecret);

        if ($packet->hasVerifyToken) {
            $token = $this->loader->decryptBinary($packet->verifyToken);
            if ($token !== $this->checkToken) {
                $this->disconnect("Invalid check token");
                return;
            }
        } else {
            $publicKey = $this->loader->mcPubKeyToPem($this->publicKey);


            $binaryStream = new BinaryStream();
            $binaryStream->put($this->checkToken);
            $binaryStream->putLong($packet->salt);
            $signable = $binaryStream->getBuffer();
            
            if (!openssl_verify($signable, $packet->signature, $publicKey, OPENSSL_ALGO_SHA256)) {
                $this->disconnect("Invalid signature");
                return;
            }
        }

        $this->loader->getInterface()->enableEncryption($this, bin2hex($this->secret));
        $username = $this->username;
        $hash = JavaBinaryStream::sha1($this->serverID . $this->secret . $this->loader->pemToMcPubKey());

        Server::getInstance()->getAsyncPool()->submitTask(new class($this, $username, $hash) extends AsyncTask {
            private string $username;
            private string $hash;

            public function __construct(JavaNetworkSession $networkSession, string $username, string $hash)
            {
                self::storeLocal("", $networkSession);
                $this->username = $username;
                $this->hash = $hash;
            }

            private function encodeURIComponent($str): string
            {
                $revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');
                return strtr(rawurlencode($str), $revert);
            }

            /**
             * @override
             */
            public function onRun(): void
            {
                $query = http_build_query([
                    "username" => $this->encodeURIComponent($this->username),
                    "serverId" => $this->hash
                ]);

                $response = Internet::getURL("https://sessionserver.mojang.com/session/minecraft/hasJoined?" . $query, 5, [], $err);
                if ($response === false || $response->getCode() !== 200) {
                    $this->publishProgress("InternetException: failed to fetch session data for '$this->username'; status={$response->getCode()}; err=$err; response_header=" . json_encode($response->getHeaders()));
                    $this->setResult(false);
                    return;
                }

                $this->setResult(json_decode($response->getBody(), true));
            }

            /**
             * @override
             * @param mixed $progress
             */
            public function onProgressUpdate($progress): void
            {
                Server::getInstance()->getLogger()->error($progress);
            }

            /**
             * @override
             */
            public function onCompletion(): void
            {
                $result = $this->getResult();
                /** @var JavaNetworkSession $player */
                $player = self::fetchLocal("");
                if (is_array($result) and isset($result["id"])) {
                    $player->authenticate($result["id"], $result["properties"]);
                } else {
                    $player->getPlayer()->kick("User not premium", "User not premium");
                }
            }
        });
    }

    public function authenticate(string $uuid, ?array $onlineModeData = null): void
    {
        if ($this->status === 0) {
            $this->uuid = $uuid;
            $this->formattedUUID = Uuid::fromString($this->uuid)->getBytes();

            $this->loader->getInterface()->setCompression($this);

            $pk = new LoginSuccessPacket();

            $pk->uuid = $this->formattedUUID;
            $pk->name = $this->username;

            $this->putRawPacket($pk);

            $this->status = 1;

            if ($onlineModeData !== null) {
                $this->bigBrother_properties = $onlineModeData;
            }

            $model = false;
            $skinImage = "";
            $capeImage = "";
            foreach ($this->bigBrother_properties as $property) {
                if ($property["name"] === "textures") {
                    $textures = json_decode(base64_decode($property["value"]), true);

                    if (isset($textures["textures"]["SKIN"])) {
                        if (isset($textures["textures"]["SKIN"]["metadata"]["model"])) {
                            $model = true;
                        }

                        $skinImage = file_get_contents($textures["textures"]["SKIN"]["url"]);
                    } else {
                        /*
                         * Detect whether the player has the “Alex?” or “Steve?”
                         * Ref) https://github.com/mapcrafter/mapcrafter-playermarkers/blob/c583dd9157a041a3c9ec5c68244f73b8d01ac37a/playermarkers/player.php#L8-L19
                         */
                        if (array_reduce(str_split($uuid, 8), function ($acm, $val) {
                                return $acm ^ hexdec($val);
                            }, 0) % 2) {
                            $skinImage = file_get_contents("https://assets.mojang.com/SkinTemplates/alex.png");
                            $model = true;
                        } else {
                            $skinImage = file_get_contents("https://assets.mojang.com/SkinTemplates/steve.png");
                        }
                    }

                    if (isset($textures["textures"]["CAPE"])) {
                        $capeImage = file_get_contents($textures["textures"]["CAPE"]["url"]);
                    }
                }
            }
            if ($model) {
                $SkinId = $this->formattedUUID . "_CustomSlim";
                $SkinResourcePatch = base64_encode(json_encode(["geometry" => ["default" => "geometry.humanoid.customSlim"]]));
            } else {
                $SkinId = $this->formattedUUID . "_Custom";
                $SkinResourcePatch = base64_encode(json_encode(["geometry" => ["default" => "geometry.humanoid.custom"]]));
            }

            $skin = new JavaSkinImage($skinImage);
            $SkinData = $skin->getSkinImageData(true);
            $skinSize = $this->getSkinImageSize(strlen($skin->getRawSkinImageData(true)));
            $SkinImageHeight = $skinSize[0];
            $SkinImageWidth = $skinSize[1];

            $cape = new JavaSkinImage($capeImage);
            $CapeData = $cape->getSkinImageData();
            $capeSize = $this->getSkinImageSize(strlen($cape->getRawSkinImageData()));
            $CapeImageHeight = $capeSize[0];
            $CapeImageWidth = $capeSize[1];
            $skin = new Skin($SkinId, base64_decode($SkinData), base64_decode($CapeData));
            JavaPlayerManger::getInstance()->addJavaPlayer($this->uuid, (string)mt_rand(2 * (10 ** 15), (3 * (10 ** 15)) - 1), $this->username, $skin, $this);
        }
    }

    private function getSkinImageSize(int $skinImageLength): array
    {
        return match ($skinImageLength) {
            64 * 32 * 4 => [64, 32],
            64 * 64 * 4 => [64, 64],
            128 * 64 * 4 => [128, 64],
            128 * 128 * 4 => [128, 128],
            default => [0, 0]
        };

    }

    /**
     * @param int $eid
     * @param string $entityType
     */
    public function addEntityList(int $eid, string $entityType): void
    {
        if (!isset($this->entityList[$eid])) {
            $this->entityList[$eid] = $entityType;
        }
    }

    /**
     * @param int $eid
     * @return string
     */
    public function bigBrother_getEntityList(int $eid): string
    {
        if (isset($this->entityList[$eid])) {
            return $this->entityList[$eid];
        }
        return "generic";
    }

    /**
     * @param int $eid
     */
    public function removeEntityList(int $eid): void
    {
        if (isset($this->entityList[$eid])) {
            unset($this->entityList[$eid]);
        }
    }

    public function handleAuthentication(LoginStartPacket $packet, bool $onlineMode = false): void
    {
        if ($this->status === 0) {
            $this->username = $packet->name;
            if ($packet->hasSigData) {
                if ($packet->timestamp < time()) {
                    $this->disconnect('Invalid public key signature');
                    return;
                }

                $this->timestamp = $packet->timestamp;
                $this->publicKey = $packet->publicKey;
                // TODO: Verify Signature

                $this->signature = $packet->signature;
            }

            if($onlineMode){
                $pk = new EncryptionRequestPacket();
                $this->serverID = bin2hex(random_bytes(4));
                $pk->serverID = $this->serverID;
                $pk->publicKey = $this->loader->pemToMcPubKey();
                $this->checkToken = random_bytes(4);
                $pk->verifyToken = $this->checkToken;
                $this->putRawPacket($pk);
            }else {
                $username = $this->username;
                if (!is_null(($info = ($manager = ProfileCacheManager::getInstance())->getProfileCache($username)))) {
                    $this->authenticate($info["id"], $info["properties"]);
                } else {
                    Server::getInstance()->getAsyncPool()->submitTask(new class($manager, $this, $username) extends AsyncTask {
                        private string $username;

                        public function __construct(ProfileCacheManager $manager, JavaNetworkSession $player, string $username)
                        {
                            self::storeLocal("", [$manager, $player]);
                            $this->username = $username;
                        }

                        /**
                         * @override
                         */
                        public function onRun(): void
                        {

                            $response = Internet::getURL("https://api.mojang.com/users/profiles/minecraft/" . $this->username, 10, [], $err);
                            var_dump($response);
                            if ($response === null) {
                                return;
                            }
                            if ($response->getCode() === 204) {
                                $this->publishProgress("UserNotFound: failed to fetch profile for '$this->username'; status={$response->getCode()}; err=$err; response_header=".json_encode($response->getHeaders()));
                                $this->setResult(false);
                                return;
                            }

                            if ($response->getCode() !== 200) {
                                $this->publishProgress("InternetException: failed to fetch profile for '$this->username'; status={$response->getCode()}; err=$err; response_header=" . json_encode($response->getHeaders()));
                                $this->setResult(false);
                                return;
                            }

                            $profile = json_decode($response->getBody(), true);
                            if (!is_array($profile)) {
                                $this->publishProgress("UnknownError: failed to parse profile for '$this->username'; status={$response->getCode()}; response={$response->getBody()}; response_header=" . json_encode($response->getHeaders()));
                                $this->setResult(false);
                                return;
                            }

                            $uuid = $profile["id"];
                            $response = Internet::getURL("https://sessionserver.mojang.com/session/minecraft/profile/" . $uuid, 3, [], $err);
                            if ($response === false || $response->getCode() !== 200) {
                                $this->publishProgress("InternetException: failed to fetch profile info for '$this->username'; status={$response->getCode()}; err=$err; response_header=" . json_encode($response->getHeaders()));
                                $this->setResult(false);
                                return;
                            }

                            $info = json_decode($response->getBody(), true);
                            if ($info === null or !isset($info["id"])) {
                                $this->publishProgress("UnknownError: failed to parse profile info for '$this->username'; status={$response->getCode()}; response={$response->getBody()}; response_header=" . json_encode($response->getHeaders()));
                                $this->setResult(false);
                                return;
                            }

                            $this->setResult($info);
                        }

                        /**
                         * @override
                         */
                        public function onProgressUpdate($progress): void
                        {
                            Server::getInstance()->getLogger()->error($progress);
                        }

                        /**
                         * @override
                         */
                        public function onCompletion(): void
                        {
                            $info = $this->getResult();
                            if (is_array($info)) {
                                list($manager, $player) = self::fetchLocal("");

                                $manager->setProfileCache($this->username, $info);
                                /** @var JavaNetworkSession $player */
                                $player->authenticate($info["id"], $info["properties"]);
                            }
                        }
                    });
                }
            }
        }
    }
}