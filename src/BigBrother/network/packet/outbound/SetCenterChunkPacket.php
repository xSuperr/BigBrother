<?php

declare(strict_types=1);

namespace BigBrother\network\packet\outbound;

use BigBrother\network\packet\JavaPacket;
use ErrorException;

class SetCenterChunkPacket extends JavaPacket implements OutboundJavaPacket
{

    public int $chunkX;
    public int $chunkZ;

    public function pid(): int
    {
        return self::SET_CENTER_CHUNK_PACKET;
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
        $this->putVarInt($this->chunkX);
        $this->putVarInt($this->chunkZ);
    }
}