<?php

namespace BigBrother\Nbt\Serializer;

use BigBrother\Nbt\MachineByteOrder;
use BigBrother\Nbt\NbtFormat;
use pocketmine\utils\Binary;

class JavaEditionNbtSerializer extends NbtSerializer
{
    /**
     * @inheritDoc
     */
    public function getFormat(): int
    {
        return NbtFormat::JAVA_EDITION;
    }

    /**
     * @inheritDoc
     */
    public function writeLengthPrefix(int $value): static
    {
        $this->getWriter()->write(Binary::writeInt(Binary::unsignInt($value)));
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function writeStringLengthPrefix(int $value): static
    {
        $this->getWriter()->write(Binary::writeShort($value));
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function writeByte(int $value): static
    {
        $this->getWriter()->write(Binary::writeByte(Binary::unsignByte($value)));
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function writeShort(int $value): static
    {
        $this->getWriter()->write(Binary::writeShort(Binary::unsignShort($value)));
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function writeInt(int $value): static
    {
        $this->getWriter()->write(Binary::writeInt(Binary::unsignInt($value)));
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function writeLong(int $value): static
    {
        $packed = pack("q", $value);
        $this->getWriter()->write(MachineByteOrder::isLittleEndian() ? strrev($packed) : $packed);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function writeFloat(float $value): static
    {
        $this->getWriter()->write(Binary::writeFloat($value));
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function writeDouble(float $value): static
    {
        $this->writer->write(Binary::writeDouble($value));
        return $this;
    }
}
