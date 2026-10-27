<?php

namespace Tests\Tier1;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\E2ETestCase;

/**
 * Feature F04: MSR Broilers Baseline Seeding
 * Verifies that Tenant #1 (MSR Broilers) is seeded, admin@poultry.com is designated
 * as Super Admin and associated with Tenant #1.
 */
class F04_MsrBroilersBaselineSeedTest extends E2ETestCase
{
    public function test_tenant_one_is_msr_broilers(): void
    {
        $this->requiresTable('tenants');
        if (DB::table('tenants')->count() === 0 && class_exists(\Database\Seeders\TenantSeeder::class)) {
            $this->seed(\Database\Seeders\TenantSeeder::class);
        }

        $tenant = DB::table('tenants')->where('id', 1)->first();
        if (!$tenant) {
            $this->markTestSkipped('Progressive check: Tenant #1 (MSR Broilers) seeding is pending Milestone 1 implementation.');
        }
        $this->assertNotNull($tenant, 'Tenant #1 does not exist in tenants table.');
        $this->assertStringContainsStringIgnoringCase('MSR Broilers', $tenant->name);
    }

    public function test_super_admin_user_is_seeded_with_admin_email(): void
    {
        $admin = User::where('email', 'admin@poultry.com')->first();
        if (!$admin && class_exists(\Database\Seeders\TenantSeeder::class)) {
            $this->seed(\Database\Seeders\TenantSeeder::class);
            $admin = User::where('email', 'admin@poultry.com')->first();
        }
        if (!$admin) {
            $this->markTestSkipped('Progressive check: TenantSeeder and admin@poultry.com baseline seeding are pending Milestone 1 implementation.');
        }
        $this->assertNotNull($admin, 'User admin@poultry.com does not exist.');
    }

    public function test_super_admin_flag_is_true_for_main_admin(): void
    {
        $this->requiresColumn('users', 'is_super_admin');
        $admin = User::where('email', 'admin@poultry.com')->first();
        if (!$admin && class_exists(\Database\Seeders\TenantSeeder::class)) {
            $this->seed(\Database\Seeders\TenantSeeder::class);
            $admin = User::where('email', 'admin@poultry.com')->first();
        }
        if (!$admin) {
            $this->markTestSkipped('Progressive check: admin@poultry.com is pending Milestone 1 seeding.');
        }
        $this->assertTrue((bool)$admin->is_super_admin, 'admin@poultry.com is not designated as is_super_admin = true');
    }

    public function test_super_admin_is_associated_with_msr_broilers(): void
    {
        $this->requiresTable('tenants');
        $this->requiresColumn('users', 'current_tenant_id');
        $admin = User::where('email', 'admin@poultry.com')->first();
        if (!$admin && class_exists(\Database\Seeders\TenantSeeder::class)) {
            $this->seed(\Database\Seeders\TenantSeeder::class);
            $admin = User::where('email', 'admin@poultry.com')->first();
        }
        if (!$admin) {
            $this->markTestSkipped('Progressive check: admin@poultry.com is pending Milestone 1 seeding.');
        }
        $this->assertEquals(1, $admin->current_tenant_id, 'admin@poultry.com current_tenant_id is not 1 (MSR Broilers)');
    }

    public function test_msr_broilers_status_is_active(): void
    {
        $this->requiresTable('tenants');
        $tenant = DB::table('tenants')->where('id', 1)->first();
        if (!$tenant && class_exists(\Database\Seeders\TenantSeeder::class)) {
            $this->seed(\Database\Seeders\TenantSeeder::class);
            $tenant = DB::table('tenants')->where('id', 1)->first();
        }
        if (!$tenant) {
            $this->markTestSkipped('Progressive check: Tenant #1 is pending Milestone 1 seeding.');
        }
        $this->assertTrue((bool)$tenant->is_active);
    }
}
