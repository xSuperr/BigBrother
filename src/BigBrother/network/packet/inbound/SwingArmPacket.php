<?php

declare(strict_types=1);

namespace BigBrother\network\packet\inbound;

use BigBrother\network\JavaNetworkSession;
use BigBrother\network\packet\JavaPacket;
use ErrorException;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\PlayerAction;

class SwingArmPacket extends JavaPacket implements InboundJavaPacket
{
    public int $hand;

    public function pid(): int
    {
        return self::SWING_ARM;
    }

    /**
     * @throws  ErrorException
     * @deprecated
     */
    protected final function encode(): void
    {
        throw new ErrorException(get_class($this) . " is subclass of InboundPacket: don't call encode() method");
    }

    protected function decode(): void
    {
        $this->hand = $this->getVarInt();
    }

    public function fromJava(JavaNetworkSession $session): null|DataPacket|array
    {
        $pk = new AnimatePacket();
        $pk->action = 1;
        $pk->actorRuntimeId = $session->getPlayer()->getId();

        $pos = $session->bigBrother_breakPosition;

        if (!$pos[0]->equals(new Vector3(0, 0, 0))) {
            $packets = [$pk];

            $pk = new PlayerActionPacket();
            $pk->actorRuntimeId = $session->getPlayer()->getId();
            $pk->action = PlayerAction::CONTINUE_DESTROY_BLOCK;
            $pk->blockPosition = new BlockPosition($pos[0]->x, $pos[0]->y, $pos[0]->z);
            $pk->face = $session->getPlayer()->getHorizontalFacing();
            $packets[] = $pk;

            return $packets;
        }

        return $pk;
    }
}