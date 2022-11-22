<?php

namespace BigBrother\network\types;

use BigBrother\network\packet\inbound\LoginPacket;
use BigBrother\utils\Utils;
use pocketmine\math\Vector3;

class GlobalPosition
{
    public function __construct(private string $dimension, private Vector3 $position)
    {
    }

    public function getDimension(): string
    {
        return $this->dimension;
    }

    public function getPosition(): Vector3
    {
        return $this->position;
    }

    public function getX(): int
    {
        return $this->position->getX();
    }

    public function getY(): int
    {
        return $this->position->getY();
    }

    public function getZ(): int
    {
        return $this->position->getZ();
    }
}
