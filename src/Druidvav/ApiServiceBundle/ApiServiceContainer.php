<?php
namespace Druidvav\ApiServiceBundle;

use Druidvav\ApiServiceBundle\Event\ApiRequestEvent;
use Druidvav\ApiServiceBundle\Event\ApiResponseEvent;
use Druidvav\ApiServiceBundle\Exception\JsonRpcExceptionInterface;
use Druidvav\EssentialsBundle\Service\ContainerService;
use Druidvav\ApiServiceBundle\Exception\JsonRpcInvalidMethodException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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

    public function registerMethod($className, $methodName)
    {
        $classNameShort = $this->fromCamelCase(str_replace('ApiService', '', substr($className, strrpos($className, '\\') + 1)));
        $this->methods[$classNameShort . '.' . $methodName] = [
            'service' => $className,
            'method' => $methodName,
        ];
    }

    public function setClassNames($requestClass, $responseClass)
    {
        $this->requestClass = $requestClass;
        $this->responseClass = $responseClass;
    }

    /**
     * @param Request $httpRequest
     * @return JsonResponse
     * @throws \ReflectionException
     */
    public function handleRequest(Request $httpRequest)
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
            $service = $this->get($this->methods[$request->getMethod()]['service']);
            $methodName = $this->methods[$request->getMethod()]['method'];

            $requestParams = $request->getParams();
            $requestParamId = 0;
            $callingParams = [ ];
            $reader = new \ReflectionMethod($service, $methodName);
            foreach ($reader->getParameters() as $i => $param) {
                if ($param->getClass()) {
                    if ($param->getClass()->getName() == JsonRpcRequest::class || $param->getClass()->isSubclassOf(JsonRpcRequest::class)) {
                        $callingParams[$i] = $request;
                    } elseif ($param->getClass()->getName() == JsonRpcResponse::class || $param->getClass()->isSubclassOf(JsonRpcResponse::class)) {
                        $callingParams[$i] = $response;
                    } elseif ($request->getObject($param->getClass()->getName())) {
                        $callingParams[$i] = $request->getObject($param->getClass()->getName());
                    } else {
                        throw new JsonRpcInvalidMethodException('Method definition is incorrect');
                    }
                } else {
                    $key = $request->isAssociative() ? $param->getName() : $requestParamId;
                    if (array_key_exists($key, $requestParams)) {
                        $callingParams[$i] = $requestParams[$key];
                        $requestParamId++;
                    } elseif (!$param->isOptional()) {
                        throw new JsonRpcInvalidMethodException('Undefined parameter "' . $key . '"');
                    }
                }
            }
            if ($requestParamId > 0 && isset($requestParams[$requestParamId])) {
                throw new JsonRpcInvalidMethodException('Too many parameters');
            }
            $response->setResult(call_user_func_array([ $service, $methodName ], $callingParams));
        } catch (JsonRpcExceptionInterface $e) {
            $response->setError($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            $response->setError($e->getMessage(), -32603);
        }
        $this->dispatcher->dispatch(ApiResponseEvent::NAME, new ApiResponseEvent($response));
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