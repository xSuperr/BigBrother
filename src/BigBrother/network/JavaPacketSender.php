<?php

declare(strict_types=1);

namespace BigBrother\network;

use pocketmine\network\mcpe\PacketSender;

final class JavaPacketSender implements PacketSender
{

    public function send(string $payload, bool $immediate): void
    {
    }

    public function close(string $reason = "unknown reason"): void
    {
    }
}