<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyBillItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'daily_bill_id',
        'item_name',
        'quantity_kg',
        'rate_per_kg',
        'tax_amount',
        'total_amount'
    ];

    public function dailyBill(): BelongsTo
    {
        return $this->belongsTo(DailyBill::class);
    }
}
