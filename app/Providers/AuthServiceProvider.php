<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Policies\PermissionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Role::class       => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
        User::class       => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
