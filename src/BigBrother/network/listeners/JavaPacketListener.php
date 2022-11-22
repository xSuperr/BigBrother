<?php

declare(strict_types=1);

namespace BigBrother\network\listeners;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ClientboundPacket;

interface JavaPacketListener
{

    public function onPacketSend(ClientboundPacket $packet, NetworkSession $session): void;
}