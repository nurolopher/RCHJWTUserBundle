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
 * AlreadyExistingUserException is thrown when a user is persisted with
 * an identifier that already exists in database.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class AlreadyExistingUserException extends UserException
{
    /**
     * Constructor.
     *
     * @param string          $message  The internal exception message
     * @param \Exception|null $previous The previous exception
     * @param int             $code     The internal exception code
     */
    public function __construct($message = 'An user with the same identifier already exists.', \Exception $previous = null, $code = 0)
    {
        parent::__construct(422, $message, $previous, $code);
    }
}
