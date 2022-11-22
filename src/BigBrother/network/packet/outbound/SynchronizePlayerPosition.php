<?php

declare(strict_types=1);

namespace BigBrother\network\packet\outbound;

use BigBrother\network\packet\JavaPacket;
use ErrorException;

class SynchronizePlayerPosition extends JavaPacket implements OutboundJavaPacket
{
    public int $x;
    public int $y;
    public int $z;
    public float $yaw;
    public float $pitch;
    public int $flags = 0;
    public int $teleportID = 0;
    public bool $dismountVehicle = true;

    public function pid(): int
    {
        return self::SYNCHRONIZE_PLAYER_POSITION;
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
        $this->putDouble($this->x);
        $this->putDouble($this->y);
        $this->putDouble($this->z);
        $this->putFloat($this->yaw);
        $this->putFloat($this->pitch);
        $this->putByte($this->flags);
        $this->putVarInt($this->teleportID);
        $this->putBool($this->dismountVehicle);
    }

}