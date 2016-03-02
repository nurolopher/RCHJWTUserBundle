<?php

/**
 * This file is part of the RCHJWTUserBundle package.
 *
 * Robin Chalas <robin.chalas@gmail.com>
 *
 * For more informations about license, please see the LICENSE
 * file distributed in this source code.
 */
namespace RCH\JWTUserBundle\Service;

use RCH\JWTUserBundle\Request\Param;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorInterface as LegacyValidatorInterface;

/**
 * Fetchs params in the body of a Request instance.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class CredentialFetcher
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
    public function __construct($requestStack = null, $validator = null)
    {
        if (!($requestStack instanceof RequestStack) && !($requestStack instanceof Request)) {
            throw new \InvalidArgumentException(
                sprintf('Argument 1 of %s constructor must be either an instance of Symfony\Component\HttpFoundation\Request or Symfony\Component\HttpFoundation\RequestStack.', get_class($this))
            );
        }

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
     * Fetches all required parameters from the current Request body.
     *
     * @return array
     */
    public function all()
    {
        $params = array();

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
        $param = $this->getRequest()->request->get($name);

        if (!$paramConfig = $this->methodRequirements[$name]) {
            return;
        }

        $config = new Param($name, $paramConfig);

        if (true === $config->required && !($this->isParameterSet($name))) {
            throw new BadRequestHttpException(
                $this->formatError($name, $param, 'The parameter must be set')
            );
        }

        if (false === $config->nullable && !$param) {
            throw new BadRequestHttpException(
                $this->formatError($name, $param, 'The parameter cannot be null')
            );
        }

        if (($config->default && $param === $config->default)
        || ($param === null && true === $config->nullable)
        || (null === $config->requirements)) {
            return $param;
        }

        $this->handleRequirements($config, $param);

        return $param;
    }

    /**
     * Handle requirements validation.
     *
     * @param Param $param
     *
     * @throws BadRequestHttpException If the param is not valid
     *
     * @return Param
     */
    private function handleRequirements(Param $config, $param)
    {
        $name = $config->name;

        if (null === $requirements = $config->requirements) {
            return;
        }

        foreach ($requirements as $constraint) {
            if (is_scalar($constraint)) {
                $constraint = new Regex(array(
                    'pattern' => '#^'.$constraint.'$#xsu',
                    'message' => sprintf('Does not match "%s"', $constraint),
                ));
            } elseif (is_array($constraint)) {
                continue;
            }

            if (!($this->validator instanceof ValidatorInterface)) {
                $errors = $this->validator->validateValue($param, $constraint);
            } else {
                if ($constraint instanceof UniqueEntity) {
                    $object = $config->class;
                    $accessor = PropertyAccess::createPropertyAccessor();

                    if ($accessor->isWritable($object, $name)) {
                        $accessor->setValue($object, $name, $param);
                    } else {
                        throw new BadRequestHttpException(
                            sprintf('The @UniqueEntity constraint must be used on an existing property. The class "%s" does not have a property "%s"', get_class($object), $name)
                        );
                    }

                    $errors = $this->validator->validate($object, $constraint);
                } else {
                    $errors = $this->validator->validate($param, $constraint);
                }
            }

            if (0 !== count($errors)) {
                $error = $errors[0];
                throw new BadRequestHttpException(
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
            "Request parameter %s value '%s' violated a requirement (%s)",
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
