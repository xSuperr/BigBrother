<?php

namespace BigBrother\listeners;

use BigBrother\player\JavaPlayerManger;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

class JavaPlayerListenerManager
{
    use SingletonTrait;

    /** @var JavaPlayerListener[] */
    private array $listeners = [];

    public function unregisterListener(JavaPlayerListener $listener): void
    {
        unset($this->listeners[spl_object_id($listener)]);
    }

    public function registerListener(JavaPlayerListener $listener): void
    {
        $this->listeners[spl_object_id($listener)] = $listener;
        $server = Server::getInstance();
        foreach (JavaPlayerManger::getInstance()->getJavaPlayerList() as $uuid => $_) {
            $listener->onPlayerAdd($server->getPlayerByRawUUID($uuid));
        }
    }

    public function getListeners(): array
    {
        return $this->listeners;
    }
}