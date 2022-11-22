<?php

declare(strict_types=1);

namespace BigBrother\network\packet\outbound;

use BigBrother\network\packet\JavaPacket;
use ErrorException;

class EncryptionRequestPacket extends JavaPacket implements OutboundJavaPacket
{

    /** @var string */
    public $serverID;
    /** @var string */
    public $publicKey;
    /** @var string */
    public $verifyToken;

    public function pid(): int
    {
        return self::ENCRYPTION_REQUEST_PACKET;
    }

    /**
     * @throws ErrorException
     * @deprecated
     */
    protected final function decode(): void
    {
        throw new ErrorException(get_class($this) . " is subclass of OutboundPacket: don't call decode() method");
    }

    protected function encode(): void
    {
        $this->putString($this->serverID);
        $this->putVarInt(strlen($this->publicKey));
        $this->put($this->publicKey);
        $this->putVarInt(strlen($this->verifyToken));
        $this->put($this->verifyToken);
    }
}