<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dealer extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'firm_name', 'sort_order', 'gst_number', 'location', 'contact_person', 'phone', 'route', 'route_id', 'pending_amount'
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'pending_amount' => 'decimal:2'
    ];

    // ── Relationships ──────────────────────────────────────────────────────────
    public function routeRelation()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DealerPayment::class);
    }

    public function dayLoadEntries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DayLoadEntry::class);
    }

    public function purchases(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Purchase::class, 'vendor_name', 'firm_name');
    }

    public function dealerPurchases(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DealerPurchase::class);
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

    public function getFormattedPendingAttribute(): string
    {
        return $this->pending_amount > 0 ? '₹' . number_format($this->pending_amount, 0, '.', ',') : '—';
    }

    /**
     * Day-load outstanding: sum of all non-cancelled entry amounts
     * minus payments made against those entries.
     *
     * Business rationale: liability accrues at delivery, not at invoicing.
     * A dealer owes for birds received even if the batch hasn't been
     * finalized into an invoice. Entry amounts (bird_weight × customer_rate)
     * are fixed at recording time with no later adjustment, so counting
     * un-invoiced entries gives an accurate view of true economic liability.
     */
    public function getDayloadOutstandingAttribute(): float
    {
        $entries = $this->dayLoadEntries()
            ->where('status', '!=', 'Cancelled')
            ->get();

        $outstanding = $entries->sum(function ($entry) {
            return max(0, (float) $entry->amount - (float) $entry->dealer_collected);
        });

        return max(0, $outstanding);
    }

    /**
     * Pending EMI dues for this dealer (Upcoming / Overdue)
     */
    public function getEmiOutstandingAttribute(): float
    {
        return (float) \App\Models\Emi::where('emi_type', 'Dealer')
            ->where('entity_id', $this->id)
            ->where('status', '!=', 'Paid')
            ->get()
            ->sum('remaining_amount');
    }

    /**
     * Merged outstanding displayed in dealer detail views:
     * old-system pending_amount + day-load outstanding + pending EMIs.
     */
    public function getDisplayedOutstandingAttribute(): float
    {
        $unallocatedPayments = $this->relationLoaded('payments')
            ? (float) $this->payments->filter(fn($p) => is_null($p->day_load_entry_id) && !str_starts_with($p->notes ?? '', 'EMI Payment'))->sum('amount')
            : (float) $this->payments()->whereNull('day_load_entry_id')
                ->where(function($q) {
                    $q->whereNull('notes')->orWhere('notes', 'not like', 'EMI Payment%');
                })->sum('amount');

        return (float) $this->pending_amount + $this->dayload_outstanding + $this->emi_outstanding - $unallocatedPayments;
    }

    protected static function booted()
    {
        static::created(function ($dealer) {
            $users = $dealer->tenant?->users ?: collect();
            if ($users->isNotEmpty()) {
                \Illuminate\Support\Facades\Notification::send($users, new \App\Notifications\ActivityNotification(
                    'New Dealer Added',
                    "Dealer {$dealer->firm_name} was registered.",
                    route('masters.dealers.show', $dealer->id),
                    'storefront',
                    'blue'
                ));
            }
        });
    }
}
