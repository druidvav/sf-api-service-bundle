<?php

namespace Druidvav\ApiServiceBundle;

use Druidvav\ApiServiceBundle\Exception\JsonRpcInvalidRequestException;
use Druidvav\ApiServiceBundle\Exception\JsonRpcInvalidParamException;
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
     * @throws JsonRpcInvalidParamException
     * @throws JsonRpcParseException
     */
    public function parseRequest(Request $request): void
    {
        $this->httpRequest = $request;
        $this->id = null;
        $this->method = null;
        $this->params = [];
        $this->isAssociative = null;
        $this->objects = [];

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
        $body = json_decode($this->httpRequest->getContent());
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new JsonRpcParseException('Invalid request body, should be valid json');
        }
        if (!($body instanceof \stdClass)) {
            throw new JsonRpcInvalidRequestException('Invalid request body, should be a JSON object');
        }
        if (!property_exists($body, 'jsonrpc') || '2.0' !== $body->jsonrpc) {
            throw new JsonRpcInvalidRequestException('Invalid request body, jsonrpc should be "2.0"');
        }
        if (!property_exists($body, 'id') || (!is_int($body->id) && !is_string($body->id))) {
            throw new JsonRpcInvalidRequestException('Invalid request body, should include id');
        }
        if (!property_exists($body, 'method') || !is_string($body->method) || '' === trim($body->method)) {
            throw new JsonRpcInvalidRequestException('Invalid request body, should include method');
        }
        $this->id = $body->id;
        $this->method = $body->method;

        if (!property_exists($body, 'params')) {
            throw new JsonRpcInvalidRequestException('Invalid request body, should include params');
        }
        if (!($body->params instanceof \stdClass) && !is_array($body->params)) {
            throw new JsonRpcInvalidParamException('Invalid request body, params should be a JSON object or array');
        }

        $this->params = json_decode($this->httpRequest->getContent(), true)['params'];
        $this->isAssociative = $body->params instanceof \stdClass;
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
