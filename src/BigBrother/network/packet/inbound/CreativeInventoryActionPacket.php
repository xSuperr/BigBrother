<?php

declare(strict_types=1);

namespace BigBrother\network\packet\inbound;

use BigBrother\network\JavaNetworkSession;
use BigBrother\network\packet\JavaPacket;
use ErrorException;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\DataPacket;

class CreativeInventoryActionPacket extends JavaPacket implements InboundJavaPacket
{

    public int $slot;
    public Item $clickedItem;

    public function pid(): int
    {
        return self::CREATIVE_INVENTORY_ACTION_PACKET;
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
        $this->slot = $this->getShort();//Signed ...?
        $this->clickedItem = $this->getSlot();
    }

    public function fromJava(JavaNetworkSession $session): null|DataPacket|array
    {
        // TODO: Implement fromJava() method.
    }
}