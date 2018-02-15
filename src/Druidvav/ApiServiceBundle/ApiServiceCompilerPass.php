<?php
namespace Druidvav\ApiServiceBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class ApiServiceCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @throws \ReflectionException
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(ApiServiceContainer::class)) {
            return;
        }
        $definition = $container->findDefinition(ApiServiceContainer::class);
        $taggedServices = $container->findTaggedServiceIds('jsonrpc.api-service');
        foreach ($taggedServices as $id => $tags) {
            $reader = new \ReflectionClass($id);
            $methods = $reader->getMethods();
            foreach ($methods as $method) {
                if ($method->isPublic() && strpos($method->getDocComment(), '@ApiMethod') !== false) {
                    $definition->addMethodCall('registerMethod', [ $reader->getName(), $method->getName() ]);
                }
            }
        }
    }
}