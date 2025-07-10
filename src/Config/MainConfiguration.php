<?php

namespace App\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class MainConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('backup');

        // @formatter:off
        $treeBuilder
            ->getRootNode()
                ->requiresAtLeastOneElement()
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('from')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->validate()
                                ->ifTrue(fn ($value) => !is_dir($value))
                                ->thenInvalid('From must be a directory')
                            ->end()
                        ->end()
                        ->scalarNode('to')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('configName')
                            ->cannotBeEmpty()
                            ->defaultValue('backup.yml')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        // @formatter:on

        return $treeBuilder;
    }
}
