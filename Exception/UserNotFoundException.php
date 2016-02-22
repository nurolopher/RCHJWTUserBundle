<?php

/**
 * This file is part of the RCHJWTUserBundle package.
 *
 * Robin Chalas <robin.chalas@gmail.com>
 *
 * For more informations about license, please see the LICENSE
 * file distributed in this source code.
 */
namespace RCH\JWTUserBundle\Exception;

/**
 * UserNotFoundException is thrown when the fetched User doesn't exist.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class UserNotFoundException extends UserException
{
    /**
     * Constructor.
     *
     * @param string          $message  The internal exception message
     * @param \Exception|null $previous The previous exception
     * @param int             $code     The internal exception code
     */
    public function __construct($message = 'The given user cannot be found.', \Exception $previous = null, $code = 0)
    {
        parent::__construct($message, 404, $previous);
    }
}
