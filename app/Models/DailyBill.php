<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyBill extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_id', 'dealer_id', 'date', 'date_from', 'date_to', 'amount', 'gst_percentage', 'gst_amount',
        'net_amount', 'discount_percentage', 'discount_amount', 'payment_mode', 'bank_method',
        'status', 'invoice_no', 'previous_outstanding', 'payments_during_day'
    ];

    protected $casts = [
        'date'                 => 'date',
        'date_from'            => 'date',
        'date_to'              => 'date',
        'amount'               => 'decimal:2',
        'gst_percentage'       => 'decimal:2',
        'gst_amount'           => 'decimal:2',
        'net_amount'           => 'decimal:2',
        'discount_percentage'  => 'decimal:2',
        'discount_amount'      => 'decimal:2',
        'previous_outstanding' => 'decimal:2',
        'payments_during_day'  => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DailyBillItem::class);
    }

    public function dayLoadEntries(): HasMany
    {
        return $this->hasMany(DayLoadEntry::class, 'daily_bill_id');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->whereHas('dealer', fn($q) => $q->where('firm_name', 'like', "%{$term}%"))
                     ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%{$term}%"))
                     ->orWhereHas('items', fn($q) => $q->where('item_name', 'like', "%{$term}%"));
    }

    public function getInvoiceNumberAttribute(): string
    {
        return $this->invoice_no ?: 'INV-D-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    /*
    |--------------------------------------------------------------------------
    | Backward Compatibility Accessors
    |--------------------------------------------------------------------------
    */

    public function getItemsDescriptionAttribute(): ?string
    {
        if (array_key_exists('items_description', $this->attributes)) {
            return $this->attributes['items_description'];
        }
        $firstItem = $this->relationLoaded('items') ? $this->items->first() : $this->items()->first();
        return $firstItem ? $firstItem->item_name : null;
    }

    public function getQuantityKgAttribute(): float
    {
        if (array_key_exists('quantity_kg', $this->attributes)) {
            return (float) $this->attributes['quantity_kg'];
        }
        return $this->relationLoaded('items')
            ? (float) $this->items->sum('quantity_kg')
            : (float) $this->items()->sum('quantity_kg');
    }

    public function getRatePerKgAttribute(): float
    {
        if (array_key_exists('rate_per_kg', $this->attributes)) {
            return (float) $this->attributes['rate_per_kg'];
        }
        $firstItem = $this->relationLoaded('items') ? $this->items->first() : $this->items()->first();
        return $firstItem ? (float) $firstItem->rate_per_kg : 0.0;
    }

    protected static function booted()
    {
        static::created(function ($bill) {
            $users = $bill->tenant?->users ?: collect();
            if ($users->isNotEmpty()) {
                \Illuminate\Support\Facades\Notification::send($users, new \App\Notifications\ActivityNotification(
                    'New Daily Invoice',
                    "Daily invoice {$bill->invoice_no} was generated.",
                    route('billing.daily.invoice', $bill->id),
                    'receipt',
                    'violet'
                ));
            }
        });
    }
}
