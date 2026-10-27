<?php

namespace Tests\Tier2;

use Illuminate\Support\Facades\DB;
use Tests\E2ETestCase;

/**
 * Feature F06 Boundary Tests: Additive Business Tables Partitioning
 * Verifies foreign key boundaries, cascade deletions, and zero/negative tenant IDs.
 */
class F06_BusinessTablesPartitioningBoundaryTest extends E2ETestCase
{
    public function test_business_table_rejects_non_existent_tenant_id_fk(): void
    {
        $this->requiresColumn('dealers', 'tenant_id');

        $this->expectException(\Exception::class);
        DB::table('dealers')->insert([
            'tenant_id'  => 99999999, // Non-existent foreign key
            'firm_name'  => 'Orphan Dealer',
            'phone'      => '9999999999',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_cascade_delete_of_tenant_removes_partitioned_records(): void
    {
        $this->requiresColumn('dealers', 'tenant_id');

        $tenant = $this->createTenant();
        $dealerId = DB::table('dealers')->insertGetId([
            'tenant_id'  => $tenant->id,
            'firm_name'  => 'Doomed Dealer',
            'phone'      => '9888888888',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('dealers', ['id' => $dealerId]);

        // Delete tenant
        DB::table('tenants')->where('id', $tenant->id)->delete();

        $this->assertDatabaseMissing('dealers', ['id' => $dealerId]);
    }

    public function test_bulk_insert_maintains_respective_tenant_ids(): void
    {
        $this->requiresColumn('dealers', 'tenant_id');

        $t1 = $this->createTenant();
        $t2 = $this->createTenant();

        DB::table('dealers')->insert([
            ['tenant_id' => $t1->id, 'firm_name' => 'Dealer A', 'phone' => '9111111111', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $t2->id, 'firm_name' => 'Dealer B', 'phone' => '9222222222', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $countT1 = DB::table('dealers')->where('tenant_id', $t1->id)->count();
        $countT2 = DB::table('dealers')->where('tenant_id', $t2->id)->count();

        $this->assertEquals(1, $countT1);
        $this->assertEquals(1, $countT2);
    }

    public function test_tenant_id_cannot_be_null_in_partitioned_tables(): void
    {
        $this->requiresColumn('dealers', 'tenant_id');

        $this->expectException(\Exception::class);
        DB::table('dealers')->insert([
            'tenant_id'  => null,
            'firm_name'  => 'Null Tenant Dealer',
            'phone'      => '9333333333',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_soft_deletes_on_tenant_preserves_business_records(): void
    {
        $this->requiresColumn('dealers', 'tenant_id');
        $this->requiresColumn('tenants', 'deleted_at');

        $tenant = $this->createTenant();
        $dealerId = DB::table('dealers')->insertGetId([
            'tenant_id'  => $tenant->id,
            'firm_name'  => 'Protected Dealer',
            'phone'      => '9444444444',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Soft delete tenant
        DB::table('tenants')->where('id', $tenant->id)->update(['deleted_at' => now()]);

        // Business record should still exist
        $this->assertDatabaseHas('dealers', ['id' => $dealerId]);
    }
}
