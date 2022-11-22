<?php

declare(strict_types=1);

namespace BigBrother\network\packet\inbound;

use BigBrother\network\packet\JavaPacket;
use pocketmine\item\Item;

class ClickContainerPacket extends JavaPacket implements InboundJavaPacket
{

    public int $windowId;
    public int $stateId;
    public int $slot;
    public int $button;
    public int $actionNumber;
    public int $mode;
    public array $changedSlots;
    public Item $clickedItem;

    public function pid(): int
    {
        return self::CLICK_CONTAINER;
    }

    protected function decode(): void
    {
        $this->windowId = $this->getByte();
        $this->stateId = $this->getVarInt();
        $this->slot = $this->getShort();
        $this->button = $this->getByte();
        $this->actionNumber = $this->getSignedShort();
        $this->mode = $this->getVarInt();
        for ($i = 0; $i < $this->getVarInt(); $i++) {
            $slotid = $this->getShort();
            $item = $this->getSlot();
            $this->changedSlots[] = [$slotid, $item];
        }
        $this->clickedItem = $this->getSlot();
    }
}