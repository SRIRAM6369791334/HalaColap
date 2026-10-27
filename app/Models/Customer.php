<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name', 'phone', 'address', 'gst_number', 'route', 'route_id', 'type', 'balance'
    ];

    protected $casts = [
        'balance' => 'decimal:2'
    ];

    // ── Relationships ──────────────────────────────────────────────────────────
    public function routeRelation()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function weeklyBills(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WeeklyBill::class);
    }

    public function dailyBills(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DailyBill::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function emis(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Emi::class, 'entity_id')->where('emi_type', 'Customer');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────
    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('route', 'like', "%{$term}%");
        });
    }

    public function scopeWithBalance($query)
    {
        return $query->where('balance', '>', 0);
    }

    // ── Accessors ──────────────────────────────────────────────────────────────
    public function getFormattedBalanceAttribute(): string
    {
        return $this->balance > 0 ? '₹' . number_format($this->balance, 0, '.', ',') : '—';
    }

    protected static function booted()
    {
        static::created(function ($customer) {
            $users = $customer->tenant?->users ?: collect();
            if ($users->isNotEmpty()) {
                \Illuminate\Support\Facades\Notification::send($users, new \App\Notifications\ActivityNotification(
                    'New Customer Added',
                    "Customer {$customer->name} was registered.",
                    route('masters.customers.show', $customer->id),
                    'person',
                    'emerald'
                ));
            }
        });
    }
}
