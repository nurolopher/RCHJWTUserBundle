<?php

/**
 * This file is part of the RCHJWTUserBundle package.
 *
 * Robin Chalas <robin.chalas@gmail.com>
 *
 * For more informations about license, please see the LICENSE
 * file distributed in this source code.
 */
namespace RCH\JWTUserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Entity\User as BaseUser;
use JMS\Serializer\Annotation as JMS;
use RCH\JWTUserBundle\Util\TimestampableTrait as Timestampable;

/**
 * User.
 *
 * @ORM\MappedSuperClass
 * @JMS\ExclusionPolicy("all")
 */
class User extends BaseUser
{
    use Timestampable;

    /**
     * @var string
     *
     * @JMS\Expose
     */
    protected $email;

    /**
     * Returns a string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getUsername() ?: 'Anonymous';
    }

    /**
     * Set facebookId .
     *
     * @param int $facebookId
     *
     * @return User
     */
    public function setFacebookId($facebookId)
    {
        $this->facebookId = $facebookId;

        return $this;
    }
}
