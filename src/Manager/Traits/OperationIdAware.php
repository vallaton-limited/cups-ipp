<?php

namespace Smalot\Cups\Manager\Traits;

use Smalot\Cups\CupsException;

/**
 * Trait OperationIdAware
 *
 * @package Smalot\Cups\Manager\Traits
 */
trait OperationIdAware
{

    /**
     * @var int
     */
    protected $operation_id;

    /**
     * @param string $type
     *
     * @return int
     */
    public function getOperationId(string $type = 'current'): int
    {
        if ($type === 'new') {
            $this->operation_id++;
        }

        return $this->operation_id;
    }

    /**
     * @param int $operation_id
     *
     * @return OperationIdAware
     */
    public function setOperationId(int $operation_id)
    {
        $this->operation_id = $operation_id;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return string
     * @throws CupsException
     */
    protected function buildOperationId(string $type = 'new'): string
    {
        $operation_id = $this->getOperationId($type);
        return $this->builder->formatInteger($operation_id);
    }
}
