<?php

/**
 * This file is part of the RCH package.
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
class Param
{
    /** @var string */
    public $name;

    /** @var array */
    protected $options;

    /** @var array */
    public $requirements = array();

    /** @var mixed */
    public $default = null;

    /** @var bool */
    public $nullable = false;

    /** @var bool */
    public $required = true;

    public $class = null;

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

        $this->create();
    }

    private function create()
    {
        $this->setRequirements();

        foreach ($this->options as $key => $option) {
            if (null === $option && null === $this->$option) {
                continue;
            }

            $this->$key = $option;
        }

        return $this;
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
            $requirements = array($requirements);
        }

        foreach ($requirements as $constraint) {
            $this->requirements[] = $constraint;
        }

        unset($this->options['requirements']);

        return $this;
    }
}
