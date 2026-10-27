<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'current_tenant_id',
        'is_super_admin',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
        ];
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Tenants that this user belongs to.
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user')
            ->withPivot('username', 'role', 'is_active')
            ->withTimestamps();
    }

    /**
     * Active / currently selected tenant context.
     */
    public function currentTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'current_tenant_id');
    }

    /**
     * Ephemeral memoized tenant admin status for request lifecycle.
     */
    public ?bool $_cached_is_tenant_admin = null;

    /**
     * Track tenant id for which _cached_is_tenant_admin was computed.
     */
    protected ?int $_cached_tenant_admin_tenant_id = null;

    /**
     * Check if user is the platform super admin.
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function usernameForTenant(int|Tenant|null $tenant = null): ?string
    {
        $tenantId = $tenant instanceof Tenant
            ? $tenant->id
            : ($tenant ?? app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? $this->current_tenant_id);

        if (! $tenantId) {
            return null;
        }

        if ($this->relationLoaded('tenants')) {
            return $this->tenants->firstWhere('id', $tenantId)?->pivot?->username;
        }

        return $this->tenants()->where('tenants.id', $tenantId)->first()?->pivot?->username;
    }

    /**
     * Check if user belongs to a given tenant.
     */
    public function belongsToTenant(int|Tenant $tenant): bool
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;
        return $this->tenants()
            ->where('tenants.id', $tenantId)
            ->wherePivot('is_active', true)
            ->exists();
    }

    /**
     * Check if user is a tenant administrator (Company MD / Business Admin).
     */
    public function isTenantAdmin(int|Tenant|null $tenantId = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $targetTenantId = $tenantId instanceof Tenant ? $tenantId->id : $tenantId;
        if ($targetTenantId === null) {
            if (class_exists(\App\Services\Tenancy\TenantContext::class)) {
                $targetTenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId();
            }
            $targetTenantId ??= $this->current_tenant_id;
            if (! $targetTenantId && session()->has('active_tenant_id')) {
                $targetTenantId = session('active_tenant_id');
            }
        }

        // Return memoized result if already evaluated for this tenant
        if ($this->_cached_is_tenant_admin !== null && $this->_cached_tenant_admin_tenant_id === $targetTenantId) {
            return $this->_cached_is_tenant_admin;
        }

        $isAdmin = false;

        // 1. Sync Spatie team context and check Spatie role
        if ($targetTenantId && function_exists('getPermissionsTeamId') && function_exists('setPermissionsTeamId')) {
            if (getPermissionsTeamId() !== (int) $targetTenantId) {
                setPermissionsTeamId((int) $targetTenantId);
                if ($this->relationLoaded('roles')) {
                    $this->unsetRelation('roles');
                }
            }
        }

        try {
            if ($this->hasRole('admin')) {
                $isAdmin = true;
            }
        } catch (\Throwable $e) {
            // Spatie tables or cache uninitialized
        }

        // 2. Check tenant_user membership pivot table
        if (! $isAdmin && $targetTenantId) {
            if ($this->relationLoaded('tenants')) {
                $isAdmin = $this->tenants->contains(function ($tenant) use ($targetTenantId) {
                    return (int) $tenant->id === (int) $targetTenantId
                        && ($tenant->pivot->role ?? null) === 'admin'
                        && (bool) ($tenant->pivot->is_active ?? true);
                });
            } else {
                try {
                    $isAdmin = \Illuminate\Support\Facades\DB::table('tenant_user')
                        ->where('tenant_id', $targetTenantId)
                        ->where('user_id', $this->id)
                        ->where('role', 'admin')
                        ->where('is_active', true)
                        ->exists();
                } catch (\Throwable $e) {
                    $isAdmin = false;
                }
            }
        }

        // 3. Fallback to current_tenant_id if targetTenantId wasn't set
        if (! $isAdmin && ! $targetTenantId && $this->current_tenant_id) {
            try {
                $isAdmin = \Illuminate\Support\Facades\DB::table('tenant_user')
                    ->where('tenant_id', $this->current_tenant_id)
                    ->where('user_id', $this->id)
                    ->where('role', 'admin')
                    ->where('is_active', true)
                    ->exists();
            } catch (\Throwable $e) {
                $isAdmin = false;
            }
        }

        // Memoize for in-memory request lifecycle
        $this->_cached_is_tenant_admin = $isAdmin;
        $this->_cached_tenant_admin_tenant_id = $targetTenantId;

        return $isAdmin;
    }

    /**
     * Get the highest role level for the user.
     */
    public function getRoleLevel(): int
    {
        $hierarchy = [
            'viewer' => 1,
            'staff' => 2,
            'delivery_staff' => 2,
            'data_entry' => 2,
            'accountant' => 3,
            'manager' => 3,
            'admin' => 4
        ];

        return $this->roles->map(fn($role) => $hierarchy[$role->name] ?? 0)->max() ?? 0;
    }
}
