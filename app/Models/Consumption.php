<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consumption extends Model
{
    use BelongsToTenant;

    protected $table = 'poultry_consumptions';

    protected $fillable = [
        'tenant_id',
        'date',
        'batch_id',
        'item_id',
        'warehouse_id',
        'quantity',
        'unit',
        'remarks',
        'created_by'
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'decimal:2'
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
