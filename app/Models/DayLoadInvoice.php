<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DayLoadInvoice extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'batch_id',
        'invoice_no',
        'invoice_date',
        'total_boxes',
        'total_box_weight',
        'total_empty_weight',
        'total_bird_weight',
        'total_farm_weight',
        'total_weight',
        'total_loss_weight',
        'total_amount',
        'amount_paid',
        'payment_status',
        'status',
        'version',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'total_boxes' => 'decimal:2',
        'total_box_weight' => 'decimal:2',
        'total_empty_weight' => 'decimal:2',
        'total_bird_weight' => 'decimal:2',
        'total_farm_weight' => 'decimal:2',
        'total_weight' => 'decimal:2',
        'total_loss_weight' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'version' => 'integer',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DayLoadBatch::class, 'batch_id');
    }

    public function dealerPayments(): HasMany
    {
        return $this->hasMany(DealerPayment::class, 'invoice_id');
    }
}
