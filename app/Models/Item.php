<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'type',
        'category',
        'brand',
        'base_unit',
        'conversion_rate',
        'is_active',
        'status'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function stockLedgers()
    {
        return $this->hasMany(StockLedger::class);
    }

    /**
     * Calculate current stock by summing IN and subtracting OUT
     */
    public function getCurrentStockAttribute()
    {
        $in = $this->stockLedgers()->where('type', 'IN')->sum('quantity');
        $out = $this->stockLedgers()->where('type', 'OUT')->sum('quantity');
        return $in - $out;
    }

    /**
     * Accessor for status attribute (compatible with legacy tests).
     */
    public function getStatusAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    /**
     * Mutator for status attribute (compatible with legacy tests).
     */
    public function setStatusAttribute($value)
    {
        $this->attributes['is_active'] = ($value === 'Active');
    }
}
