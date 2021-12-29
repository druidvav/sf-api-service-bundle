<?php
namespace Druidvav\ApiServiceBundle\Event;

use Druidvav\ApiServiceBundle\JsonRpcRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\Event;
//use Symfony\Contracts\EventDispatcher\Event;

class ApiRequestEvent extends Event
{
    const NAME = 'dv_api.request';

    protected $request;

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