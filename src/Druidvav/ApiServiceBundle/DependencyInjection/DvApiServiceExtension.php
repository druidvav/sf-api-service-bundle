<?php
namespace Druidvav\ApiServiceBundle\DependencyInjection;

use Exception;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DvApiServiceExtension extends Extension
{
    /**
     * @param  array $configs
     * @param  ContainerBuilder $container
     * @return void
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->loadConfiguration($configs, $container);
    }

    /**
     * Loads the configuration in, with any defaults
     *
     * @param array $configs
     * @param ContainerBuilder $container
     * @throws Exception
     */
    protected function loadConfiguration(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new DvApiServiceConfiguration(), $configs);

        $optionDef = new Definition('Druidvav\ApiServiceBundle\ApiServiceContainer');
        $optionDef->addArgument(new Reference('service_container'));
        $optionDef->addArgument(new Reference(str_replace('@', '', $config['logger'])));
        $optionDef->addArgument(new Reference('event_dispatcher'));
        $optionDef->addMethodCall('setClassNames', [ $config['request_class'], $config['response_class'] ]);
        $optionDef->setPublic(true);
        foreach ($config['aliases'] as $alias) {
            $optionDef->addMethodCall('registerMethod', [ $alias['alias'], $alias['class'], $alias['method'] ]);
        }
        $container->setDefinition('Druidvav\ApiServiceBundle\ApiServiceContainer', $optionDef);

        $optionDef = new Definition('Druidvav\ApiServiceBundle\ApiController');
        $optionDef->setPublic(true);
        $optionDef->setAutoconfigured(true);
        $optionDef->setAutowired(true);
        $container->setDefinition('Druidvav\ApiServiceBundle\ApiController', $optionDef);
    }
}
