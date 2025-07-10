<?php

namespace App\Config;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class BackupConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('backup');

        // @formatter:off
        $treeBuilder
            ->getRootNode()
            ->children()
                ->arrayNode('dump_scripts')
                    ->scalarPrototype()
                        ->cannotBeEmpty()
                    ->end()
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('ignore_pattern')
                    ->scalarPrototype()
                        ->cannotBeEmpty()
                    ->end()
                ->end()
                ->arrayNode('ignore_folder')
                    ->scalarPrototype()
                        ->cannotBeEmpty()
                    ->end()
                ->end()
                ->scalarNode('before_backup')
                    ->cannotBeEmpty()
                    ->defaultNull()
                ->end()
                ->scalarNode('during_backup')
                    ->cannotBeEmpty()
                    ->defaultNull()
                ->end()
                ->scalarNode('after_backup')
                    ->cannotBeEmpty()
                    ->defaultNull()
                ->end()
            ->end()
        ;
        // @formatter:on

        return $treeBuilder;
    }
}
