<?php

/**
 * This file is part of the RCHJWTUserBundle package.
 *
 * Robin Chalas <robin.chalas@gmail.com>
 *
 * For more informations about license, please see the LICENSE
 * file distributed in this source code.
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
        $bundles = $container->getParameter('kernel.bundles');
        $kernelRootDir = $container->getParameter('kernel.root_dir');

        $configurations = array(
            'lexik_jwt_authentication' => array(
                'private_key_path' => $kernelRootDir.'/var/jwt/private.pem',
                'public_key_path'  => $kernelRootDir.'/var/jwt/public.pem',
                'pass_phrase'      => 'foobar',
            ),
            'fos_rest' => array(
                'exception' => array(
                    'enabled' => true,
                    'codes'   => array(
                        'RCH\JWTUserBundle\Exception\UserAlreadyExistsException' => 422,
                        'RCH\JWTUserBundle\Exception\UserNotFoundException'      => 404,
                    ),
                ),
            ),
        );

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
