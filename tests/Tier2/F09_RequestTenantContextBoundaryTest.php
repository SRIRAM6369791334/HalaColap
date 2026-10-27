<?php

namespace Tests\Tier2;

use Tests\E2ETestCase;

/**
 * Feature F09 Boundary Tests: Request Tenant Context Resolution
 * Verifies null context handling, rapid switching, singleton stability, and inactive tenant guards.
 */
class F09_RequestTenantContextBoundaryTest extends E2ETestCase
{
    public function test_null_tenant_clears_context_completely(): void
    {
        $this->requiresClass(\App\Services\Tenancy\TenantContext::class);

        $context = app(\App\Services\Tenancy\TenantContext::class);
        $context->setTenant(null);

        $this->assertNull($context->getTenant());
        $this->assertNull($context->getTenantId());
        $this->assertFalse($context->check());
    }

    public function test_rapid_consecutive_tenant_switching(): void
    {
        $this->requiresClass(\App\Services\Tenancy\TenantContext::class);
        $this->requiresTable('tenants');

        $t1 = $this->createTenant();
        $t2 = $this->createTenant();
        $t3 = $this->createTenant();

        $context = app(\App\Services\Tenancy\TenantContext::class);

        $m1 = class_exists(\App\Models\Tenant::class) ? \App\Models\Tenant::find($t1->id) : null;
        $m2 = class_exists(\App\Models\Tenant::class) ? \App\Models\Tenant::find($t2->id) : null;
        $m3 = class_exists(\App\Models\Tenant::class) ? \App\Models\Tenant::find($t3->id) : null;

        if ($m1 && $m2 && $m3) {
            $context->setTenant($m1);
            $this->assertEquals($t1->id, $context->getTenantId());

            $context->setTenant($m2);
            $this->assertEquals($t2->id, $context->getTenantId());

            $context->setTenant($m3);
            $this->assertEquals($t3->id, $context->getTenantId());
        }
    }

    public function test_tenant_context_is_shared_singleton_in_container(): void
    {
        $this->requiresClass(\App\Services\Tenancy\TenantContext::class);

        $instance1 = app(\App\Services\Tenancy\TenantContext::class);
        $instance2 = app(\App\Services\Tenancy\TenantContext::class);

        $this->assertSame($instance1, $instance2, 'TenantContext is not registered as a singleton!');
    }

    public function test_inactive_tenant_resolution_handling(): void
    {
        $this->requiresClass(\App\Services\Tenancy\TenantContext::class);
        $this->requiresTable('tenants');

        $inactiveTenant = $this->createTenant(['is_active' => false]);
        $context = app(\App\Services\Tenancy\TenantContext::class);

        $model = class_exists(\App\Models\Tenant::class) ? \App\Models\Tenant::find($inactiveTenant->id) : null;
        if ($model) {
            $context->setTenant($model);
            $this->assertEquals($inactiveTenant->id, $context->getTenantId());
            $this->assertFalse((bool)$context->getTenant()->is_active);
        }
    }

    public function test_unauthenticated_request_context_check(): void
    {
        $this->requiresClass(\App\Services\Tenancy\TenantContext::class);
        auth()->logout();
        $context = app(\App\Services\Tenancy\TenantContext::class);
        $context->setTenant(null);

        $this->assertFalse($context->check());
    }
}
