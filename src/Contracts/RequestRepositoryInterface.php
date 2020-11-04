<?php
declare(strict_types=1);

namespace Viezel\Nayra\Contracts;

interface RequestRepositoryInterface
{
    /**
     * @param string $id
     *
     * @return Request
     */
    public function find($id);

    /**
     * @param array $data
     *
     * @return Request
     */
    public function make(array $data);
}
