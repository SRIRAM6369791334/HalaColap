<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'vehicle_number', 'vehicle_type', 'capacity'];

    public function routes()
    {
        return $this->hasMany(Route::class);
    }
}
