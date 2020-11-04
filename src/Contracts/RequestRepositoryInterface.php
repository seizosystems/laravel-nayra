<?php
declare(strict_types=1);

namespace Viezel\Nayra\Contracts;

use Viezel\Nayra\Models\Request;

interface RequestRepositoryInterface
{
    public function find($id): ?Request;

    public function make(array $data): ?Request;
}
