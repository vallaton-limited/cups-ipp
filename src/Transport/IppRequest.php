<?php

namespace Smalot\Cups\Transport;

use Smalot\Cups\CupsException;
use Smalot\Cups\Model\Operations;

class IppRequest
{
    /**
     * @var string[]
     */
    private $strings = [];

    public function __construct(string $version, int $operation_id)
    {
        $this->strings[] = $version;
        $this->strings[] = Operations::getOperationID($operation_id);
    }

    /**
     * Add an attribute
     *
     * @param string $string
     *
     * @return $this
     */
    public function addAddAttribute(string $string): IppRequest
    {
        $this->strings[] = $string;

        return $this;
    }

    /**
     * Add an attribute tag
     *
     * @return $this
     */
    public function addAddAttributeTag(int $tag_id): IppRequest
    {
        $this->strings[] = chr($tag_id);

        return $this;
    }

    /**
     * Get the request as an IPP request message
     *
     * @return string
     */
    public function __toString(){
        return implode('', $this->strings);
    }
}
