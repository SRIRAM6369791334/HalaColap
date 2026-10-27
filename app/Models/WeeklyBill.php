<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklyBill extends Model
{
    use BelongsToTenant, HasFactory;
    
    protected $fillable = [
        'tenant_id',
        'dealer_id', 'period_start', 'period_end', 'invoice_no',
        'amount', 'gst_percentage', 'gst_amount', 'net_amount',
        'discount_percentage', 'discount_amount', 'status', 'payment_mode', 'bank_method',
        'previous_outstanding', 'payments_during_week'
    ];

    protected $casts = [
        'period_start'          => 'date',
        'period_end'            => 'date',
        'amount'                => 'decimal:2',
        'gst_percentage'        => 'decimal:2',
        'gst_amount'            => 'decimal:2',
        'net_amount'            => 'decimal:2',
        'discount_percentage'   => 'decimal:2',
        'discount_amount'       => 'decimal:2',
        'previous_outstanding'  => 'decimal:2',
        'payments_during_week'  => 'decimal:2',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WeeklyBillItem::class);
    }

    public function dealerPurchases(): HasMany
    {
        return $this->hasMany(DealerPurchase::class);
    }

    public function dayLoadEntries(): HasMany
    {
        return $this->hasMany(DayLoadEntry::class);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->whereHas('dealer', fn($q) => $q->where('firm_name', 'like', "%{$term}%"))
                     ->orWhereHas('items', fn($q) => $q->where('item_name', 'like', "%{$term}%"));
    }

    public function getInvoiceNumberAttribute(): string
    {
        return 'INV-W-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    /*
    |--------------------------------------------------------------------------
    | Backward Compatibility Accessors
    |--------------------------------------------------------------------------
    */

    public function getItemsDescriptionAttribute(): ?string
    {
        if (array_key_exists('items_description', $this->attributes) && $this->attributes['items_description'] !== null) {
            return $this->attributes['items_description'];
        }
        return $this->relationLoaded('items') 
            ? $this->items->pluck('item_name')->implode(', ')
            : $this->items()->pluck('item_name')->implode(', ');
    }

    public function getQuantityKgAttribute(): float
    {
        if (array_key_exists('quantity_kg', $this->attributes) && $this->attributes['quantity_kg'] !== null) {
            return (float) $this->attributes['quantity_kg'];
        }
        return $this->relationLoaded('items')
            ? (float) $this->items->sum('quantity_kg')
            : (float) $this->items()->sum('quantity_kg');
    }

    protected static function booted()
    {
        static::created(function ($bill) {
            $users = $bill->tenant?->users ?: collect();
            if ($users->isNotEmpty()) {
                \Illuminate\Support\Facades\Notification::send($users, new \App\Notifications\ActivityNotification(
                    'New Weekly Invoice',
                    "Weekly invoice {$bill->invoice_no} was generated.",
                    route('billing.weekly.show', $bill->id),
                    'receipt_long',
                    'purple'
                ));
            }
        });
    }
}
