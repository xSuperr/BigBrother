<?php

namespace BigBrother\Nbt\Tag;

use BigBrother\Nbt\IO\Reader\Reader;
use BigBrother\Nbt\IO\Writer\Writer;

class EndTag extends Tag
{
    public const TYPE = TagType::TAG_End;

    /**
     * @inheritDoc
     */
    public function writeContent(Writer $writer): static
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function readContent(Reader $reader): static
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected static function readContentRaw(Reader $reader, TagOptions $options): string
    {
        return "";
    }

    /**
     * @inheritDoc
     */
    public static function canBeNamed(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    protected function getValueString(): string
    {
        return "";
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    function equals(Tag $tag): bool
    {
        return $tag->getType() === $this->getType();
    }
}
