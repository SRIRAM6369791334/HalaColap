<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'location',
        'is_active'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
