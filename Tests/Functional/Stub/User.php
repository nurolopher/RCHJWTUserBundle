<?php

/*
 * This file is part of the RCH package.
 *
 * (c) Robin Chalas <https://github.com/chalasr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RCH\JWTUserBundle\Tests\Functional\Stub;

use Doctrine\ORM\Mapping as ORM;
use RCH\JWTUserBundle\Entity\User as BaseUser;

/**
 */
class User extends BaseUser
{
    /**
     * @var int
     *
     */
    protected $id;

    public function __construct()
    {
        parent::__construct();
    }
}
