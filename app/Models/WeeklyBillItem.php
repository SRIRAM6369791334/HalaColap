<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyBillItem extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'weekly_bill_id', 'item_name', 'vendor_name', 'quantity_kg', 'rate_per_kg', 
        'tax_amount', 'total_amount'
    ];

    protected $casts = [
        'quantity_kg'  => 'decimal:2',
        'rate_per_kg'  => 'decimal:2',
        'tax_amount'   => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function weeklyBill(): BelongsTo
    {
        return $this->belongsTo(WeeklyBill::class);
    }
}
