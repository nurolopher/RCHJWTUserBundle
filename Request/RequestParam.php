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

/**
 * Request parameter.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class RequestParam
{
    /** @var string */
    protected $name;

    /** @var array */
    protected $options;

    /** @var array */
    protected $requirements = array();

    /** @var mixed */
    protected $default = null;

    /** @var bool */
    protected $nullable = false;

    /** @var bool */
    protected $required = true;

    /**
     * Constructor.
     *
     * @param array $param
     * @param array $options
     */
    public function __construct($name, array $options)
    {
        $this->name = $name;
        $this->options = $options;

        $this->setRequirements()
            ->setRequired()
            ->setDefault()
            ->setNullable();
    }

    /**
     * Get parameter name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set requirements.
     */
    public function setRequirements()
    {
        if (!isset($this->options['requirements']) || $this->options['requirements'] === null) {
            return $this;
        }

        $requirements = $this->options['requirements'];

        if (!is_array($requirements)) {
            $requirements = [$requirements];
        }

        foreach ($requirements as $constraint) {
            $this->requirements[] = $constraint;
        }

        return $this;
    }

    /**
     * Get requirements option.
     *
     * @return array
     */
    public function getRequirements()
    {
        return $this->requirements;
    }

    /**
     * Set required option.
     */
    public function setRequired()
    {
        if (!isset($this->options['required']) || $this->options['required'] === null) {
            return $this;
        }

        $required = $this->options['required'];

        if (!is_bool($required)) {
            return $this;
        }

        $this->required = $required;
    }

    /**
     * Get required option.
     *
     * @return bool
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * Set nullable option.
     */
    public function setNullable()
    {
        if (!isset($this->options['nullable']) || $this->options['nullable'] === null) {
            return $this;
        }

        $nullable = $this->options['nullable'];

        if (!is_bool($nullable)) {
            return $this;
        }

        $this->nullable = $nullable;
    }

    /**
     * Get nullable option.
     *
     * @return bool
     */
    public function getNullable()
    {
        return $this->nullable;
    }

    /**
     * Set default value.
     */
    public function setDefault()
    {
        if (!isset($this->options['default']) || $this->options['default'] === null) {
            return $this;
        }

        $default = $this->options['default'];

        $this->default = $default;

        return $this;
    }

    /**
     * Get default value.
     *
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }
}
