<?php

namespace BigBrother\Nbt\Tag;

use BigBrother\Nbt\IO\Reader\Reader;
use BigBrother\Nbt\IO\Writer\Writer;

class IntTag extends IntValueTag
{
    public const TYPE = TagType::TAG_Int;

    /**
     * @inheritDoc
     */
    public function writeContent(Writer $writer): static
    {
        $writer->getSerializer()->writeInt($this->value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function readContent(Reader $reader): static
    {
        $this->value = $reader->getDeserializer()->readInt()->getValue();
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected static function readContentRaw(Reader $reader, TagOptions $options): string
    {
        return $reader->getDeserializer()->readInt()->getRawData();
    }
}
