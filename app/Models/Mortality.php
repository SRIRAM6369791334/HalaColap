<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mortality extends Model
{
    use BelongsToTenant;

    protected $table = 'poultry_mortalities';

    protected $fillable = [
        'tenant_id',
        'date',
        'batch_id',
        'count',
        'reason',
        'remarks',
        'created_by'
    ];

    protected $casts = [
        'date' => 'date',
        'count' => 'integer'
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
