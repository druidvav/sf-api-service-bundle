<?php
namespace Druidvav\ApiServiceBundle;

use ReflectionClass;
use ReflectionException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\HttpFoundation\Request;

class ApiServiceCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @throws ReflectionException
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(ApiServiceContainer::class)) {
            return;
        }
        $definition = $container->findDefinition(ApiServiceContainer::class);
        $taggedServices = $container->findTaggedServiceIds('jsonrpc.api-service');
        foreach ($taggedServices as $id => $tags) {
            $reader = new ReflectionClass($id);
            $methods = $reader->getMethods();
            foreach ($methods as $method) {
                if (!$method->isPublic() || $method->isConstructor() || $method->getDeclaringClass()->getName() !== $reader->getName()) {
                    continue;
                }
                $className = $reader->getName();
                $methodName = $method->getName();
                $classNameShort = $this->fromCamelCase(str_replace('ApiService', '', substr($className, strrpos($className, '\\') + 1)));
                $apiMethodName = $classNameShort . '.' . $methodName;
                $methodParams = [ ];
                foreach ($method->getParameters() as $i => $param) {
                    if ($param->getClass()) {
                        if ($param->getClass()->getName() == JsonRpcRequest::class || $param->getClass()->isSubclassOf(JsonRpcRequest::class)) {
                            $methodParams[$i] = [ 'className' => JsonRpcRequest::class ];
                        } elseif ($param->getClass()->getName() == JsonRpcResponse::class || $param->getClass()->isSubclassOf(JsonRpcResponse::class)) {
                            $methodParams[$i] = [ 'className' => JsonRpcResponse::class ];
                        } elseif ($param->getClass()->getName() == Request::class) {
                            $methodParams[$i] = [ 'className' => Request::class ];
                        } else {
                            $methodParams[$i] = [ 'className' => $param->getClass()->getName() ];
                        }
                    } else {
                        $methodParams[$i] = [
                            'type' => $param->getType() ? $param->getType()->getName() : null,
                            'name' => $param->getName(),
                            'nullable' => $param->allowsNull(),
                            'optional' => $param->isOptional()
                        ];
                    }
                }
                $definition->addMethodCall('registerMethod', [ $apiMethodName, $className, $methodName, $methodParams ]);
            }
        }
    }

    protected function fromCamelCase($input): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }
}