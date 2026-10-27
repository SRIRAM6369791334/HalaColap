<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class StockLedger extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'item_id',
        'batch_id',
        'warehouse_id',
        'quantity',
        'type',
        'source_type',
        'source_id',
        'unit',
        'transaction_date',
        'remarks'
    ];

    protected $casts = [
        'transaction_date' => 'date',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
