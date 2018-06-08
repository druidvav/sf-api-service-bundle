<?php
namespace Druidvav\ApiServiceBundle\Event;

use Druidvav\ApiServiceBundle\JsonRpcResponse;
use Symfony\Component\EventDispatcher\Event;

class ApiResponseEvent extends Event
{
    const NAME = 'dv_api.response';

    protected $response;

    public function __construct(JsonRpcResponse $response)
    {
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }
}