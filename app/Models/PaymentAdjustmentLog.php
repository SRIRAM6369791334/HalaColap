<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAdjustmentLog extends Model
{
    use BelongsToTenant, HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'payment_id',
        'action_type',
        'old_values',
        'new_values',
        'reason',
        'adjusted_by',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(DealerPayment::class, 'payment_id');
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}
