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
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $kernelRootDir = $container->getParameter('kernel.root_dir');
        $configs = $this->processConfiguration(new Configuration(), $container->getExtensionConfig($this->getAlias()));
        $fosUserProviderId = 'fos_user.user_provider.username_email';

        $container->setParameter('rch_jwt_user.passphrase', $configs['passphrase']);
        $container->setParameter('rch_jwt_user.user_class', $configs['user_class']);
        $container->setParameter('rch_jwt_user.user_identity_field', $configs['user_identity_field']);
        $container->setParameter('rch_jwt_user.user_provider', $fosUserProviderId);

        $configurations = [
            'fos_user' => [
                'user_class'    => $configs['user_class'],
                'firewall_name' => 'main',
                'db_driver'     => 'orm',
            ],
            'lexik_jwt_authentication' => [
                'private_key_path'    => $kernelRootDir.'/../var/jwt/private.pem',
                'public_key_path'     => $kernelRootDir.'/../var/jwt/public.pem',
                'pass_phrase'         => $configs['passphrase'],
                'user_identity_field' => $configs['user_identity_field'],
            ],
            'gesdinet_jwt_refresh_token' => [
                'ttl'           => 86400,
                'user_provider' => $fosUserProviderId,
            ],
        ];

        foreach ($configurations as $extension => $config) {
            $container->prependExtensionConfig($extension, $configurations[$extension]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'rch_jwt_user';
    }
}
