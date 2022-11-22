<?php

declare(strict_types=1);

namespace BigBrother\network\packet\inbound;

use BigBrother\network\JavaNetworkSession;
use BigBrother\network\packet\JavaPacket;
use pocketmine\network\mcpe\protocol\DataPacket;

class ChangeDifficultyPacket extends JavaPacket implements InboundJavaPacket
{

    public int $newDifficulty;

    public function pid(): int
    {
        return self::CHANGE_DIFFICULTY;
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
        $this->newDifficulty = $this->getByte();
    }

    public function fromJava(JavaNetworkSession $session): null|DataPacket|array
    {
        // TODO: Implement fromJava() method.
    }
}