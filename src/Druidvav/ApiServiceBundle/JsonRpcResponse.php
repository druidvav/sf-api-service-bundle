<?php

namespace Druidvav\ApiServiceBundle;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Stopwatch\Stopwatch;

class JsonRpcResponse
{
    protected Stopwatch $stopwatch;
    protected JsonRpcRequest $request;
    protected ?array $error = null;
    protected $result = false;
    protected $httpResponse;

    public function __construct(JsonRpcRequest $request)
    {
        $this->request = $request;
        $this->stopwatch = new Stopwatch();
        $this->stopwatch->start('api');
    }

    public function getRequest(): JsonRpcRequest
    {
        return $this->request;
    }

    public function getStopwatch(): Stopwatch
    {
        return $this->stopwatch;
    }

    public function getDuration()
    {
        return $this->getStopwatch()->getEvent('api')->getDuration();
    }

    public function setError($message, $code = 0): void
    {
        $this->error = [
            'code' => $code,
            'message' => $message,
        ];
    }

    public function setResult($result): void
    {
        $this->result = $result;
    }

    public function getError(): ?array
    {
        return null === $this->error || [] === $this->error ? null : $this->error;
    }

    public function getResult()
    {
        return null === $this->error || [] === $this->error ? $this->result : null;
    }

    protected function getResponseArray(): array
    {
        $result = [
            'jsonrpc' => '2.0',
            'id' => $this->getRequest() ? $this->getRequest()->getId() : null,
        ];
        if (null !== $this->error && [] !== $this->error) {
            $result['error'] = $this->error;
        } else {
            $result['result'] = $this->result;
        }

        return $result;
    }

    public function generateHttpResponse(): void
    {
        $this->stopwatch->stop('api');
        $response = new JsonResponse($this->getResponseArray());
        $response->setEncodingOptions($response->getEncodingOptions() | JSON_UNESCAPED_UNICODE);
        $response->headers->add(['X-Api-Time' => $this->getDuration()]);
        $this->httpResponse = $response;
    }

    public function getHttpResponse(): JsonResponse
    {
        if (empty($this->httpResponse)) {
            $this->generateHttpResponse();
        }

        return $this->httpResponse;
    }
}
