<?php

declare(strict_types=1);

namespace BigBrother\network\packet\outbound;

use BigBrother\network\packet\JavaPacket;
use ErrorException;

class PluginMessagePacket extends JavaPacket implements OutboundJavaPacket
{
    public string $channel;
    public array $data = [];

    public function pid(): int
    {
        return self::PLUGIN_MESSAGE;
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
        $this->putString($this->channel);

        if ($this->channel === "minecraft:brand") $this->putString($this->data[0]);
    }
}