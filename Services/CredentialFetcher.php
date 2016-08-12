<?php

/*
 * This file is part of the RCH package.
 *
 * (c) Robin Chalas <https://github.com/chalasr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace RCH\JWTUserBundle\Services;

use RCH\JWTUserBundle\Exception\BadRequestUserException;
use RCH\JWTUserBundle\Request\Credential;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Fetches params in the body of the current Request.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class CredentialFetcher
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var array */
    protected $methodRequirements;

    /**
     * Constructor.
     *
     * @param RequestStack       $request
     * @param ValidatorInterface $validator
     */
    public function __construct(RequestStack $requestStack, ValidatorInterface $validator)
    {
        $this->requestStack = $requestStack;
        $this->validator = $validator;
    }

    /**
     * Set requirements for the whole request.
     *
     * @param array $methodRequirements A list of request params with their validation rules
     */
    public function create(array $methodRequirements)
    {
        $this->methodRequirements = $methodRequirements;

        return $this;
    }

    /**
     * Fetches all required parameters from the current Request body.
     *
     * @return array
     */
    public function all()
    {
        $params = [];

        foreach ($this->methodRequirements as $key => $config) {
            $params[$key] = $this->get($key);
        }

        return $params;
    }

    /**
     * Fetches a given parameter from the current Request body.
     *
     * @param string $name The parameter key
     *
     * @return mixed The parameter value
     */
    public function get($name)
    {
        if (!$paramConfig = $this->methodRequirements[$name]) {
            return;
        }

        $config = new Credential($name, $paramConfig);

        if (true === $config->required && !($this->getRequest()->request->has($name))) {
            throw new BadRequestUserException(
                $this->formatError($name, false, 'The parameter must be set')
            );
        }

        $param = $this->getRequest()->request->get($name);

        if (false === $config->nullable && !$param) {
            throw new BadRequestUserException(
                $this->formatError($name, $param, 'The parameter cannot be null')
            );
        }

        if (($config->default && $param === $config->default
        || ($param === null && true === $config->nullable)
        || (null === $config->requirements))) {
            return $param;
        }

        $this->validateParam($config, $param);

        return $param;
    }

    /**
     * Handle requirements validation.
     *
     * @param Param $param
     *
     * @throws BadRequestUserException If the param is not valid
     *
     * @return Param
     */
    private function validateParam(Credential $config, $param)
    {
        $name = $config->name;

        if (null === $requirements = $config->requirements) {
            return;
        }

        foreach ($requirements as $constraint) {
            if (is_scalar($constraint)) {
                $constraint = new Regex([
                    'pattern' => '#^'.$constraint.'$#xsu',
                    'message' => sprintf('Does not match "%s"', $constraint),
                ]);
            } elseif (is_array($constraint)) {
                continue;
            }

            if ($constraint instanceof UniqueEntity) {
                $object = $config->class;
                $accessor = PropertyAccess::createPropertyAccessor();

                if ($accessor->isWritable($object, $name)) {
                    $accessor->setValue($object, $name, $param);
                } else {
                    throw new BadRequestUserException(
                        sprintf('The @UniqueEntity constraint must be used on an existing property. The class "%s" does not have a property "%s"', get_class($object), $name)
                    );
                }

                $errors = $this->validator->validate($object, $constraint);
            } else {
                $errors = $this->validator->validate($param, $constraint);
            }

            if (0 !== count($errors)) {
                $error = $errors[0];
                throw new BadRequestUserException(
                    $this->formatError($name, $error->getInvalidValue(), $error->getMessage())
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    private function formatError($key, $invalidValue, $errorMessage)
    {
        return sprintf(
            false === $invalidValue
            ? 'Request parameter %s must be set'
            : "Request parameter %s value '%s' violated a requirement (%s)",
            $key,
            $invalidValue,
            $errorMessage
        );
    }

    /**
     * @throws \RuntimeException
     *
     * @return Request
     */
    private function getRequest()
    {
        if ($this->requestStack instanceof Request) {
            $request = $this->requestStack;
        } elseif ($this->requestStack instanceof RequestStack) {
            $request = $this->requestStack->getCurrentRequest();
        }

        if ($request !== null) {
            return $request;
        }

        throw new \RuntimeException('There is no current request.');
    }
}
