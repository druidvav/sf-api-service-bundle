<?php
namespace Druidvav\ApiServiceBundle;

use Druidvav\EssentialsBundle\Service\ContainerService;
use Druidvav\ApiServiceBundle\Exception\JsonRpcException;
use Druidvav\ApiServiceBundle\Exception\JsonRpcInvalidMethodException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class ApiServiceContainer extends ContainerService
{
    protected $logger;
    protected $methods = [ ];

    public function __construct(ContainerInterface $container = null, LoggerInterface $logger)
    {
        $this->logger = $logger;
        parent::__construct($container);
    }

    public function registerMethod($className, $methodName)
    {
        $classNameShort = $this->fromCamelCase(str_replace('ApiService', '', substr($className, strrpos($className, '\\') + 1)));
        $this->methods[$classNameShort . '.' . $methodName] = [
            'service' => $className,
            'method' => $methodName,
        ];
    }

    /**
     * @param JsonRpcRequest $request
     * @param JsonRpcResponse $response
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \ReflectionException
     */
    public function handleRequest(JsonRpcRequest $request, JsonRpcResponse $response)
    {
        $stopWatch = new Stopwatch();
        $stopWatch->start('api');
        $response->setStopwatch($stopWatch);
        try {
            $response->setRequest($request);
            $request->parseRequest();
        } catch (JsonRpcException $e) {
            $response->setError($e->getMessage(), $e->getCode());
            $stopWatch->stop('api');
            return $response->getHttpResponse();
        }
        try {
            if (!$request->getMethod() || empty($this->methods[$request->getMethod()])) {
                throw new JsonRpcInvalidMethodException('Method not found');
            }
            $service = $this->get($this->methods[$request->getMethod()]['service']);
            $methodName = $this->methods[$request->getMethod()]['method'];

            $reader = new \ReflectionMethod($service, $methodName);
            $isAssociative = array_keys($request->getParams()) ? array_keys($request->getParams())[0] !== 0 : false;
            $requestParams = $request->getParams();
            $requestParamId = 0;
            $callingParams = [ ];
            foreach ($reader->getParameters() as $i => $param) {
                if ($param->getClass()) {
                    if ($param->getClass()->getName() == JsonRpcRequest::class || $param->getClass()->isSubclassOf(JsonRpcRequest::class)) {
                        $callingParams[$i] = $request;
                    } elseif ($param->getClass()->getName() == JsonRpcResponse::class || $param->getClass()->isSubclassOf(JsonRpcResponse::class)) {
                        $callingParams[$i] = $response;
                    } else {
                        throw new JsonRpcInvalidMethodException('Method definition is incorrect');
                    }
                } else {
                    if ($isAssociative) {
                        if (isset($requestParams[$param->getName()])) {
                            $callingParams[$i] = $requestParams[$param->getName()];
                        } elseif (!$param->isOptional()) {
                            throw new JsonRpcInvalidMethodException('Undefined parameter: ' . $param->getName());
                        }
                    } elseif (isset($requestParams[$requestParamId])) {
                        $callingParams[$i] = $requestParams[$requestParamId];
                        $requestParamId++;
                    } elseif (!$param->isOptional()) {
                        throw new JsonRpcInvalidMethodException('Not enough parameters');
                    }
                }
            }
            if ($requestParamId > 0 && isset($requestParams[$requestParamId])) {
                throw new JsonRpcInvalidMethodException('Too many parameters');
            }
            $result = call_user_func_array([ $service, $methodName ], $callingParams);
            $response->setResult($result);
        } catch (JsonRpcException $e) {
            $response->setError($e->getMessage(), $e->getCode());
        }
        $stopWatch->stop('api');
        return $response->getHttpResponse();
    }

    protected function fromCamelCase($input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }
}