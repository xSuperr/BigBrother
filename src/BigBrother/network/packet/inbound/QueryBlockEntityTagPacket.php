<?php

declare(strict_types=1);

namespace BigBrother\network\packet\inbound;

use BigBrother\network\JavaNetworkSession;
use BigBrother\network\packet\JavaPacket;
use ErrorException;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;

class QueryBlockEntityTagPacket extends JavaPacket implements InboundJavaPacket
{

    public int $transactionId;
    public int $x;
    public int $y;
    public int $z;

    public function pid(): int
    {
        return self::QUERY_BLOCK_ENTITY_TAG;
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
        $this->transactionId = $this->getVarInt();
        $this->getPosition($this->x, $this->y, $this->z);
    }

    public function fromJava(JavaNetworkSession $session): null|DataPacket|array
    {
        // TODO: Implement fromJava() method.
    }
}