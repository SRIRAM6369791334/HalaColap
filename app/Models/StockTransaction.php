<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockTransaction extends Model
{
    use BelongsToTenant;

    protected $table = 'stock_transactions';

    protected $fillable = [
        'tenant_id',
        'type', 'txn_type', 'item_name', 'quantity', 'unit', 'rate', 
        'reference_type', 'reference_id', 'notes', 'date', 'created_by'
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'decimal:3',
        'rate' => 'decimal:2',
    ];

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
