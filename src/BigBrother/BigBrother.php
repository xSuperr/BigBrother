<?php

namespace BigBrother;

use BigBrother\listeners\DefaultJavaPlayerListener;
use BigBrother\listeners\EventListener;
use BigBrother\listeners\JavaPlayerListenerManager;
use BigBrother\network\JavaProtocolInterface;
use phpseclib\Crypt\Base;
use phpseclib\Crypt\RSA;
use phpseclib\Crypt\AES;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class BigBrother extends PluginBase
{
    const MOJANG_PUBLIC_KEY = "-----BEGIN PUBLIC KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAylB4B6m5lz7jwrcFz6Fd\n/fnfUhcvlxsTSn5kIK/2aGG1C3kMy4VjhwlxF6BFUSnfxhNswPjh3ZitkBxEAFY2\n5uzkJFRwHwVA9mdwjashXILtR6OqdLXXFVyUPIURLOSWqGNBtb08EN5fMnG8iFLg\nEJIBMxs9BvF3s3/FhuHyPKiVTZmXY0WY4ZyYqvoKR+XjaTRPPvBsDa4WI2u1zxXM\neHlodT3lnCzVvyOYBLXL6CJgByuOxccJ8hnXfF9yY4F0aeL080Jz/3+EBNG8RO4B\nyhtBf4Ny8NQ6stWsjfeUIvH7bU/4zCYcYOq4WrInXHqS8qruDmIl7P5XXGcabuzQ\nstPf/h2CRAUpP/PlHXcMlvewjmGU6MfDK+lifScNYwjPxRo4nKTGFZf/0aqHCh/E\nAsQyLKrOIYRE0lDG3bzBh8ogIMLAugsAfBb6M3mqCqKaTMAf/VAjh5FFJnjS+7bE\n+bZEV0qwax1CEoPPJL1fIQjOS8zj086gjpGRCtSy9+bTPTfTR/SJ+VUB5G2IeCIt\nkNHpJX2ygojFZ9n5Fnj7R9ZnOM+L8nyIjPu3aePvtcrXlyLhH/hvOfIOjPxOlqW+\nO5QwSFP4OEcyLAUgDdUgyW36Z5mB285uKW/ighzZsOTevVUG2QwDItObIV6i8RCx\nFbN2oDHyPaO5j1tTaBNyVt8CAwEAAQ==\n-----END PUBLIC KEY-----";

    public RSA $rsa;
    protected string $privateKey;
    protected string $publicKey;

    private JavaProtocolInterface $interface;

    private static self $instance;

    public function onEnable(): void
    {
        self::$instance = $this;

        $this->saveResource('openssl.cnf');
        $this->saveResource('network_codec.nbt', true);

        $aes = new AES(Base::MODE_CFB8);
        switch ($aes->getEngine()) {
            case Base::ENGINE_OPENSSL:
                $this->getLogger()->info("Use openssl as AES encryption engine.");
                break;
            case Base::ENGINE_MCRYPT:
                $this->getLogger()->warning("Use obsolete mcrypt for AES encryption. Try to install openssl extension instead!!");
                break;
            case Base::ENGINE_INTERNAL:
                $this->getLogger()->warning("Use phpseclib internal engine for AES encryption, this may impact on performance. To improve them, try to install openssl extension.");
                break;
        }

        $this->rsa = new RSA();
        switch (constant("CRYPT_RSA_MODE")) {
            case RSA::MODE_OPENSSL:
                $this->rsa->configFile = $this->getDataFolder() . "openssl.cnf";
                $this->getLogger()->info("Use openssl as RSA encryption engine.");
                break;
            case RSA::MODE_INTERNAL:
                $this->getLogger()->info("Use phpseclib internal engine for RSA encryption.");
                break;
        }

        if ($aes->getEngine() === Base::ENGINE_OPENSSL or constant("CRYPT_RSA_MODE") === RSA::MODE_OPENSSL) {
            ob_start();
            @phpinfo();
            preg_match_all('#OpenSSL (Header|Library) Version => (.*)#im', ob_get_contents() ?? "", $matches);
            ob_end_clean();

            foreach (array_map(null, $matches[1], $matches[2]) as $version) {
                $this->getLogger()->info("OpenSSL " . $version[0] . " version: " . $version[1]);
            }
        }

        $this->getLogger()->info("Server is being started in the background");
        $this->getLogger()->info("Generating keypair");
        $this->rsa->setPrivateKeyFormat(RSA::PRIVATE_FORMAT_PKCS1);
        $this->rsa->setPublicKeyFormat(RSA::PUBLIC_FORMAT_PKCS8);
        $this->rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
        $keys = $this->rsa->createKey();//1024 bits
        $this->privateKey = $keys["privatekey"];
        $this->publicKey = $keys["publickey"];
        $this->rsa->loadKey($this->privateKey);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        JavaPlayerListenerManager::getInstance()->registerListener(new DefaultJavaPlayerListener($this));

        $this->getServer()->getLogger()->info("Starting Minecraft: Java Edition server version 1.19");
        $this->interface = new JavaProtocolInterface($this, $this->getServer(), 256, 25565, Server::getInstance()->getIp(), 'BigBrother');
        $this->getServer()->getNetwork()->registerInterface($this->interface);
    }

    public function getInterface(): JavaProtocolInterface
    {
        return $this->interface;
    }

    public function mcPubKeyToPem(string $mcPubKeyBuffer): string
    {
        $pem = "-----BEGIN PUBLIC KEY-----\n";
        $base64PubKey = base64_encode($mcPubKeyBuffer);
        for ($i = 1; $i <= 7; $i++) {
            $offset = 64 * ($i - 1);
            $sub = substr($base64PubKey, $offset, 64);
            $pem .= $sub . "\n";
        }

        $pem .= "-----END PUBLIC KEY-----\n";
        return $pem;
    }

    public function pemToMcPubKey(): string
    {
        $key = explode("\n", $this->publicKey);
        array_pop($key);
        array_shift($key);
        $pubKey = implode(array_map("trim", $key));
        return base64_decode($pubKey);
    }

    public function decryptBinary(string $cipher): string
    {
        return $this->rsa->decrypt($cipher);
    }

    public function verifySignature(string $message, string $cipher): bool
    {
        return $this->rsa->verify($message, $cipher);
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }

    public function getFile(): string
    {
        return parent::getFile();
    }
}