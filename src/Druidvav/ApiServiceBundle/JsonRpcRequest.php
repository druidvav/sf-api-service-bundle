<?php
namespace Druidvav\ApiServiceBundle;

use Druidvav\ApiServiceBundle\Exception\JsonRpcInvalidRequestException;
use Druidvav\ApiServiceBundle\Exception\JsonRpcParseException;
use Symfony\Component\HttpFoundation\Request;

class JsonRpcRequest
{
    private $httpRequest;
    private $id;
    private $method;
    private $params;
    private $isAssociative;

    private $objects = [ ];

    /**
     * @param Request $request
     * @throws JsonRpcInvalidRequestException
     * @throws JsonRpcParseException
     */
    public function parseRequest(Request $request)
    {
        $this->httpRequest = $request;
        if (!$this->httpRequest->isMethod('POST')) {
            throw new JsonRpcParseException('Invalid method, method should be POST');
        }
        if ($this->httpRequest->getContentType() != 'json' &&
            $this->httpRequest->getContentType() != 'json-rpc') {
            throw new JsonRpcParseException('Content-Type should by application/json');
        }
        $body = json_decode($this->httpRequest->getContent(), true);
        if (empty($body)) {
            throw new JsonRpcParseException('Invalid request body, should be valid json');
        }
        if (empty($body['id'])) {
            throw new JsonRpcInvalidRequestException('Invalid request body, should include id');
        }
        if (empty($body['method'])) {
            throw new JsonRpcInvalidRequestException('Invalid request body, should include method');
        }
        if (!isset($body['params'])) {
            throw new JsonRpcInvalidRequestException('Invalid request body, should include params');
        }
        $this->id = $body['id'];
        $this->method = $body['method'];
        $this->params = $body['params'];
        $this->isAssociative = array_keys($this->params) ? array_keys($this->params)[0] !== 0 : false;
    }

    /**
     * @return Request
     */
    public function getHttpRequest()
    {
        return $this->httpRequest;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getParams()
    {
        return $this->params ? $this->params : [ ];
    }

    public function isAssociative()
    {
        return $this->isAssociative;
    }

    public function getParam($param, $def = null)
    {
        return isset($this->params[$param]) ? $this->params[$param] : $def;
    }

    public function addObject($object)
    {
        $this->objects[get_class($object)] = $object;
    }

    public function getObject($class)
    {
        return isset($this->objects[$class]) ? $this->objects[$class] : null;
    }
}