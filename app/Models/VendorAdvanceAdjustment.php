<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class VendorAdvanceAdjustment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'vendor_advance_id',
        'day_load_entry_id',
        'amount',
        'date',
        'notes',
    ];

    protected $casts = [
        'date'   => 'date',
        'amount' => 'decimal:2',
    ];

    public function advance(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(VendorAdvance::class, 'vendor_advance_id');
    }

    public function dayLoadEntry(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DayLoadEntry::class, 'day_load_entry_id');
    }
}
