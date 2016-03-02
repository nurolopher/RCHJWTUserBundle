<?php

/**
 * This file is part of the RCH package.
 *
 * Robin Chalas <robin.chalas@gmail.com>
 *
 * For more informations about license, please see the LICENSE
 * file distributed in this source code.
 */
namespace RCH\JWTUserBundle\Exception;

/**
 * NotFoundUserException is thrown the fetched User doesn't exist.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class NotFoundUserException extends UserException
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
        parent::__construct(404, $message, $previous, array(), $code);
    }
}
