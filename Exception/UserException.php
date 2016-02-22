<?php

/**
 * This file is part of the RCH/JWTUserBundle.
 *
 * Robin Chalas <robin.chalas@gmail.com>
 *
 * For more informations about license, please see the LICENSE
 * file distributed in this source code.
 */
namespace RCH\JWTUserBundle\Exception;

/**
  * UserAlreadyExistsException is thrown when a user is persisted with
  * an identifier that already exists in database.
  *
  * @author Robin Chalas <robin.chalas@gmail.com>
  */
 class UserException extends \RuntimeException
 {
     private $statusCode;
     private $headers;

     /**
      * Constructor.
      *
      * @param int             $statusCode
      * @param string|null     $message
      * @param \Exception|null $previous
      */
     public function __construct($statusCode, $message = null, \Exception $previous = null, $code = 0)
     {
         $this->statusCode = $statusCode;

         parent::__construct($message, $code, $previous);
     }

     /**
      * Get statusCode.
      *
      * @return int
      */
     public function getStatusCode()
     {
         return $this->statusCode;
     }
 }
