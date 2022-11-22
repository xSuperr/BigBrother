<?php

declare(strict_types=1);

namespace BigBrother\network\packet\inbound;

use BigBrother\network\JavaNetworkSession;
use BigBrother\network\packet\JavaPacket;
use ErrorException;
use pocketmine\network\mcpe\protocol\DataPacket;

class PlaceRecipePacket extends JavaPacket implements InboundJavaPacket
{

    public int $windowId;
    public string $recipeId;
    public bool $isMakeAll = false;

    public function pid(): int
    {
        return self::PLACE_RECIPE;
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
        $this->windowId = $this->getSignedByte();
        $this->recipeId = $this->getString();
        $this->isMakeAll = $this->getBool();
    }

    public function fromJava(JavaNetworkSession $session): null|DataPacket|array
    {
        // TODO: Implement fromJava() method.
    }
}