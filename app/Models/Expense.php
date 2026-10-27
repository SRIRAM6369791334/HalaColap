<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use BelongsToTenant, HasFactory;
    use SoftDeletes;

    protected $fillable = ['tenant_id', 'date', 'category', 'category_id', 'description', 'amount', 'payment_method'];

    protected $casts = [
        'date'   => 'date',
        'amount' => 'decimal:2',
    ];

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }
}
