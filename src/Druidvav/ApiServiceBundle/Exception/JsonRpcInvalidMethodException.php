<?php
namespace Druidvav\ApiServiceBundle\Exception;

class JsonRpcInvalidMethodException extends JsonRpcException
{
    const CODE = -32601;

    public function __construct($message = "", $code = self::CODE, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}