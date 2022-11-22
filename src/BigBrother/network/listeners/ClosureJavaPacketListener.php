<?php

declare(strict_types=1);

namespace BigBrother\network\listeners;

use Closure;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\utils\Utils;

final class ClosureJavaPacketListener implements JavaPacketListener
{
    private Closure $listener;

    public function __construct(Closure $closure)
    {
        Utils::validateCallableSignature(static function (ClientboundPacket $packet, NetworkSession $session): void {
        }, $closure);
        $this->listener = $closure;
    }

    public function onPacketSend(ClientboundPacket $packet, NetworkSession $session): void
    {
        ($this->listener)($packet, $session);
    }
}