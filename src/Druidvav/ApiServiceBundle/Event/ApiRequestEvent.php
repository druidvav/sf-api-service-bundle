<?php

namespace Druidvav\ApiServiceBundle\Event;

use Druidvav\ApiServiceBundle\JsonRpcRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class ApiRequestEvent extends Event
{
    public const NAME = 'dv_api.request';

    protected JsonRpcRequest $request;

    public function __construct(JsonRpcRequest $request)
    {
        $this->request = $request;
    }

    public function getRequest(): JsonRpcRequest
    {
        return $this->request;
    }

    public function getHttpRequest(): Request
    {
        return $this->request->getHttpRequest();
    }
}
