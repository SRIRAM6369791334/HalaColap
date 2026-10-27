<?php

namespace Tests\Tier1;

use App\Models\Dealer;
use Tests\E2ETestCase;

/**
 * Feature F10: Eloquent Tenant Global Scope & Trait
 * Verifies that the BelongsToTenant trait and TenantGlobalScope auto-filter queries,
 * auto-assign tenant_id, and prevent cross-tenant IDOR leakage.
 */
class F10_EloquentTenantGlobalScopeTest extends E2ETestCase
{
    public function test_belongs_to_tenant_trait_exists(): void
    {
        $this->requiresClass(\App\Traits\BelongsToTenant::class);
        $this->assertTrue(trait_exists(\App\Traits\BelongsToTenant::class));
    }

    public function test_tenant_global_scope_class_exists(): void
    {
        $this->requiresClass(\App\Scopes\TenantGlobalScope::class);
        $this->assertTrue(class_exists(\App\Scopes\TenantGlobalScope::class));
    }

    public function test_cross_tenant_model_lookup_enforces_zero_idor(): void
    {
        $this->requiresClass(\App\Traits\BelongsToTenant::class);
        $this->requiresColumn('dealers', 'tenant_id');

        $t1 = $this->createTenant();
        $t2 = $this->createTenant();

        // Create dealer in tenant 1
        $dealerT1 = Dealer::create([
            'tenant_id' => $t1->id,
            'firm_name' => 'Tenant 1 Dealer',
            'phone'     => '9123456780',
        ]);

        // Switch context to tenant 2
        $this->setTenantContext($t2);

        // Attempting to look up Tenant 1's dealer while in Tenant 2 context must fail (Zero IDOR)
        $this->assertZeroIdor(function () use ($dealerT1) {
            return Dealer::find($dealerT1->id);
        });
    }

    public function test_model_creation_auto_stamps_tenant_id(): void
    {
        $this->requiresClass(\App\Traits\BelongsToTenant::class);
        $this->requiresColumn('dealers', 'tenant_id');

        $t1 = $this->createTenant();
        $this->setTenantContext($t1);

        $dealer = Dealer::create([
            'firm_name' => 'Auto Stamped Dealer',
            'phone'     => '9988776655',
        ]);

        $this->assertEquals($t1->id, $dealer->tenant_id, 'Model creating event did not auto-stamp active tenant_id.');
    }

    public function test_without_tenancy_scope_allows_platform_wide_access(): void
    {
        $this->requiresClass(\App\Traits\BelongsToTenant::class);
        $this->requiresColumn('dealers', 'tenant_id');

        $t1 = $this->createTenant();
        $t2 = $this->createTenant();

        Dealer::create(['tenant_id' => $t1->id, 'firm_name' => 'Dealer T1', 'phone' => '1111111111']);
        Dealer::create(['tenant_id' => $t2->id, 'firm_name' => 'Dealer T2', 'phone' => '2222222222']);

        $this->setTenantContext($t1);

        if (method_exists(Dealer::class, 'withoutTenancy')) {
            $allDealers = Dealer::withoutTenancy()->count();
            $this->assertGreaterThanOrEqual(2, $allDealers);
        }
    }
}
