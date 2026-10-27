<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class BirdBatch extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'batch_name', 'date_received', 'initial_count', 'current_count', 'avg_weight'
    ];

    protected $casts = [
        'date_received' => 'date',
    ];
}
