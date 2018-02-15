<?php
namespace Druidvav\ApiServiceBundle\DependencyInjection;

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
     * @throws \Exception
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
     * @throws \Exception
     */
    protected function loadConfiguration(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new DvApiServiceConfiguration(), $configs);

        $optionDef = new Definition('Druidvav\ApiServiceBundle\ApiServiceContainer');
        $optionDef->addArgument(new Reference('service_container'));
        $optionDef->addArgument(new Reference(str_replace('@', '', $config['logger'])));
        $optionDef->setPublic(true);
        $container->setDefinition('Druidvav\ApiServiceBundle\ApiServiceContainer', $optionDef);
    }
}
