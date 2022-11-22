<?php

declare(strict_types=1);

namespace BigBrother\network\packet\inbound;

use BigBrother\network\packet\JavaPacket;
use ErrorException;

class ClientSettingsPacket extends JavaPacket implements InboundJavaPacket
{

    /** @var string */
    public $lang;
    /** @var int */
    public $viewDistance;
    /** @var int */
    public $chatMode;
    /** @var bool */
    public $chatColors;
    /** @var int */
    public $displayedSkinParts;
    /** @var int */
    public $mainHand;
    /** @var bool */
    public $textFilteringEnabled;
    /** @var bool */
    public $allowsListing;

    public function pid(): int
    {
        return self::CLIENT_SETTINGS_PACKET;
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
        $this->lang = $this->getString();
        $this->viewDistance = $this->getSignedByte();
        $this->chatMode = $this->getVarInt();
        $this->chatColors = $this->getBool();
        $this->displayedSkinParts = $this->getByte();
        $this->mainHand = $this->getVarInt();
        $this->textFilteringEnabled = $this->getBool();
        $this->allowsListing = $this->getBool();
    }
}