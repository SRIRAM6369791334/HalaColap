<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class VendorAdvance extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'vendor_id',
        'date',
        'total_amount',
        'cash_amount',
        'bank_amount',
        'investment_amount',
        'adjusted_amount',
        'payment_mode',
        'bank_transfer_type',
        'status',
        'reference_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date'              => 'date',
        'total_amount'      => 'decimal:2',
        'cash_amount'       => 'decimal:2',
        'bank_amount'       => 'decimal:2',
        'investment_amount' => 'decimal:2',
        'adjusted_amount'   => 'decimal:2',
    ];

    public function vendor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function adjustments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VendorAdvanceAdjustment::class);
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, round((float) $this->total_amount - (float) $this->adjusted_amount, 2));
    }

    public function getBalanceAmountAttribute(): float
    {
        return $this->remaining_amount;
    }
}
