<?php

declare(strict_types=1);

namespace BigBrother\network\packet\outbound;

use BigBrother\network\packet\JavaPacket;
use ErrorException;

class ChangeDifficultyPacket extends JavaPacket implements OutboundJavaPacket
{
    public int $difficulty;
    public bool $difficultyLocked = false;

    public function pid(): int
    {
        return self::CHANGE_DIFFICULTY;
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
        $this->putByte($this->difficulty);
        $this->putBool($this->difficultyLocked);
    }
}