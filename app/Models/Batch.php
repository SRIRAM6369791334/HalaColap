<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'batch_code',
        'placement_date',
        'initial_count',
        'current_count',
        'breed',
        'avg_placement_weight',
        'status',
        'closed_at'
    ];

    protected $casts = [
        'placement_date' => 'date',
        'closed_at' => 'date',
    ];

    public function consumptions(): HasMany
    {
        return $this->hasMany(Consumption::class, 'batch_id');
    }

    public function mortalities(): HasMany
    {
        return $this->hasMany(Mortality::class, 'batch_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }
}

if (!class_exists('App\Models\DailyConsumption')) {
    class_alias(Consumption::class, 'App\Models\DailyConsumption');
}
