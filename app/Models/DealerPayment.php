<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealerPayment extends Model
{
    use BelongsToTenant, HasFactory;
    
    protected $fillable = [
        'tenant_id',
        'dealer_id', 'invoice_id', 'day_load_entry_id', 'payment_group_id',
        'date', 'amount', 'payment_mode',
        'cash_amount', 'bank_amount', 'bank_transfer_type',
        'reference_number', 'notes', 'pending_balance_after', 'discount_amount',
    ];

    protected $casts = [
        'date'            => 'date',
        'amount'          => 'decimal:2',
        'cash_amount'     => 'decimal:2',
        'bank_amount'     => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function dealer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function dayLoadInvoice(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DayLoadInvoice::class, 'invoice_id');
    }

    public function dayLoadEntry(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DayLoadEntry::class, 'day_load_entry_id');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->whereHas('dealer', fn($q) => $q->where('firm_name', 'like', "%{$term}%"));
    }
}
