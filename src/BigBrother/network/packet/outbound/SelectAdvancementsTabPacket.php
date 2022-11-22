<?php

declare(strict_types=1);

namespace BigBrother\network\packet\outbound;

use BigBrother\network\packet\JavaPacket;
use ErrorException;

class SelectAdvancementsTabPacket extends JavaPacket implements OutboundJavaPacket
{
    public bool $hasId;
    public string $identifier = '';

    public function pid(): int
    {
        return self::SELECT_ADVANCEMENTS_TAB;
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
        $this->putBool($this->hasId);
        $this->putString($this->identifier);
    }
}