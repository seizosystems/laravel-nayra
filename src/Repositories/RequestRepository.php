<?php
declare(strict_types=1);

namespace Viezel\Nayra\Repositories;

use Viezel\Nayra\Contracts\RequestRepositoryInterface;
use Viezel\Nayra\Models\Request;

class RequestRepository implements RequestRepositoryInterface
{
    public function find($id)
    {
        return Request::find($id);
    }

    public function make(array $data)
    {
        return Request::make($data);
    }
}
