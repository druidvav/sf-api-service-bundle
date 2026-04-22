<?php

namespace Druidvav\ApiServiceBundle;

use Druidvav\ApiServiceBundle\Event\ApiRequestEvent;
use Druidvav\ApiServiceBundle\Event\ApiResponseEvent;
use Druidvav\ApiServiceBundle\Exception\JsonRpcExceptionInterface;
use Druidvav\ApiServiceBundle\Exception\JsonRpcInvalidMethodException;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ApiServiceContainer
{
    protected LoggerInterface $logger;
    protected $methods = [];
    protected EventDispatcherInterface $dispatcher;
    protected $requestClass;
    protected $responseClass;
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container, LoggerInterface $logger, EventDispatcherInterface $dispatcher)
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
    }

    public function registerMethod($apiMethodName, $className, $methodName, $methodParams): void
    {
        $this->methods[$apiMethodName] = ['service' => $className, 'method' => $methodName, 'params' => $methodParams];
    }

    public function setClassNames($requestClass, $responseClass): void
    {
        $this->requestClass = $requestClass;
        $this->responseClass = $responseClass;
    }

    public function handleRequest(Request $httpRequest, LoggerInterface $logger): JsonResponse
    {
        /** @var JsonRpcRequest $request */
        $request = new $this->requestClass();
        /** @var JsonRpcResponse $response */
        $response = new $this->responseClass($request);
        try {
            $request->parseRequest($httpRequest);
            $this->dispatcher->dispatch(new ApiRequestEvent($request), ApiRequestEvent::NAME);
            if (!$request->getMethod() || empty($this->methods[$request->getMethod()])) {
                throw new JsonRpcInvalidMethodException('Method not found');
            }
            $methodDef = $this->methods[$request->getMethod()];
            $requestParams = $request->getParams();
            $requestParamId = 0;
            $callingParams = [];
            foreach ($methodDef['params'] as $i => $param) {
                if (!empty($param['className'])) {
                    if (JsonRpcRequest::class == $param['className']) {
                        $callingParams[$i] = $request;
                    } elseif (Request::class == $param['className']) {
                        $callingParams[$i] = $request->getHttpRequest();
                    } elseif (JsonRpcResponse::class == $param['className']) {
                        $callingParams[$i] = $response;
                    } elseif ($request->getObject($param['className'])) {
                        $callingParams[$i] = $request->getObject($param['className']);
                    } else {
                        throw new JsonRpcInvalidMethodException('Method definition is incorrect');
                    }
                } else {
                    $key = $request->isAssociative() ? $param['name'] : $requestParamId;
                    if (!array_key_exists($key, $requestParams) && !$param['optional']) {
                        throw new JsonRpcInvalidMethodException('Undefined parameter "'.$param['name'].'"');
                    }
                    if (array_key_exists($key, $requestParams)) {
                        if (!empty($param['type']) && is_array($requestParams[$key]) && 'array' != $param['type']) {
                            throw new JsonRpcInvalidMethodException('Invalid parameter type for "'.$param['name'].'", should be "'.$param['type'].'"');
                        }
                        if (!$param['nullable'] && null === $requestParams[$key]) {
                            throw new JsonRpcInvalidMethodException('Parameter "'.$param['name'].'" cannot be null');
                        }
                        $callingParams[$i] = $requestParams[$key];
                        ++$requestParamId;
                    }
                }
            }
            if ($requestParamId > 0 && isset($requestParams[$requestParamId])) {
                throw new JsonRpcInvalidMethodException('Too many parameters');
            }
            $response->setResult(call_user_func_array([$this->container->get($methodDef['service']), $methodDef['method']], $callingParams));
        } catch (JsonRpcExceptionInterface $e) {
            $response->setError($e->getMessage(), $e->getCode());
        } catch (Exception $e) {
            $logger->error($e->getMessage(), ['exception' => $e]);
            $response->setError($e->getMessage(), -32603);
        }
        $this->dispatcher->dispatch(new ApiResponseEvent($response), ApiResponseEvent::NAME);

        return $response->getHttpResponse();
    }
}
