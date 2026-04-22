<?php

namespace Druidvav\ApiServiceBundle\DependencyInjection;

use Druidvav\ApiServiceBundle\ApiController;
use Druidvav\ApiServiceBundle\ApiServiceContainer;
use Exception;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class DvApiServiceExtension extends Extension
{
    /**
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $this->loadConfiguration($configs, $container);
    }

    /**
     * Loads the configuration in, with any defaults
     *
     * @throws Exception
     */
    protected function loadConfiguration(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new DvApiServiceConfiguration(), $configs);

        $optionDef = new Definition(ApiServiceContainer::class);
        $optionDef->addArgument(new Reference('service_container'));
        $optionDef->addArgument(new Reference(str_replace('@', '', $config['logger'])));
        $optionDef->addArgument(new Reference('event_dispatcher'));
        $optionDef->addMethodCall('setClassNames', [$config['request_class'], $config['response_class']]);
        $optionDef->setPublic(true);
        foreach ($config['aliases'] as $alias) {
            $optionDef->addMethodCall('registerMethod', [$alias['alias'], $alias['class'], $alias['method']]);
        }
        $container->setDefinition(ApiServiceContainer::class, $optionDef);

        $optionDef = new Definition(ApiController::class);
        $optionDef->setPublic(true);
        $optionDef->setAutoconfigured(true);
        $optionDef->setAutowired(true);
        $container->setDefinition(ApiController::class, $optionDef);
    }
}
