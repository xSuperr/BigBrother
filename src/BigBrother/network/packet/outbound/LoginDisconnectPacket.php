<?php

declare(strict_types=1);

namespace BigBrother\network\packet\outbound;

use BigBrother\network\packet\JavaPacket;
use ErrorException;

class LoginDisconnectPacket extends JavaPacket implements OutboundJavaPacket
{

    /** @var string */
    public $reason;

    public function pid(): int
    {
        return self::LOGIN_DISCONNECT_PACKET;
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
        $this->putString($this->reason);
    }
}