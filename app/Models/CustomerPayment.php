<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPayment extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'date',
        'amount',
        'cod_amount',
        'bank_transfer_amount',
        'payment_mode',
        'payment_type',
        'balance_after',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'cod_amount' => 'decimal:2',
        'bank_transfer_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->whereHas('customer', fn ($q) => $q->where('name', 'like', "%{$term}%"));
    }
}
