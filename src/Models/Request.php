<?php
declare(strict_types=1);

namespace Viezel\Nayra\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $table = 'bpmn_requests';

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
        'tokens' => 'array',
    ];
}
