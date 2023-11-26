<?php

namespace Smalot\Cups\Model\Traits;

use Smalot\Cups\Model\JobInterface;
use Smalot\Cups\Model\PrinterInterface;

/**
 * Trait UriAware
 *
 * @package Smalot\Cups\Model\Traits
 */
trait UriAware
{

    /**
     * @var string
     */
    protected $uri;

    /**
     * @return null|string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     *
     * @return JobInterface|PrinterInterface
     */
    public function setUri(string $uri): self
    {
        $this->uri = $uri;

        return $this;
    }
}
