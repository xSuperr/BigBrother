<?php

namespace BigBrother\Nbt\Tag;

use BigBrother\Nbt\IO\Reader\Reader;
use BigBrother\Nbt\IO\Writer\Writer;

class DoubleTag extends FloatValueTag
{
    use RawValueTag;

    public const TYPE = TagType::TAG_Double;

    /**
     * @inheritDoc
     */
    public function writeContent(Writer $writer): static
    {
        if ($this->rawValueValid($writer->getFormat())) {
            $writer->write($this->rawValue);
            return $this;
        }
        $writer->getSerializer()->writeDouble($this->value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function readContent(Reader $reader): static
    {
        $result = $reader->getDeserializer()->readDouble();
        $this->setRawDataFromSerializer($result, $reader->getFormat());
        $this->value = $result->getValue();
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected static function readContentRaw(Reader $reader, TagOptions $options): string
    {
        return $reader->getDeserializer()->readDouble()->getRawData();
    }

    /**
     * @inheritDoc
     */
    public function setValue(float $value): FloatValueTag
    {
        $this->resetRawValue();
        return parent::setValue($value);
    }
}
