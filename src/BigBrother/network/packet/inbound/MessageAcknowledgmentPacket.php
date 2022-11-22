<?php

declare(strict_types=1);

namespace BigBrother\network\packet\inbound;

use BigBrother\network\packet\JavaPacket;

class MessageAcknowledgmentPacket extends JavaPacket implements InboundJavaPacket
{

    public bool $hasData;
    public UUID $profileId;
    public int $lastSignatureLength;
    public string $lastSignature;

    public function pid(): int
    {
        return self::MESSAGE_ACKNOWLEDGEMENT;
    }

    /**
     * @throws  ErrorException
     * @deprecated
     */
    protected final function encode(): void
    {
        throw new ErrorException(get_class($this) . " is subclass of InboundPacket: don't call encode() method");
    }

    protected function decode(): void
    {
        $this->hasData = $this->getBool();
        $this->profileId = $this->getUUID(); //TODO
        $this->lastSignatureLength = $this->getVarInt();
        $this->lastSignature = $this->getString();
    }
}