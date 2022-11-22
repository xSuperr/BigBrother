<?php

declare(strict_types=1);

namespace BigBrother\network\packet\inbound;

use BigBrother\network\JavaNetworkSession;
use BigBrother\network\packet\JavaPacket;
use ErrorException;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\TextPacket;

class ChatMessagePacket extends JavaPacket implements InboundJavaPacket
{

    public string $message;
    public int $timestamp;
    public int $salt;
    //idk
    public $signature;
    public bool $signed;

    public function pid(): int
    {
        return self::CHAT_MESSAGE;
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
        $this->message = $this->getString();
        /*		$this->timestamp = $this->getLong();
                $this->salt = $this->getlong();
        // 		$this->signature = $this->get();
                $this->signed = $this->getBool();*/
    }

    public function fromJava(JavaNetworkSession $session): null|DataPacket|array
    {
        return TextPacket::raw($this->message);
    }
}