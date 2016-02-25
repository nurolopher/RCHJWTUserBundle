<?php

/**
 * This file is part of the RCHJWTUserBundle package.
 *
 * Robin Chalas <robin.chalas@gmail.com>
 *
 * For more informations about license, please see the LICENSE
 * file distributed in this source code.
 */
namespace RCH\JWTUserBundle\Request;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorInterface as LegacyValidatorInterface;

/**
 * Fetchs params in the body of a Request instance.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class RequestParamFetcher
{
    protected $requestStack;
    protected $validator;
    protected $methodRequirements;
    protected $options;
    protected $container;

    /**
     * Constructor.
     *
     * @param Request|RequestStack                        $request
     * @param ValidatorInterface|LegacyValidatorInterface $validator
     * @param array                                       $methodRequirements
     */
    public function __construct($requestStack, $validator)
    {
        if (!($requestStack instanceof RequestStack)) {
            throw new \InvalidArgumentException(sprintf('Argument 1 of %s constructor must be either an instance of Symfony\Component\HttpFoundation\Request or Symfony\Component\HttpFoundation\RequestStack.', ));
        }

        $this->requestStack = $requestStack;
        $this->validator = $validator;
    }

    /**
     * Set requirements for the whole request.
     *
     * @param array $methodRequirements A list of request params with their validation rules
     */
    public function setRequirements($methodRequirements)
    {
        $this->methodRequirements = $methodRequirements;
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface $container A ContainerInterface instance
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Valid and get a parameter.
     *
     * @param string $name The parameter key
     *
     * @return mixed The parameter value
     */
    public function get($name)
    {
        $param = $this->getParamValue($name);

        if (!$paramConfig = $this->methodRequirements[$name]) {
            return;
        }

        $config = new RequestParam($name, $paramConfig);
        $requirements = $config->getRequirements();

        if (true === $config->getRequired() && !$this->isParameterSet($name)) {
            throw new BadRequestHttpException($this->formatError($name, $param, 'required'));
        }

        $requirements = $config->getRequirements();

        if (null === $requirements
        || ($param === $config->getDefault() && null !== $default)
        || ($param === null && true === $config->getNullable())) {
            return $param;
        }

        $this->handleRequirements($config, $param);

        return $param;
    }

    /**
     * Handle requirements validation.
     *
     * @param RequestParam $param
     *
     * @throws BadRequestHttpException If the param is not valid
     *
     * @return RequestParam
     */
    private function handleRequirements(RequestParam $config, $param)
    {
        if (null === $requirements = $config->getRequirements()) {
            return;
        }

        foreach ($requirements as $constraint) {
            if (is_scalar($constraint)) {
                $constraint = new Regex(array(
                    'pattern' => '#^'.$constraint.'$#xsu',
                    'message' => $this->formatError($config->getName(), $param, $constraint),
                ));
            } elseif (is_array($constraint)) {
                continue; // TODO throw an exception OR parse it
            }

            if ($this->validator instanceof ValidatorInterface) {
                $errors = $this->validator->validate($param, $constraint);
            } else {
                $errors = $this->validator->validateValue($param, $constraint);
            }

            if (0 !== count($errors)) {
                $error = $errors[0];
                throw new BadRequestHttpException(
                    $this->formatError($config->getName(), $error->getInvalidValue(), $error->getMessage())
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
            "Request parameter %s value '%s' violated a constraint (%s)",
            $key,
            $invalidValue,
            $errorMessage
        );
    }

    /**
     * Is parameter set in request ParameterBag.
     *
     * @param string $name
     *
     * @return bool
     */
    private function isParameterSet($name)
    {
        $requestParameters = $this->getRequest()->request->all();

        return isset($requestParameters[$name]);
    }

    /**
     * @throws \RuntimeException
     *
     * @return Request
     */
    private function getRequest()
    {
        if ($this->requestStack instanceof Request) {
            return $this->requestStack;
        } elseif ($this->requestStack instanceof RequestStack) {
            $request = $this->requestStack->getCurrentRequest();
        } else {
            $request = $this->container->get('request');
        }

        if ($request !== null) {
            return $request;
        }

        throw new \RuntimeException('There is no current request.');
    }

    /**
     * Get a parameter from Request body.
     *
     * @param string $name The parameter name
     *
     * @return array|null
     */
    private function getParamValue($name)
    {
        return $this->getRequest()->request->get($name);
    }
}
