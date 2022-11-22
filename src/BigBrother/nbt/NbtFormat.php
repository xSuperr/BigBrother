<?php

namespace BigBrother\Nbt;

use BigBrother\Nbt\Deserializer\BedrockEditionNbtDeserializer;
use BigBrother\Nbt\Deserializer\BedrockEditionNetworkNbtDeserializer;
use BigBrother\Nbt\Deserializer\JavaEditionNbtDeserializer;
use BigBrother\Nbt\Deserializer\NbtDeserializer;
use BigBrother\Nbt\IO\Reader\Reader;
use BigBrother\Nbt\IO\Writer\Writer;
use BigBrother\Nbt\Serializer\BedrockEditionNbtSerializer;
use BigBrother\Nbt\Serializer\BedrockEditionNetworkNbtSerializer;
use BigBrother\Nbt\Serializer\JavaEditionNbtSerializer;
use BigBrother\Nbt\Serializer\NbtSerializer;

class NbtFormat
{
    const JAVA_EDITION = 0;
    const BEDROCK_EDITION = 1;
    const BEDROCK_EDITION_NETWORK = 2;

    /**
     * Find the appropriate deserializer for an NBT format
     *
     * @param int $type
     * @param Reader $reader
     * @return NbtDeserializer
     */
    public static function getDeserializer(int $type, Reader $reader): NbtDeserializer
    {
        return match ($type) {
            static::BEDROCK_EDITION => new BedrockEditionNbtDeserializer($reader),
            static::BEDROCK_EDITION_NETWORK => new BedrockEditionNetworkNbtDeserializer($reader),
            default => new JavaEditionNbtDeserializer($reader),
        };
    }

    /**
     * Find the appropriate serializer for an NBT format
     *
     * @param int $type
     * @param Writer $writer
     * @return NbtSerializer
     */
    public static function getSerializer(int $type, Writer $writer): NbtSerializer
    {
        return match ($type) {
            static::BEDROCK_EDITION => new BedrockEditionNbtSerializer($writer),
            static::BEDROCK_EDITION_NETWORK => new BedrockEditionNetworkNbtSerializer($writer),
            default => new JavaEditionNbtSerializer($writer),
        };
    }
}
