<?php

declare(strict_types=1);

namespace BigBrother\network\packet\inbound;


use BigBrother\network\JavaNetworkSession;
use BigBrother\network\packet\JavaPacket;
use ErrorException;
use pocketmine\network\mcpe\protocol\DataPacket;

class EncryptionResponsePacket extends JavaPacket implements InboundJavaPacket
{
    public string $sharedSecret;
    public bool $hasVerifyToken;
    public ?string $verifyToken = null;
    public ?int $salt = null;
    public ?string $signature = null;

    public function pid(): int
    {
        return self::ENCRYPTION_RESPONSE;
    }

    protected function decode(): void
    {
        $this->sharedSecret = $this->get($this->getVarInt());
        $this->hasVerifyToken = $this->getBool();
        if ($this->hasVerifyToken)
        {
            $this->verifyToken = $this->get($this->getVarInt());
        } else {
            $this->salt = $this->getLong();
            $this->signature = $this->get($this->getVarInt());
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