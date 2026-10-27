<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use BelongsToTenant, HasFactory;
    
    protected $fillable = [
        'tenant_id',
        'vendor_id', 'vendor_name', 'invoice_no', 'date', 'due_date', 'gst_percentage', 'gst_amount', 'total_amount', 'payment_mode',
    ];

    protected $casts = [
        'date'           => 'date',
        'due_date'       => 'date',
        'gst_percentage' => 'decimal:2',
        'gst_amount'     => 'decimal:2',
        'total_amount'   => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('vendor_name', 'like', "%{$term}%")
              ->orWhereHas('items', function($iq) use ($term) {
                  $iq->where('item_name', 'like', "%{$term}%");
              });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Backward Compatibility Accessors
    |--------------------------------------------------------------------------
    */

    public function getQuantityAttribute(): float
    {
        if (array_key_exists('quantity', $this->attributes)) {
            return (float) $this->attributes['quantity'];
        }
        return $this->relationLoaded('items')
            ? (float) $this->items->sum('quantity')
            : (float) $this->items()->sum('quantity');
    }

    public function getUnitAttribute(): ?string
    {
        if (array_key_exists('unit', $this->attributes)) {
            return $this->attributes['unit'];
        }
        $firstItem = $this->relationLoaded('items') ? $this->items->first() : $this->items()->first();
        return $firstItem ? $firstItem->unit : null;
    }

    public function getRateAttribute(): float
    {
        if (array_key_exists('rate', $this->attributes)) {
            return (float) $this->attributes['rate'];
        }
        $firstItem = $this->relationLoaded('items') ? $this->items->first() : $this->items()->first();
        return $firstItem ? (float) $firstItem->rate : 0.0;
    }

    public function getItemAttribute(): ?string
    {
        if (array_key_exists('item', $this->attributes)) {
            return $this->attributes['item'];
        }
        $firstItem = $this->relationLoaded('items') ? $this->items->first() : $this->items()->first();
        return $firstItem ? $firstItem->item_name : null;
    }

    protected static function booted()
    {
        static::created(function ($purchase) {
            $users = $purchase->tenant?->users ?: collect();
            if ($users->isNotEmpty()) {
                \Illuminate\Support\Facades\Notification::send($users, new \App\Notifications\ActivityNotification(
                    'New Purchase Invoice',
                    "Purchase invoice {$purchase->invoice_no} was generated for {$purchase->vendor_name}.",
                    route('purchases.show', $purchase->id),
                    'shopping_cart',
                    'orange'
                ));
            }
        });
    }
}

