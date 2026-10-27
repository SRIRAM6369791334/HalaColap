<?php

namespace App\Traits;

use App\Models\Tenant;
use App\Scopes\TenantGlobalScope;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Boot the BelongsToTenant trait.
     */
    public static function bootBelongsToTenant(): void
    {
        // 1. Automatically scope all select, update, and delete queries to active tenant
        static::addGlobalScope(new TenantGlobalScope());

        // 2. Automatically assign tenant_id on creating new records unconditionally when tenant context is active
        static::creating(function (Model $model) {
            $context = app(TenantContext::class);
            if ($context->check()) {
                $model->tenant_id = $context->getTenantId();
            }
        });
    }

    /**
     * Query without the tenant global scope.
     */
    public static function withoutTenancy()
    {
        return static::withoutGlobalScope(TenantGlobalScope::class);
    }

    /**
     * Scope a query to ignore the tenant global scope.
     */
    public function scopeWithoutTenancy($query)
    {
        return $query->withoutGlobalScope(TenantGlobalScope::class);
    }

    /**
     * Relationship to the owning tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
