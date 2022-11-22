<?php

declare(strict_types=1);

namespace BigBrother\network\packet\inbound;

use BigBrother\network\JavaNetworkSession;
use BigBrother\network\packet\JavaPacket;
use pocketmine\network\mcpe\protocol\DataPacket;

class PlayerCommandPacket extends JavaPacket implements InboundJavaPacket
{

    public int $entityId;
    public int $actionId;
    public int $jumpBoost;

    public function pid(): int
    {
        return self::PLAYER_COMMAND;
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
        $this->entityId = $this->getVarInt();
        $this->actionId = $this->getVarInt();
        $this->jumpBoost = $this->getVarInt();
    }

    public function fromJava(JavaNetworkSession $session): null|DataPacket|array
    {
        // TODO: Implement fromJava() method.
    }
}