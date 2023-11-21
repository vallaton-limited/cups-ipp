<?php

namespace Smalot\Cups\Manager\Traits;

use Smalot\Cups\CupsException;

/**
 * Trait CharsetAware
 *
 * @package Smalot\Cups\Manager\Traits
 */
trait CharsetAware
{

    /**
     * @var string
     */
    protected $charset;

    /**
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * @param string $charset
     *
     * @return CharsetAware
     */
    public function setCharset(string $charset)
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * @return string
     * @throws CupsException
     */
    protected function buildCharset(): string
    {
        // Charset
        $charset = strtolower($this->getCharset());
        return chr(0x47) // charset type | value-tag
          .chr(0x00).chr(0x12) // name-length
          .'attributes-charset' // attributes-charset | name
          .$this->builder->formatStringLength($charset) // value-length
          .$charset; // value
    }
}
