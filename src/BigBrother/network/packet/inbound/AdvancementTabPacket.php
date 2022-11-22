<?php

declare(strict_types=1);

namespace BigBrother\network\packet\inbound;

use BigBrother\network\JavaNetworkSession;
use BigBrother\network\packet\JavaPacket;
use BigBrother\network\packet\outbound\SelectAdvancementsTabPacket;
use ErrorException;
use pocketmine\network\mcpe\protocol\DataPacket;

class SeenAdvancementsPacket extends JavaPacket implements InboundJavaPacket
{

    const OPENED_TAB = 0;
    const CLOSED_SCREEN = 1;

    public int $action;
    public string $tabId = '';

    public function pid(): int
    {
        return self::SEEN_ADVANCEMENTS;
    }

    /**
     * @throws  ErrorException
     * @deprecated
     */
    protected final function encode(): void
    {
        throw new ErrorException(get_class($this) . " is subclass of InboundPacket: don't call encode() method");
    }

    protected function decode(): void
    {
        $this->action = $this->getVarInt();
        if ($this->action === self::OPENED_TAB) {
            $this->tabId = $this->getString();
        }
    }

    public function fromJava(JavaNetworkSession $session): null|DataPacket|array
    {
        if ($this->action === self::OPENED_TAB) {
            $pk = new SelectAdvancementsTabPacket();
            $pk->hasId = true;
            $pk->identifier = $this->tabId;
            $session->putRawPacket($pk);
        }

        return null;
    }
}