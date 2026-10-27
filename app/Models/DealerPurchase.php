<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DealerPurchase extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'dealer_id',
        'date',
        'invoice_no',
        'amount',
        'gst_percentage',
        'gst_amount',
        'net_amount',
        'weekly_bill_id'
    ];

    protected $casts = [
        'date'       => 'date',
        'amount'     => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function weeklyBill(): BelongsTo
    {
        return $this->belongsTo(WeeklyBill::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DealerPurchaseItem::class);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->whereHas('dealer', fn($q) => $q->where('firm_name', 'like', "%{$term}%"))
                     ->orWhere('invoice_no', 'like', "%{$term}%")
                     ->orWhereHas('items', fn($q) => $q->where('item_name', 'like', "%{$term}%"));
    }

    public function getItemsDescriptionAttribute(): string
    {
        return $this->items->pluck('item_name')->implode(', ');
    }

    public function getQuantityKgAttribute(): float
    {
        return (float) $this->items->sum('quantity_kg');
    }
}
