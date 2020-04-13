<?php

declare(strict_types=1);

namespace PHPTdGram\TdClient\Exception;

use PHPTdGram\Schema\TdObject;

/**
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class QueryTimeoutException extends TdClientException
{
    private TdObject $packet;

    public function __construct(TdObject $packet)
    {
        $this->packet = $packet;

        parent::__construct(
            sprintf(
                'Query for "%s" packet received timeout',
                $packet->getTdTypeName()
            )
        );
    }

    public function getPacket(): TdObject
    {
        return $this->packet;
    }
}
