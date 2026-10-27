<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view roles') || $user->can('manage roles');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('view roles') || $user->can('manage roles');
    }

    public function create(User $user): bool
    {
        return $user->can('create roles') || $user->can('manage roles');
    }

    public function update(User $user, Role $role): bool
    {
        if ($role->name === 'admin') return false;
        return $user->can('edit roles') || $user->can('manage roles');
    }

    public function delete(User $user, Role $role): bool
    {
        if ($role->name === 'admin' || $role->is_system) return false;
        return $user->can('delete roles') || $user->can('manage roles');
    }

    public function assignPermissions(User $user, Role $role): bool
    {
        if ($role->name === 'admin') return false;
        return $user->can('manage roles');
    }
}
