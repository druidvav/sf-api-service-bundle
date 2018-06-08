<?php
namespace Druidvav\ApiServiceBundle\Event;

use Druidvav\ApiServiceBundle\JsonRpcRequest;
use Symfony\Component\EventDispatcher\Event;

class ApiRequestEvent extends Event
{
    const NAME = 'dv_api.request';

    protected $request;

    public function __construct(JsonRpcRequest $request)
    {
        $this->request = $request;
    }

    public function getRequest()
    {
        return $this->request;
    }
}