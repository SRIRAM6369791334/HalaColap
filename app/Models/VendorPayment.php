<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPayment extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'vendor_id',
        'day_load_entry_id',
        'date',
        'amount',
        'payment_mode',
        'cash_amount',
        'bank_amount',
        'bank_transfer_type',
        'reference_number',
        'notes',
        'pending_balance_after',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'cash_amount' => 'decimal:2',
        'bank_amount' => 'decimal:2',
        'pending_balance_after' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function dayLoadEntry(): BelongsTo
    {
        return $this->belongsTo(DayLoadEntry::class, 'day_load_entry_id');
    }
}
