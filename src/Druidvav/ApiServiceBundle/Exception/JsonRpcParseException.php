<?php

namespace Druidvav\ApiServiceBundle\Exception;

class JsonRpcParseException extends JsonRpcException
{
    public const CODE = -32700;

    public function __construct($message = '', $code = self::CODE, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
