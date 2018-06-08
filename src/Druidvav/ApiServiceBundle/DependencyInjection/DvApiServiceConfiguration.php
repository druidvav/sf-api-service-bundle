<?php
namespace Druidvav\ApiServiceBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class DvApiServiceConfiguration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root("dv_api_service");

        $rootNode->
            children()->
                scalarNode("logger")->defaultValue('@logger')->end()->
                scalarNode("request_class")->defaultValue('Druidvav\ApiServiceBundle\JsonRpcRequest')->end()->
                scalarNode("response_class")->defaultValue('Druidvav\ApiServiceBundle\JsonRpcResponse')->end()->
            end()
        ;

        return $treeBuilder;
    }
}
