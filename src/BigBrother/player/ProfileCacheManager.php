<?php

namespace BigBrother\player;

use pocketmine\utils\SingletonTrait;

class ProfileCacheManager
{
    use SingletonTrait;

    protected array $profileCache = [];

    public function getProfileCache(string $username, int $timeout = 60): ?array
    {
        if (isset($this->profileCache[$username]) && (microtime(true) - $this->profileCache[$username]["timestamp"] < $timeout)) {
            return $this->profileCache[$username]["profile"];
        } else {
            unset($this->profileCache[$username]);
            return null;
        }
    }

    public function setProfileCache(string $username, array $profile): void
    {
        $this->profileCache[$username] = [
            "timestamp" => microtime(true),
            "profile" => $profile
        ];
    }
}