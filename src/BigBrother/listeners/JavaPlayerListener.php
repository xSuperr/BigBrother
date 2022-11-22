<?php

declare(strict_types=1);

namespace BigBrother\listeners;

use pocketmine\player\Player;

interface JavaPlayerListener
{

    public function onPlayerAdd(Player $player): void;

    public function onPlayerRemove(Player $player): void;
}