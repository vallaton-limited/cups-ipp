<?php

namespace Smalot\Cups\Model;

/**
 * Interface PrinterInterface
 *
 * @package Smalot\Cups\Model
 */
interface PrinterInterface
{

    /**
     * @return null|string
     */
    public function getUri();

    /**
     * @param string $uri
     *
     * @return Printer
     */
    public function setUri(string $uri): Printer;

    /**
     * @return null|string
     */
    public function getName();

    /**
     * @param string $name
     *
     * @return Printer
     */
    public function setName(string $name): Printer;

    /**
     * @return array
     */
    public function getAttributes(): array;

    /**
     * @param array $attributes
     *
     * @return Printer
     */
    public function setAttributes(array $attributes): Printer;

    /**
     * @return string
     */
    public function getStatus(): string;

    /**
     * @param string $status
     *
     * @return Printer
     */
    public function setStatus(string $status): Printer;
}
