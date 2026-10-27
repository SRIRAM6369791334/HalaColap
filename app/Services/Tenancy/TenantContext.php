<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;

class TenantContext
{
    private ?Tenant $tenant = null;

    /**
     * Set the current tenant.
     */
    public function setTenant(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    /**
     * Get the current active tenant.
     */
    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    /**
     * Get the active tenant ID, if any.
     */
    public function getTenantId(): ?int
    {
        return $this->tenant?->id;
    }

    /**
     * Determine if a tenant is currently resolved.
     */
    public function check(): bool
    {
        return $this->tenant !== null;
    }

    /**
     * Clear the tenant context.
     */
    public function clear(): void
    {
        $this->tenant = null;
    }
}
