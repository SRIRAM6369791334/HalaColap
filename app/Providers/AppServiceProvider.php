<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * Binds Repository Interfaces → Eloquent Implementations (Clean Architecture).
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\Contracts\StockRepositoryInterface::class,
            \App\Repositories\StockRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\PurchaseRepositoryInterface::class,
            \App\Repositories\PurchaseRepository::class
        );

        $this->app->singleton(\App\Services\Tenancy\TenantContext::class, function () {
            return new \App\Services\Tenancy\TenantContext();
        });
    }


    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ Gate authorization rules for Multi-Tenant SaaS
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability, $arguments = []) {
            if (! $user) {
                return false;
            }

            // a. Platform Super Admin (MSR Broilers Owner) universal bypass
            $isSuperAdmin = method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();

            if ($isSuperAdmin) {
                return true;
            }

            // b. Platform Super Admin ability blacklist: explicitly return false for non-super admins
            $platformSuperAdminAbilities = [
                'manage-platform-tenants',
                'create-tenants',
                'resell-saas',
                'manage-tenants',
                'manage-resellers',
                'manage-platform-settings',
                'system-level-override',
                'platform-admin',
            ];

            if (in_array($ability, $platformSuperAdminAbilities, true)) {
                return false;
            }

            // Prevent non-super-admins from mutating or deleting Platform Super Admin accounts
            if (in_array($ability, ['delete', 'update', 'toggleStatus'], true) && isset($arguments[0]) && $arguments[0] instanceof \App\Models\User) {
                $target = $arguments[0];
                if ($target->isSuperAdmin()) {
                    return false;
                }
            }

            // c. Resolve active tenant context & synchronize Spatie setPermissionsTeamId()
            $activeTenantId = null;
            if (class_exists(\App\Services\Tenancy\TenantContext::class)) {
                $activeTenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId();
            }
            $activeTenantId ??= $user->current_tenant_id;
            if (! $activeTenantId && session()->has('active_tenant_id')) {
                $activeTenantId = session('active_tenant_id');
            }

            if ($activeTenantId && function_exists('setPermissionsTeamId') && function_exists('getPermissionsTeamId')) {
                if (getPermissionsTeamId() !== (int) $activeTenantId) {
                    setPermissionsTeamId((int) $activeTenantId);
                    if (method_exists($user, 'relationLoaded') && $user->relationLoaded('roles')) {
                        $user->unsetRelation('roles');
                    }
                }
            }

            // Tenant Admin operational access (100% full operational access within active tenant)
            if (method_exists($user, 'isTenantAdmin') && $user->isTenantAdmin($activeTenantId)) {
                return true;
            }

            // d. Sub-users (staff, accountants): return null so granular Spatie permissions apply
            return null;
        });

        // \Illuminate\Pagination\Paginator::useBootstrapFive();
        \Illuminate\Support\Facades\RateLimiter::for('billing', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('payments', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        \App\Models\Customer::observe(\App\Observers\CustomerObserver::class);
        \App\Models\Item::observe(\App\Observers\ItemObserver::class);
    }
}
