<?php

namespace RCH\JWTUserBundle;

use RCH\JWTUserBundle\DependencyInjection\RCHJWTUserExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * RCH\JWTUserBundle.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class RCHJWTUserBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new RCHJWTUserExtension();
    }
}
