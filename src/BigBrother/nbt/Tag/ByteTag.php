<?php

namespace BigBrother\Nbt\Tag;

use BigBrother\Nbt\IO\Reader\Reader;
use BigBrother\Nbt\IO\Writer\Writer;

class ByteTag extends IntValueTag
{
    public const TYPE = TagType::TAG_Byte;

    /**
     * @inheritDoc
     */
    public function writeContent(Writer $writer): static
    {
        $writer->getSerializer()->writeByte($this->value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function readContent(Reader $reader): static
    {
        $this->value = $reader->getDeserializer()->readByte()->getValue();
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected static function readContentRaw(Reader $reader, TagOptions $options): string
    {
        return $reader->getDeserializer()->readByte()->getRawData();
    }
}
