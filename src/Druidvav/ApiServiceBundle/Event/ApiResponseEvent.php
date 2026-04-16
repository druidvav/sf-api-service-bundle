<?php
namespace Druidvav\ApiServiceBundle\Event;

use Druidvav\ApiServiceBundle\JsonRpcResponse;
use Symfony\Contracts\EventDispatcher\Event;

class ApiResponseEvent extends Event
{
    const NAME = 'dv_api.response';

    protected $response;

    public function __construct(JsonRpcResponse $response)
    {
        $this->response = $response;
    }

    public function getResponse(): JsonRpcResponse
    {
        return $this->response;
    }
}