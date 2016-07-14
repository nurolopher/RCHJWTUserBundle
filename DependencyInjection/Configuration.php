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
                ->scalarNode('user_class')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->validate()
                    ->ifString()
                        ->then(function ($class) {
                            if (!class_exists('\\'.$class)) {
                                throw new InvalidConfigurationException(sprintf('"rch_jwt_user.user_class" option must be a valid class, "%s" given', $class));
                            }

                            return $class;
                        })
                    ->end()
                ->end()
                ->scalarNode('user_identity_field')
                    ->cannotBeEmpty()
                    ->defaultValue('username')
                ->end()
                ->scalarNode('passphrase')
                    ->cannotBeEmpty()
                    ->defaultValue('')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
