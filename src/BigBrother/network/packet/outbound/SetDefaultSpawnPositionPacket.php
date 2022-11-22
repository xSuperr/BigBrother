<?php

declare(strict_types=1);

namespace BigBrother\network\packet\outbound;

use BigBrother\network\packet\JavaPacket;
use ErrorException;
use pocketmine\math\Vector3;

class SetDefaultSpawnPositionPacket extends JavaPacket implements OutboundJavaPacket
{
    public Vector3 $location;
    public float $angle;

    public function pid(): int
    {
        return self::SET_DEFAULT_SPAWN_POSITION;
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
        $this->putPosition($this->location->x, $this->location->y, $this->location->z);
        $this->putFloat($this->angle);
    }
}