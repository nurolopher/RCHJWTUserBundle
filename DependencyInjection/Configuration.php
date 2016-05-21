<?php

/*
 * This file is part of the RCH package.
 *
 * (c) Robin Chalas <https://github.com/chalasr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RCH\JWTUserBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Loads default bundle configuration.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('rch_jwt_user');
        $rootNode
            ->children()
                ->arrayNode('exceptions')
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultValue(true)
                        ->end()
                        ->enumNode('format')
                            ->values(['json', 'xml'])
                            ->defaultValue('json')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
