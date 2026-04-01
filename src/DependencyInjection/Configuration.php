<?php

namespace Survos\LocationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder $builder The tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('survos_location');

        $rootNode = $builder->getRootNode();
        $rootNode->children()
            ->scalarNode('db')
//                ->isRequired()
                ->defaultValue('location.db')
            ->end()

            ->end();

        return $builder;
    }
}
