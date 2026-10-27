<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = ['team_id', 'name', 'guard_name', 'description', 'is_system', 'created_by'];

    protected $casts = ['is_system' => 'boolean'];
}
