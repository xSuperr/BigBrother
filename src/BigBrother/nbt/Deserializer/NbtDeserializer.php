<?php

namespace BigBrother\Nbt\Deserializer;

use BigBrother\Nbt\IO\Reader\Reader;

abstract class NbtDeserializer
{
    public function __construct(protected Reader $reader)
    {
    }

    /**
     * Read an NBT length prefix (TAG_List, TAG_Byte_Array, TAG_Int_Array, and TAG_Long_Array)
     *
     * @return DeserializerIntReadResult
     */
    abstract public function readLengthPrefix(): DeserializerIntReadResult;

    /**
     * Read an NBT string length prefix
     *
     * @return DeserializerIntReadResult
     */
    abstract public function readStringLengthPrefix(): DeserializerIntReadResult;

    /**
     * @return DeserializerIntReadResult
     */
    abstract public function readByte(): DeserializerIntReadResult;

    /**
     * @return DeserializerIntReadResult
     */
    abstract public function readShort(): DeserializerIntReadResult;

    /**
     * @return DeserializerIntReadResult
     */
    abstract public function readInt(): DeserializerIntReadResult;

    /**
     * @return DeserializerIntReadResult
     */
    abstract public function readLong(): DeserializerIntReadResult;

    /**
     * @return DeserializerFloatReadResult
     */
    abstract public function readFloat(): DeserializerFloatReadResult;

    /**
     * @return DeserializerFloatReadResult
     */
    abstract public function readDouble(): DeserializerFloatReadResult;

    /**
     * @return int
     */
    abstract public function getFormat(): int;

    /**
     * @return Reader
     */
    public function getReader(): Reader
    {
        return $this->reader;
    }
}
