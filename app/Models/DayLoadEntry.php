<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DayLoadEntry extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'batch_id',
        'vendor_id',
        'dealer_id',
        'sort_order',
        'weekly_bill_id',
        'daily_bill_id',
        'paper_rate',
        'billing_rate',
        'customer_rate',
        'amount',
        'no_of_boxes',
        'box_weight',
        'empty_weight',
        'farm_weight',
        'total_weight',
        'loss_weight',
        'bird_weight',
        'status',
        'parent_entry_id',
        'version',
        'remarks',
        'dealer_collected',
        'vendor_paid',
        'dealer_payment_status',
        'vendor_payment_status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'no_of_boxes' => 'integer',
        'paper_rate' => 'decimal:2',
        'billing_rate' => 'decimal:2',
        'customer_rate' => 'decimal:2',
        'box_weight' => 'decimal:2',
        'empty_weight' => 'decimal:2',
        'bird_weight' => 'decimal:2',
        'farm_weight' => 'decimal:2',
        'total_weight' => 'decimal:2',
        'loss_weight' => 'decimal:2',
        'amount' => 'decimal:2',
        'dealer_collected' => 'decimal:2',
        'vendor_paid' => 'decimal:2',
        'version' => 'integer',
    ];

    public function getDealerIncomeAttribute(): float
    {
        return round((float) $this->bird_weight * (float) $this->customer_rate, 2);
    }

    public function getEffectiveFarmWeightAttribute(): ?float
    {
        if ($this->farm_weight !== null) {
            return (float) $this->farm_weight;
        }

        $batch = $this->batch;
        if ($batch && (float) $batch->total_farm_weight > 0) {
            $totalBirdWeight = (float) $batch->total_bird_weight;
            if ($totalBirdWeight > 0) {
                $ratio = (float) $this->bird_weight / $totalBirdWeight;
                return round($ratio * (float) $batch->total_farm_weight, 2);
            }
        }

        return null;
    }

    public function getVendorCostAttribute(): float
    {
        $effectiveFw = $this->effective_farm_weight;
        if ($effectiveFw === null) {
            return 0.0;
        }
        $vendorRate = (float) $this->billing_rate > 0 ? (float) $this->billing_rate : (float) $this->paper_rate;
        return round($effectiveFw * $vendorRate, 2);
    }

    public function getGrossMarginAttribute(): float
    {
        if ($this->effective_farm_weight === null) {
            return 0.0;
        }
        return round($this->dealer_income - $this->vendor_cost, 2);
    }

    public function getDealerBalanceAttribute(): float
    {
        return round($this->dealer_income - (float) $this->dealer_collected, 2);
    }

    public function getVendorBalanceAttribute(): float
    {
        if ($this->effective_farm_weight === null) {
            return 0.0;
        }
        return round($this->vendor_cost - (float) $this->vendor_paid, 2);
    }

    protected static function booted(): void
    {
        static::saving(function (DayLoadEntry $entry): void {
            $birdWeight = round((float) $entry->box_weight - (float) $entry->empty_weight, 2);

            $entry->bird_weight = $birdWeight;

            if ($entry->farm_weight !== null) {
                $farmWeight = (float) $entry->farm_weight;
                $entry->loss_weight = round($farmWeight - $birdWeight, 2);
                $entry->total_weight = $birdWeight;
            } else {
                $entry->loss_weight = null;
                $entry->total_weight = null;
            }

            $entry->amount = round((float) $entry->bird_weight * (float) $entry->customer_rate, 2);
        });
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DayLoadBatch::class, 'batch_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

    public function weeklyBill(): BelongsTo
    {
        return $this->belongsTo(WeeklyBill::class, 'weekly_bill_id');
    }

    public function dailyBill(): BelongsTo
    {
        return $this->belongsTo(DailyBill::class, 'daily_bill_id');
    }

    public function parentEntry(): BelongsTo
    {
        return $this->belongsTo(DayLoadEntry::class, 'parent_entry_id');
    }

    public function childEntries(): HasMany
    {
        return $this->hasMany(DayLoadEntry::class, 'parent_entry_id');
    }

    public function advanceAdjustments(): HasMany
    {
        return $this->hasMany(VendorAdvanceAdjustment::class, 'day_load_entry_id');
    }

    public function adjustmentLogs(): HasMany
    {
        return $this->hasMany(EntryAdjustmentLog::class, 'entry_id');
    }

    public function getRateDifferenceAttribute(): float
    {
        $vendorRate = (float) $this->billing_rate > 0 ? (float) $this->billing_rate : (float) $this->paper_rate;
        return round((float) $this->customer_rate - $vendorRate, 2);
    }

    public function dealerPayments(): HasMany
    {
        return $this->hasMany(DealerPayment::class, 'day_load_entry_id');
    }

    public function vendorPayments(): HasMany
    {
        return $this->hasMany(VendorPayment::class, 'day_load_entry_id');
    }
}
