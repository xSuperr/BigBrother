<?php

namespace BigBrother\Nbt\Serializer;

use BigBrother\Nbt\NbtFormat;
use pocketmine\utils\Binary;

class BedrockEditionNetworkNbtSerializer extends BedrockEditionNbtSerializer
{
    /**
     * @inheritDoc
     */
    public function getFormat(): int
    {
        return NbtFormat::BEDROCK_EDITION_NETWORK;
    }

    /**
     * @inheritDoc
     */
    public function writeLengthPrefix(int $value): static
    {
        $this->getWriter()->write(Binary::writeVarInt($value));
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function writeStringLengthPrefix(int $value): static
    {
        $this->getWriter()->write(Binary::writeUnsignedVarInt($value));
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function writeInt(int $value): static
    {
        $this->getWriter()->write(Binary::writeVarInt($value));
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function writeLong(int $value): static
    {
        $this->getWriter()->write(Binary::writeVarLong($value));
        return $this;
    }
}
