<?php

namespace Smalot\Cups\Builder;

use Smalot\Cups\CupsException;
use Smalot\Cups\Model\Operations;

class IppRequestBuilder
{
    /**
     * @var string[]
     */
    private $strings = [];

    /**
     * @param string $version
     * @param int    $operation_id
     *
     * @throws CupsException
     */
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
    public function addAddAttribute(string $string): IppRequestBuilder
    {
        $this->strings[] = $string;

        return $this;
    }

    /**
     * Add an attribute tag
     *
     * @return $this
     */
    public function addAddAttributeTag(int $tag_id): IppRequestBuilder
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
