<?php

namespace Smalot\Cups\Model\Traits;

use Smalot\Cups\Model\JobInterface;
use Smalot\Cups\Model\PrinterInterface;

/**
 * Trait AttributeAware
 *
 * @package Smalot\Cups\Model\Traits
 */
trait AttributeAware
{

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param string $name
     * @param mixed  $values
     */
    public function setAttribute(string $name, $values)
    {
        if (!is_array($values)) {
            $values = [$values];
        }

        $this->attributes[$name] = $values;
    }

    /**
     * @param array $attributes
     *
     * @return JobInterface|PrinterInterface
     */
    public function setAttributes(array $attributes): self
    {
        foreach ($attributes as $name => $values) {
            $this->setAttribute($name, $values);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function addAttribute(string $name, $value)
    {
        $this->attributes[$name][] = $value;
    }

    /**
     * @param string $name
     */
    public function removeAttribute(string $name)
    {
        unset($this->attributes[$name]);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasAttribute(string $name): bool
    {
        return isset($this->attributes[$name]);
    }
}
