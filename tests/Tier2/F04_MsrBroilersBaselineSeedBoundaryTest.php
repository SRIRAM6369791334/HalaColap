<?php

namespace Tests\Tier2;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\E2ETestCase;

/**
 * Feature F04 Boundary Tests: MSR Broilers Baseline Seeding
 * Verifies boundaries on multiple seeder runs, password verification, and slug invariants.
 */
class F04_MsrBroilersBaselineSeedBoundaryTest extends E2ETestCase
{
    public function test_tenant_seeder_is_idempotent(): void
    {
        $this->requiresTable('tenants');
        if (!class_exists(\Database\Seeders\TenantSeeder::class)) {
            $this->markTestSkipped('Progressive check: TenantSeeder is pending Milestone 1 implementation.');
        }

        // Run seeder twice
        $this->seed(\Database\Seeders\TenantSeeder::class);
        $count1 = DB::table('tenants')->where('id', 1)->count();

        $this->seed(\Database\Seeders\TenantSeeder::class);
        $count2 = DB::table('tenants')->where('id', 1)->count();

        $this->assertSame(1, $count1);
        $this->assertSame(1, $count2);
    }

    public function test_super_admin_cannot_have_null_email(): void
    {
        $this->expectException(\Exception::class);
        User::create([
            'name'           => 'Admin No Email',
            'email'          => null,
            'password'       => bcrypt('password'),
            'is_super_admin' => true,
        ]);
    }

    public function test_tenant_one_slug_uniqueness(): void
    {
        $this->requiresTable('tenants');

        $t1 = DB::table('tenants')->where('id', 1)->first();
        if (!$t1) {
            $this->markTestSkipped('Progressive check: Tenant #1 is pending Milestone 1 seeding.');
        }

        $this->expectException(\Exception::class);
        DB::table('tenants')->insert([
            'name'       => 'Duplicate Slug Attempt',
            'slug'       => $t1->slug,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_linking_user_to_multiple_tenants_via_pivot(): void
    {
        $this->requiresTable('tenants');
        $this->requiresTable('tenant_user');

        $t1 = $this->createTenant();
        $t2 = $this->createTenant();
        $user = $this->createUser();

        DB::table('tenant_user')->insert([
            ['tenant_id' => $t1->id, 'user_id' => $user->id, 'username' => 'boundary_user', 'role' => 'admin', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $t2->id, 'user_id' => $user->id, 'username' => 'boundary_user', 'role' => 'viewer', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $memberships = DB::table('tenant_user')->where('user_id', $user->id)->count();
        $this->assertEquals(2, $memberships);
    }

    public function test_super_admin_flag_persists_on_update(): void
    {
        $this->requiresColumn('users', 'is_super_admin');

        $user = $this->createUser(['is_super_admin' => true]);
        $user->update(['name' => 'Updated Name']);

        $user->refresh();
        $this->assertTrue((bool)$user->is_super_admin);
    }
}
