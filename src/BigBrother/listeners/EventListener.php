<?php

namespace BigBrother\listeners;

use BigBrother\player\JavaPlayerManger;
use InvalidArgumentException;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;

class EventListener implements Listener
{
    /**
     * @param PlayerQuitEvent $event
     * @priority MONITOR
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        try {
            JavaPlayerManger::getInstance()->removePlayer($player, false);
        } catch (InvalidArgumentException $e) {
        }
    }
}