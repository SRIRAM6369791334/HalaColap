<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DayLoadBatch extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'billing_date',
        'status',
        'total_boxes',
        'total_box_weight',
        'total_empty_weight',
        'total_bird_weight',
        'total_farm_weight',
        'total_weight',
        'total_loss_weight',
        'weight_loss_amount',
        'is_weight_loss_approved',
        'weight_loss_approved_by',
        'weight_loss_approved_at',
        'invoice_id',
    ];

    protected $casts = [
        'billing_date'             => 'date',
        'total_boxes'              => 'decimal:2',
        'total_box_weight'         => 'decimal:2',
        'total_empty_weight'       => 'decimal:2',
        'total_bird_weight'        => 'decimal:2',
        'total_farm_weight'        => 'decimal:2',
        'total_weight'             => 'decimal:2',
        'total_loss_weight'        => 'decimal:2',
        'weight_loss_amount'       => 'decimal:2',
        'is_weight_loss_approved'  => 'boolean',
        'weight_loss_approved_at'  => 'datetime',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(DayLoadEntry::class, 'batch_id')
            ->orderByRaw('CASE WHEN sort_order IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('sort_order', 'asc')
            ->latest('id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(DayLoadInvoice::class, 'invoice_id');
    }

    public function weightLossApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'weight_loss_approved_by');
    }
}
