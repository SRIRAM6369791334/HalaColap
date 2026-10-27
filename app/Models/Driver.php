<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'driver_name', 'phone', 'license_number'];

    public function routes()
    {
        return $this->hasMany(Route::class);
    }
}
