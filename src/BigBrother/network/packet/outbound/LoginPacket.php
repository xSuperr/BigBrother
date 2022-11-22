<?php

declare(strict_types=1);

namespace BigBrother\network\packet\outbound;

use BigBrother\Nbt\IO\Writer\StringWriter;
use BigBrother\Nbt\NbtFormat;
use BigBrother\Nbt\Tag\CompoundTag;
use BigBrother\network\packet\JavaPacket;
use BigBrother\network\types\GlobalPosition;
use ErrorException;

class LoginPacket extends JavaPacket implements OutboundJavaPacket
{
    private const GAMEMODE_MASK = 0x07;

    public int $entityId;
    public bool $hardcore;
    public int $gamemode;
    public int $previousGamemode;
    public int $worldCount;
    public array $worldNames;
    public CompoundTag $registry;
    public string $dimension;
    public string $worldName;
    public int $hashedSeed;
    public int $maxPlayers;
    public int $viewDistance;
    public int $simulationDistance;
    public bool $reducedDebugInfo;
    public bool $enableRespawnScreen;
    public bool $debug;
    public bool $flat;
    public ?GlobalPosition $lastDeathPos;

    public function pid(): int
    {
        return self::LOGIN;
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
        $this->putInt($this->entityId);
        $this->putBool($this->hardcore);
        $this->putByte($this->gamemode & self::GAMEMODE_MASK);
        $this->putByte($this->previousGamemode);
        $this->putVarInt($this->worldCount);
        foreach ($this->worldNames as $name) {
            $this->putString($name);
        }

        $writer = (new StringWriter())->setFormat(NbtFormat::JAVA_EDITION);
        $this->registry->write($writer);
        $this->put($writer->getStringData());

        $this->putString($this->dimension);
        $this->putString($this->worldName);
        $this->putLong($this->hashedSeed);
        $this->putVarInt($this->maxPlayers);
        $this->putVarInt($this->viewDistance);
        $this->putVarInt($this->simulationDistance);
        $this->putBool($this->reducedDebugInfo);
        $this->putBool($this->enableRespawnScreen);
        $this->putBool($this->debug);
        $this->putBool($this->flat);
        $this->putBool($this->lastDeathPos !== null);
        if ($this->lastDeathPos !== null) {
            // TODO: write global pos
        }

    }
}