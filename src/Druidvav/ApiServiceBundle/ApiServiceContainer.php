<?php
namespace Druidvav\ApiServiceBundle;

use Druidvav\ApiServiceBundle\Event\ApiRequestEvent;
use Druidvav\ApiServiceBundle\Event\ApiResponseEvent;
use Druidvav\ApiServiceBundle\Exception\JsonRpcExceptionInterface;
use Druidvav\EssentialsBundle\Service\ContainerService;
use Druidvav\ApiServiceBundle\Exception\JsonRpcInvalidMethodException;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use TypeError;

class ApiServiceContainer extends ContainerService
{
    protected $logger;
    protected $methods = [ ];
    protected $dispatcher;
    protected $requestClass;
    protected $responseClass;

    public function __construct(ContainerInterface $container, LoggerInterface $logger, EventDispatcherInterface $dispatcher)
    {
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
        parent::__construct($container);
    }

    public function registerMethod($apiMethodName, $className, $methodName, $methodParams)
    {
        $this->methods[$apiMethodName] = [ 'service' => $className, 'method' => $methodName, 'params' => $methodParams ];
    }

    public function setClassNames($requestClass, $responseClass)
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
            $this->dispatcher->dispatch(ApiRequestEvent::NAME, new ApiRequestEvent($request));
            if (!$request->getMethod() || empty($this->methods[$request->getMethod()])) {
                throw new JsonRpcInvalidMethodException('Method not found');
            }
            $methodDef = $this->methods[$request->getMethod()];
            $requestParams = $request->getParams();
            $requestParamId = 0;
            $callingParams = [ ];
            foreach ($methodDef['params'] as $i => $param) {
                if (!empty($param['className'])) {
                    if ($param['className'] == JsonRpcRequest::class) {
                        $callingParams[$i] = $request;
                    } elseif ($param['className'] == JsonRpcResponse::class) {
                        $callingParams[$i] = $response;
                    } elseif ($request->getObject($param['className'])) {
                        $callingParams[$i] = $request->getObject($param['className']);
                    } else {
                        throw new JsonRpcInvalidMethodException('Method definition is incorrect');
                    }
                } else {
                    $key = $request->isAssociative() ? $param['name'] : $requestParamId;
                    if (!array_key_exists($key, $requestParams) && !$param['optional']) {
                        throw new JsonRpcInvalidMethodException('Undefined parameter "' . $param['name'] . '"');
                    } elseif (array_key_exists($key, $requestParams)) {
                        if (!empty($param['type']) && is_array($requestParams[$key]) && $param['type'] != 'array') {
                            throw new JsonRpcInvalidMethodException('Invalid parameter type for "' . $param['name'] . '", should be "' . $param['type'] . '"');
                        }
                        $callingParams[$i] = $requestParams[$key];
                        $requestParamId++;
                    }
                }
            }
            if ($requestParamId > 0 && isset($requestParams[$requestParamId])) {
                throw new JsonRpcInvalidMethodException('Too many parameters');
            }
            $response->setResult(call_user_func_array([ $this->get($methodDef['service']), $methodDef['method'] ], $callingParams));
        } catch (JsonRpcExceptionInterface $e) {
            $response->setError($e->getMessage(), $e->getCode());
        } catch (Exception $e) {
            $logger->error($e->getMessage(), [ 'exception' => $e ]);
            $response->setError($e->getMessage(), -32603);
        }
        $this->dispatcher->dispatch(ApiResponseEvent::NAME, new ApiResponseEvent($response));
        return $response->getHttpResponse();
    }
}