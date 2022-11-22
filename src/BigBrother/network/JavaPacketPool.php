<?php

namespace BigBrother\network;

use BigBrother\network\packet\inbound\InboundJavaPacket;
use BigBrother\network\packet\NonAutomaticRegisterTrait;
use BigBrother\utils\Utils;
use pocketmine\utils\SingletonTrait;
use ReflectionClass;

class JavaPacketPool
{
    use SingletonTrait;

    /** @var InboundJavaPacket[] */
    private array $inboundPackets;

    public static function init(): void
    {
        Utils::callDirectory('network/packet/inbound', function (string $namespace): void{
            $rc = new ReflectionClass($namespace);

            if(!$rc->isAbstract()){
                if($rc->implementsInterface(NonAutomaticRegisterTrait::class)){
                    $diff = array_diff($rc->getInterfaceNames(), class_implements($rc->getParentClass()->getName()));

                    if(in_array(NonAutomaticRegisterTrait::class, $diff)){
                        return;
                    }
                }
                self::getInstance()->registerPacket(new $namespace());
            }
        });
    }

    public function registerPacket(InboundJavaPacket $packet): void
    {
        $this->inboundPackets[$packet->pid()] = $packet;
    }

    public function getPacketInbound(int $pid): ?InboundJavaPacket
    {
        return isset($this->inboundPackets[$pid]) ? clone $this->inboundPackets[$pid] : null;
    }
}