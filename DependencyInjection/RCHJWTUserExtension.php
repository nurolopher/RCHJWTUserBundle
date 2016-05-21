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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Pre-configures dependencies required by the bundle.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class RCHJWTUserExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->processConfiguration(new Configuration(), $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $kernelRootDir = $container->getParameter('kernel.root_dir');

        $configurations = [
            'lexik_jwt_authentication' => [
                'private_key_path' => $kernelRootDir.'/var/jwt/private.pem',
                'public_key_path'  => $kernelRootDir.'/var/jwt/public.pem',
                'pass_phrase'      => 'foobar',
            ],
        ];

        foreach ($configurations as $extension => $config) {
            $container->prependExtensionConfig($extension, $configurations[$extension]);
        }

        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'rch_jwt_user';
    }
}
