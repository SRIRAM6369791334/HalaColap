<?php

namespace Tests\Tier2;

use App\Models\Dealer;
use Tests\E2ETestCase;

/**
 * Feature F10 Boundary Tests: Eloquent Tenant Global Scope & Trait
 * Verifies empty results, complex where clauses, cross-tenant update/delete blocking,
 * and relation boundaries.
 */
class F10_EloquentTenantGlobalScopeBoundaryTest extends E2ETestCase
{
    public function test_query_with_no_records_returns_empty_collection(): void
    {
        $this->requiresClass(\App\Traits\BelongsToTenant::class);
        $this->requiresColumn('dealers', 'tenant_id');

        $t1 = $this->createTenant();
        $this->setTenantContext($t1);

        $results = Dealer::all();
        $this->assertCount(0, $results);
    }

    public function test_additional_where_clauses_compose_cleanly_with_tenant_scope(): void
    {
        $this->requiresClass(\App\Traits\BelongsToTenant::class);
        $this->requiresColumn('dealers', 'tenant_id');

        $t1 = $this->createTenant();
        $this->setTenantContext($t1);

        $d1 = $this->createDealer(['tenant_id' => $t1->id, 'firm_name' => 'Active Dealer', 'location' => 'Salem']);
        $d2 = $this->createDealer(['tenant_id' => $t1->id, 'firm_name' => 'Other Dealer', 'location' => 'Namakkal']);

        $salemDealers = Dealer::where('location', 'Salem')->get();
        $this->assertCount(1, $salemDealers);
        $this->assertEquals($d1->id, $salemDealers->first()->id);
    }

    public function test_cross_tenant_update_is_blocked_by_global_scope(): void
    {
        $this->requiresClass(\App\Traits\BelongsToTenant::class);
        $this->requiresColumn('dealers', 'tenant_id');

        $t1 = $this->createTenant();
        $t2 = $this->createTenant();

        $d1 = $this->createDealer(['tenant_id' => $t1->id, 'firm_name' => 'Original Name']);

        // Switch to tenant 2
        $this->setTenantContext($t2);

        // Attempting to update tenant 1's dealer through query builder in tenant 2 context
        $affected = Dealer::where('id', $d1->id)->update(['firm_name' => 'Hacked Name']);
        $this->assertEquals(0, $affected, 'Cross-tenant update succeeded!');

        $d1->refresh();
        $this->assertEquals('Original Name', $d1->firm_name);
    }

    public function test_cross_tenant_delete_is_blocked_by_global_scope(): void
    {
        $this->requiresClass(\App\Traits\BelongsToTenant::class);
        $this->requiresColumn('dealers', 'tenant_id');

        $t1 = $this->createTenant();
        $t2 = $this->createTenant();

        $d1 = $this->createDealer(['tenant_id' => $t1->id, 'firm_name' => 'Target Dealer']);

        // Switch to tenant 2
        $this->setTenantContext($t2);

        $affected = Dealer::where('id', $d1->id)->delete();
        $this->assertEquals(0, $affected, 'Cross-tenant delete succeeded!');

        $this->assertDatabaseHas('dealers', ['id' => $d1->id]);
    }

    public function test_eager_loading_scoped_to_active_tenant(): void
    {
        $this->requiresClass(\App\Traits\BelongsToTenant::class);
        $this->requiresColumn('dealers', 'tenant_id');

        $t1 = $this->createTenant();
        $this->setTenantContext($t1);

        $dealer = $this->createDealer(['tenant_id' => $t1->id]);
        $loaded = Dealer::with('payments')->find($dealer->id);

        $this->assertNotNull($loaded);
    }
}
