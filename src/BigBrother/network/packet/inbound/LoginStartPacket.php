<?php

declare(strict_types=1);

namespace BigBrother\network\packet\inbound;


use BigBrother\network\JavaNetworkSession;
use BigBrother\network\packet\JavaPacket;
use ErrorException;
use pocketmine\network\mcpe\protocol\DataPacket;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class LoginStartPacket extends JavaPacket implements InboundJavaPacket
{
    public string $name;
    public bool $hasSigData;
    public ?int $timestamp = null;
    public ?string $publicKey = null;
    public ?string $signature = null;
    public bool $hasPlayerUUID;
    public ?UuidInterface $uuid = null;

    public function pid(): int
    {
        return self::LOGIN_START;
    }

    protected function decode(): void
    {
        $this->name = $this->getString();
        $this->hasSigData = $this->getBool();
        if ($this->hasSigData) {
            $this->timestamp = $this->getLong();
            $this->publicKey = $this->get($this->getVarInt());
            $this->signature = $this->get($this->getVarInt());
        }

        $this->hasPlayerUUID = $this->getBool();
        if ($this->hasPlayerUUID) {
            //$this->uuid = Uuid::fromInteger(str_replace($this->getLong() . $this->getLong(), '-', ''));
        }
    }

    /**
     * @throws ErrorException
     * @deprecated
     */
    protected final function encode(): void
    {
        throw new ErrorException(get_class($this) . " is subclass of InboundPacket: don't call encode() method");
    }

    public function fromJava(JavaNetworkSession $session): null|DataPacket|array
    {
        return null;
    }
}