<?php

declare(strict_types=1);

namespace BigBrother\listeners;

use Closure;
use pocketmine\player\Player;
use pocketmine\utils\Utils;

final class ClosureJavaPlayerListener implements JavaPlayerListener
{

    private Closure $addPlayerClosure;
    private Closure $removePlayerClosure;

    public function __construct(Closure $addPlayer, Closure $removePlayer)
    {
        Utils::validateCallableSignature(static function (Player $player): void {}, $addPlayer);
        $this->addPlayerClosure = $addPlayer;

        Utils::validateCallableSignature(static function (Player $player): void {}, $removePlayer);
        $this->removePlayerClosure = $removePlayer;
    }

    public function onPlayerAdd(Player $player): void
    {
        ($this->addPlayerClosure)($player);
    }

    public function onPlayerRemove(Player $player): void
    {
        ($this->removePlayerClosure)($player);
    }
}