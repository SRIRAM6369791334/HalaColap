<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class StockItem extends Model
{
    use BelongsToTenant;

    protected $table = 'stock_items';

    protected $fillable = ['tenant_id', 'item_name', 'category', 'unit', 'current_stock', 'reorder_level'];

    public function transactions()
    {
        return $this->hasMany(StockTransaction::class, 'item_name', 'item_name');
    }
}
