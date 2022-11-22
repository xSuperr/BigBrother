<?php

declare(strict_types=1);

namespace BigBrother\network\packet\outbound;

use BigBrother\network\packet\JavaPacket;
use ErrorException;

class LoginSuccessPacket extends JavaPacket implements OutboundJavaPacket
{

    /** @var string */
    public $uuid;
    /** @var string */
    public $name;

    public function pid(): int
    {
        return self::LOGIN_SUCCESS_PACKET;
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
        $this->put($this->uuid);
        $this->putString($this->name);
    }

}