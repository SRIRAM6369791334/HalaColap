<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use BelongsToTenant, HasFactory;
    use SoftDeletes;

    protected $fillable = ['tenant_id', 'firm_name', 'is_shop', 'gst_number', 'location', 'contact_person', 'phone', 'route', 'notes', 'pending_amount'];

    protected $casts = [
        'is_shop'        => 'boolean',
        'pending_amount' => 'decimal:2',
    ];

    public function dayLoadEntries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DayLoadEntry::class);
    }

    public function purchases(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function vendorPayments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function advances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VendorAdvance::class);
    }

    public function getActiveAdvanceBalanceAttribute(): float
    {
        $advs = $this->relationLoaded('advances')
            ? $this->advances->where('status', '!=', 'Fully Adjusted')
            : $this->advances()->where('status', '!=', 'Fully Adjusted')->get();

        return (float) $advs->sum(fn($a) => $a->remaining_amount);
    }

    public function getEmiOutstandingAttribute(): float
    {
        return (float) \App\Models\Emi::where('emi_type', 'Vendor')
            ->where('entity_id', $this->id)
            ->where('status', '!=', 'Paid')
            ->get()
            ->sum('remaining_amount');
    }

    public function getOutstandingBalanceAttribute(): float
    {
        $totalCreditPurchases = $this->relationLoaded('purchases') 
            ? (float) $this->purchases->where('payment_mode', 'Credit')->sum('total_amount')
            : (float) $this->purchases()->where('payment_mode', 'Credit')->sum('total_amount');

        $dayLoadEntries = $this->relationLoaded('dayLoadEntries')
            ? $this->dayLoadEntries->where('status', '!=', 'Cancelled')
            : $this->dayLoadEntries()->where('status', '!=', 'Cancelled')->get();

        $totalDayLoadLiabilities = (float) $dayLoadEntries->sum(function ($entry) {
            return $entry->vendor_cost;
        });

        $totalPaymentsPaid = $this->relationLoaded('vendorPayments')
            ? (float) $this->vendorPayments->filter(fn($p) => !str_starts_with($p->notes ?? '', 'EMI Payment'))->sum('amount')
            : (float) $this->vendorPayments()->where(function($q) {
                $q->whereNull('notes')->orWhere('notes', 'not like', 'EMI Payment%');
            })->sum('amount');
        
        $openingOutstanding = (float) $this->pending_amount;

        return round(($totalCreditPurchases + $totalDayLoadLiabilities + $openingOutstanding + $this->emi_outstanding) - $totalPaymentsPaid - $this->active_advance_balance, 2);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('firm_name', 'like', "%{$term}%")
              ->orWhere('contact_person', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    public function scopeShops($query)
    {
        return $query->where('is_shop', true);
    }

    protected static function booted()
    {
        static::created(function ($vendor) {
            $users = $vendor->tenant?->users ?: collect();
            if ($users->isNotEmpty()) {
                \Illuminate\Support\Facades\Notification::send($users, new \App\Notifications\ActivityNotification(
                    'New Vendor Added',
                    "Vendor {$vendor->firm_name} was registered.",
                    route('masters.vendors.show', $vendor->id),
                    'local_shipping',
                    'amber'
                ));
            }
        });
    }
}
