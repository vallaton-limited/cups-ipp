<?php

namespace Smalot\Cups\Manager\Traits;

use Smalot\Cups\CupsException;

/**
 * Trait UsernameAware
 *
 * @package Smalot\Cups\Manager\Traits
 */
trait UsernameAware
{

    /**
     * @var string
     */
    protected $username;

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return UsernameAware
     */
    public function setUsername(string $username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     * @throws CupsException
     */
    protected function buildUsername(): string
    {
        $meta_username = '';

        if ($this->username) {
            $meta_username = chr(0x42) // keyword type || value-tag
              .chr(0x00).chr(0x14) // name-length
              .'requesting-user-name'
              .$this->builder->formatStringLength($this->username) // value-length
              .$this->username;
        }

        return $meta_username;
    }
}
