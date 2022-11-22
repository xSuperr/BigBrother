<?php

declare(strict_types=1);

namespace BigBrother\player;

use BigBrother\BigBrother;
use BigBrother\network\JavaNetworkSession;
use pocketmine\player\Player;
use pocketmine\Server;

final class JavaPlayer
{

    public BigBrother $loader;
    public $status = 0;
    private JavaNetworkSession $session;
    private Player $player;
    /** @var array<string, mixed> */
    private array $metadata = [];

    public function __construct(JavaNetworkSession $session, BigBrother $loader)
    {
        $this->session = $session;
        $this->loader = $loader;
        $this->player = $session->getPlayer();
    }

    public function getServer(): Server
    {
        return Server::getInstance();
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getPlayerNullable(): ?Player
    {
        return $this->player;
    }

    public function destroy(): void
    {
        $this->metadata = [];
    }

    public function getNetworkSession(): JavaNetworkSession
    {
        return $this->session;
    }

    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    public function setMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
    }

    public function deleteMetadata(string $key): void
    {
        unset($this->metadata[$key]);
    }

}