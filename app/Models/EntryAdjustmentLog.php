<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryAdjustmentLog extends Model
{
    use BelongsToTenant, HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'entry_id',
        'action_type',
        'old_values',
        'new_values',
        'resulting_entry_id',
        'reason',
        'adjusted_by',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(DayLoadEntry::class, 'entry_id');
    }

    public function resultingEntry(): BelongsTo
    {
        return $this->belongsTo(DayLoadEntry::class, 'resulting_entry_id');
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}
