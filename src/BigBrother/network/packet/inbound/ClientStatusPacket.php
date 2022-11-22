<?php

declare(strict_types=1);

namespace BigBrother\network\packet\inbound;

use BigBrother\network\JavaNetworkSession;
use BigBrother\network\packet\JavaPacket;
use ErrorException;
use pocketmine\network\mcpe\protocol\DataPacket;

//TODO: Does this exists?
class ClientStatusPacket extends JavaPacket implements InboundJavaPacket
{
    public int $actionId;

    public function pid(): int
    {
        return self::CLIENT_STATUS_PACKET;
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
        $this->actionId = $this->getVarInt();
    }

    public function fromJava(JavaNetworkSession $session): null|DataPacket|array
    {
        // TODO: Implement fromJava() method.
    }
}