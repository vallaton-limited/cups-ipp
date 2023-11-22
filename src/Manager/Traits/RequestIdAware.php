<?php

namespace Smalot\Cups\Manager\Traits;

use Smalot\Cups\CupsException;

/**
 * Trait RequestIdAware
 *
 * @package Smalot\Cups\Manager\Traits
 */
trait RequestIdAware
{

    /**
     * @var int
     */
    protected $request_id;

    /**
     * @param string $type
     *
     * @return int
     */
    public function getRequestId(string $type = 'current'): int
    {
        if ($type === 'new') {
            $this->request_id++;
        }

        return $this->request_id;
    }

    /**
     * @param int $request_id
     *
     * @return RequestIdAware
     */
    public function setRequestId(int $request_id)
    {
        $this->request_id = $request_id;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return string
     * @throws CupsException
     */
    protected function buildRequestId(string $type = 'new'): string
    {
        $operation_id = $this->getRequestId($type);
        return $this->builder->formatInteger($operation_id);
    }
}
