<?php

namespace Druidvav\ApiServiceBundle;

use Druidvav\ApiServiceBundle\Exception\JsonRpcInvalidRequestException;
use Druidvav\ApiServiceBundle\Exception\JsonRpcParseException;
use Symfony\Component\HttpFoundation\Request;

class JsonRpcRequest
{
    private ?Request $httpRequest = null;
    private $id;
    private $method;
    private $params;
    private ?bool $isAssociative = null;

    private array $objects = [];

    /**
     * @throws JsonRpcInvalidRequestException
     * @throws JsonRpcParseException
     */
    public function parseRequest(Request $request): void
    {
        $this->httpRequest = $request;
        if (!$this->httpRequest->isMethod('POST')) {
            throw new JsonRpcParseException('Invalid method, method should be POST');
        }
        if (method_exists($this->httpRequest, 'getContentType')) {
            $contentFormat = $this->httpRequest->getContentType();
        } elseif (method_exists($this->httpRequest, 'getContentTypeFormat')) {
            $contentFormat = $this->httpRequest->getContentTypeFormat();
        } else {
            throw new JsonRpcParseException('Unable to determine content type');
        }
        if ('json' != $contentFormat) {
            throw new JsonRpcParseException('Content-Type should be application/json');
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
        $this->isAssociative = array_keys($this->params) && 0 !== array_keys($this->params)[0];
    }

    public function getHttpRequest(): Request
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

    public function getParams(): array
    {
        return $this->params ?: [];
    }

    public function isAssociative(): ?bool
    {
        return $this->isAssociative;
    }

    public function getParam($param, $def = null)
    {
        return $this->params[$param] ?? $def;
    }

    public function addObject($object): void
    {
        $this->objects[get_class($object)] = $object;
    }

    public function getObject($class)
    {
        return $this->objects[$class] ?? null;
    }
}
