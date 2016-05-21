<?php

/*
 * This file is part of the RCH package.
 *
 * (c) Robin Chalas <https://github.com/chalasr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
