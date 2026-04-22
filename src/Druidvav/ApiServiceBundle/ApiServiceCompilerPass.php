<?php

namespace Druidvav\ApiServiceBundle;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;

class ApiServiceCompilerPass implements CompilerPassInterface
{
    /**
     * @throws ReflectionException
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(ApiServiceContainer::class)) {
            return;
        }
        $definition = $container->findDefinition(ApiServiceContainer::class);
        $taggedServices = $container->findTaggedServiceIds('jsonrpc.api-service');

        // Restrict ApiServiceContainer to only tagged API services (can be private).
        $serviceMap = [];
        foreach ($taggedServices as $id => $tags) {
            $serviceMap[$id] = new Reference($id);
        }
        $locator = ServiceLocatorTagPass::register($container, $serviceMap);
        $definition->setArgument(0, $locator);

        foreach ($taggedServices as $id => $tags) {
            $reader = new ReflectionClass($id);
            $methods = $reader->getMethods();
            foreach ($methods as $method) {
                if (!$method->isPublic()) {
                    continue;
                }
                if ($method->isConstructor()) {
                    continue;
                }
                if ($method->getDeclaringClass()->getName() !== $reader->getName()) {
                    continue;
                }
                $className = $reader->getName();
                $methodName = $method->getName();
                $classNameShort = $this->fromCamelCase(str_replace('ApiService', '', substr($className, strrpos($className, '\\') + 1)));
                $apiMethodName = $classNameShort.'.'.$methodName;
                $methodParams = [];
                foreach ($method->getParameters() as $i => $param) {
                    $paramClassName = $this->getParameterClassName($param);
                    if (null !== $paramClassName) {
                        if (is_a($paramClassName, JsonRpcRequest::class, true)) {
                            $methodParams[$i] = ['className' => JsonRpcRequest::class];
                        } elseif (is_a($paramClassName, JsonRpcResponse::class, true)) {
                            $methodParams[$i] = ['className' => JsonRpcResponse::class];
                        } elseif (Request::class === $paramClassName) {
                            $methodParams[$i] = ['className' => Request::class];
                        } else {
                            $methodParams[$i] = ['className' => $paramClassName];
                        }
                    } else {
                        $methodParams[$i] = [
                            'type' => ($param->getType() instanceof ReflectionNamedType) ? $param->getType()->getName() : null,
                            'name' => $param->getName(),
                            'nullable' => $param->allowsNull(),
                            'optional' => $param->isOptional(),
                        ];
                    }
                }
                $definition->addMethodCall('registerMethod', [$apiMethodName, $className, $methodName, $methodParams]);
            }
        }
    }

    protected function fromCamelCase($input): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match === strtoupper($match) ? strtolower($match) : lcfirst($match);
        }

        return implode('_', $ret);
    }

    private function getParameterClassName(ReflectionParameter $param): ?string
    {
        $type = $param->getType();
        if ($type instanceof ReflectionNamedType) {
            if ($type->isBuiltin()) {
                return null;
            }

            return $type->getName();
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $namedType) {
                if (!$namedType instanceof ReflectionNamedType) {
                    continue;
                }
                if ($namedType->isBuiltin()) {
                    continue;
                }

                return $namedType->getName();
            }
        }

        return null;
    }
}
