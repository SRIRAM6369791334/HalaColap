<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view users') || $user->can('manage users');
    }

    public function view(User $user, User $target): bool
    {
        return $user->can('view users') || $user->can('manage users');
    }

    public function create(User $user): bool
    {
        return $user->can('create users') || $user->can('manage users');
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('edit users') || $user->can('manage users');
    }

    public function delete(User $user, User $target): bool
    {
        if ($user->id === $target->id) return false;
        if ($target->hasRole('admin')) return false;
        return $user->can('delete users') || $user->can('manage users');
    }

    public function toggleStatus(User $user, User $target): bool
    {
        if ($user->id === $target->id) return false;
        return $user->can('edit users') || $user->can('manage users');
    }
}
