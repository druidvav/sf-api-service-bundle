<?php
namespace Druidvav\ApiServiceBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class DvApiServiceConfiguration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder("dv_api_service");
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->
            children()->
                scalarNode("logger")->defaultValue('@logger')->end()->
                scalarNode("request_class")->defaultValue('Druidvav\ApiServiceBundle\JsonRpcRequest')->end()->
                scalarNode("response_class")->defaultValue('Druidvav\ApiServiceBundle\JsonRpcResponse')->end()->
                arrayNode('aliases')->
                    arrayPrototype()->
                        children()->
                            scalarNode('alias')->cannotBeEmpty()->end()->
                            scalarNode('class')->cannotBeEmpty()->end()->
                            scalarNode('method')->cannotBeEmpty()->end()->
                        end()->
                    end()->
                end()->
            end()
        ;

        return $treeBuilder;
    }
}
