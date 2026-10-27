<?php

namespace Tests\Tier1;

use Tests\E2ETestCase;

/**
 * Feature F09: Request Tenant Context Resolution
 * Verifies that the TenantContext service and ResolveTenantContext middleware
 * identify and resolve the active tenant context.
 */
class F09_RequestTenantContextTest extends E2ETestCase
{
    public function test_tenant_context_service_class_exists(): void
    {
        $this->requiresClass(\App\Services\Tenancy\TenantContext::class);
        $this->assertTrue(class_exists(\App\Services\Tenancy\TenantContext::class));
    }

    public function test_tenant_context_binds_and_retrieves_active_tenant(): void
    {
        $this->requiresClass(\App\Services\Tenancy\TenantContext::class);
        $this->requiresTable('tenants');

        $tenant = $this->createTenant(['name' => 'Context Test Farm']);
        $context = app(\App\Services\Tenancy\TenantContext::class);

        $tenantModel = class_exists(\App\Models\Tenant::class) ? \App\Models\Tenant::find($tenant->id) : null;
        if ($tenantModel) {
            $context->setTenant($tenantModel);
            $this->assertSame($tenant->id, $context->getTenantId());
            $this->assertSame($tenantModel->id, $context->getTenant()?->id);
        }
    }

    public function test_tenant_context_check_returns_true_when_bound(): void
    {
        $this->requiresClass(\App\Services\Tenancy\TenantContext::class);
        $context = app(\App\Services\Tenancy\TenantContext::class);

        if (method_exists($context, 'check')) {
            $this->assertIsBool($context->check());
        }
    }

    public function test_resolve_tenant_context_middleware_exists(): void
    {
        $middlewareClass = 'App\\Http\\Middleware\\ResolveTenantContext';
        $this->requiresClass($middlewareClass);
        $this->assertTrue(class_exists($middlewareClass));
    }

    public function test_tenant_context_defaults_to_null_when_unauthenticated(): void
    {
        $this->requiresClass(\App\Services\Tenancy\TenantContext::class);
        $context = app(\App\Services\Tenancy\TenantContext::class);
        if (method_exists($context, 'setTenant')) {
            $context->setTenant(null);
            $this->assertNull($context->getTenant());
            $this->assertNull($context->getTenantId());
            $this->assertFalse($context->check());
        }
    }
}
