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
 * InvalidUserException is thrown when an User property/identifer is not valid.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class InvalidPropertyUserException extends UserException
{
    /**
     * Constructor.
     *
     * @param string          $message  The internal exception message
     * @param \Exception|null $previous The previous exception
     * @param int             $code     The internal exception code
     */
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct(422, $message, $previous, $code);
    }
}
