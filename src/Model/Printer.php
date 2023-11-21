<?php

namespace Smalot\Cups\Model;

/**
 * Class Printer
 *
 * @package Smalot\Cups\Model
 */
class Printer implements PrinterInterface
{

    use Traits\AttributeAware;
    use Traits\UriAware;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $status;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Printer
     */
    public function setName(string $name): Printer
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return Printer
     */
    public function setStatus(string $status): Printer
    {
        $this->status = $status;

        return $this;
    }
}
