<?php

namespace Tests\Feature\Security;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Adversarial Super Admin Isolation Test
 *
 * Authored by Challenger Gen3-1 to empirically verify:
 * 1. Gate abilities blacklist evaluation for Tenant Admin
 * 2. HTTP route blocking for SaaS tenant management
 * 3. Super Admin account mutation & deletion protection
 * 4. Parameter tampering resistance
 */
class AdversarialSuperAdminIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $superAdmin;
    protected User $tenant2Admin;
    protected User $tenant2Staff;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Tenant #1 (MSR Broilers - Primary)
        $this->tenant1 = Tenant::where('slug', 'msr-broilers')->first() ?? Tenant::create([
            'name' => 'MSR Broilers',
            'company_name' => 'MSR Broilers',
            'slug' => 'msr-broilers',
            'is_active' => true,
        ]);

        $existingSuperAdmin = User::where('email', 'admin@poultry.com')->first();
        if (! $existingSuperAdmin) {
            $this->superAdmin = User::create([
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'email' => 'admin@poultry.com',
                'password' => bcrypt('super-secret-password'),
                'is_active' => true,
                'is_super_admin' => true,
                'current_tenant_id' => $this->tenant1->id,
            ]);
        } else {
            $existingSuperAdmin->update([
                'is_super_admin' => true,
                'current_tenant_id' => $this->tenant1->id,
            ]);
            $this->superAdmin = $existingSuperAdmin;
        }

        if (! DB::table('tenant_user')->where('tenant_id', $this->tenant1->id)->where('user_id', $this->superAdmin->id)->exists()) {
            DB::table('tenant_user')->insert([
                'tenant_id' => $this->tenant1->id,
                'user_id' => $this->superAdmin->id,
                'username' => 'admin',
                'role' => 'admin',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Setup Tenant #2 (Kongu Broilers - Secondary)
        $this->tenant2 = Tenant::create([
            'name' => 'Kongu Broilers',
            'company_name' => 'Kongu Broilers Pvt Ltd',
            'slug' => 'kongu-broilers-' . uniqid(),
            'is_active' => true,
        ]);

        $this->tenant2Admin = User::create([
            'name' => 'Kongu MD',
            'username' => 'kongu_md',
            'email' => 'md@kongubroilers.com',
            'password' => bcrypt('tenant-password'),
            'is_active' => true,
            'is_super_admin' => false,
            'current_tenant_id' => $this->tenant2->id,
        ]);

        DB::table('tenant_user')->insert([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->tenant2Admin->id,
            'username' => 'tenant2_admin',
            'role' => 'admin',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Setup Tenant #2 Staff
        $this->tenant2Staff = User::create([
            'name' => 'Kongu Staff',
            'username' => 'kongu_staff',
            'email' => 'staff@kongubroilers.com',
            'password' => bcrypt('staff-password'),
            'is_active' => true,
            'is_super_admin' => false,
            'current_tenant_id' => $this->tenant2->id,
        ]);

        DB::table('tenant_user')->insert([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->tenant2Staff->id,
            'username' => 'tenant2_staff',
            'role' => 'staff',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    /**
     * Requirement 2a: Gate abilities blacklist check for Tenant Admin
     */
    public function test_tenant_admin_gate_abilities_all_evaluate_to_false(): void
    {
        $this->actingAs($this->tenant2Admin);
        app(TenantContext::class)->setTenant($this->tenant2);

        $blacklistedAbilities = [
            'manage-platform-tenants',
            'create-tenants',
            'resell-saas',
            'manage-tenants',
            'manage-resellers',
            'manage-platform-settings',
            'system-level-override',
            'platform-admin',
        ];

        foreach ($blacklistedAbilities as $ability) {
            $this->assertFalse(
                Gate::allows($ability),
                "Gate ability '{$ability}' MUST evaluate to false for Tenant Admin."
            );
        }
    }

    /**
     * Requirement 2b: Tenant Admin HTTP requests to /admin/tenants endpoints MUST return 403 Forbidden.
     */
    public function test_tenant_admin_http_requests_to_admin_tenants_return_403(): void
    {
        $this->actingAs($this->tenant2Admin);
        app(TenantContext::class)->setTenant($this->tenant2);

        // GET /admin/tenants
        $this->get('/admin/tenants')->assertStatus(403);

        // GET /admin/tenants/create
        $this->get('/admin/tenants/create')->assertStatus(403);

        // POST /admin/tenants
        $this->post('/admin/tenants', [
            'name' => 'Hostile Takeover Farm',
            'company_name' => 'Hostile Takeover Farm Ltd',
            'admin_name' => 'Attacker',
            'admin_username' => 'attacker',
            'admin_email' => 'attacker@farm.com',
            'admin_password' => 'secret123',
        ])->assertStatus(403);

        // GET /admin/tenants/1/edit
        $this->get('/admin/tenants/' . $this->tenant1->id . '/edit')->assertStatus(403);

        // PUT /admin/tenants/1
        $this->put('/admin/tenants/' . $this->tenant1->id, [
            'name' => 'Compromised MSR Broilers',
        ])->assertStatus(403);

        // POST /admin/tenants/1/toggle-status
        $this->post('/admin/tenants/' . $this->tenant1->id . '/toggle-status')->assertStatus(403);
    }

    /**
     * Requirement 2c (Gate level): Direct Gate check on mutating or deleting Super Admin
     */
    public function test_gate_denies_mutation_or_deletion_of_super_admin(): void
    {
        $this->actingAs($this->tenant2Admin);
        app(TenantContext::class)->setTenant($this->tenant2);

        $this->assertFalse(
            Gate::allows('delete', $this->superAdmin),
            "Gate::allows('delete', \$superAdmin) MUST return false for non-super-admin."
        );

        $this->assertFalse(
            Gate::allows('update', $this->superAdmin),
            "Gate::allows('update', \$superAdmin) MUST return false for non-super-admin."
        );

        $this->assertFalse(
            Gate::allows('toggleStatus', $this->superAdmin),
            "Gate::allows('toggleStatus', \$superAdmin) MUST return false for non-super-admin."
        );
    }

    /**
     * Requirement 2c: HTTP DELETE /admin/users/{superAdmin->id}
     */
    public function test_tenant_admin_cannot_delete_super_admin_via_http_delete(): void
    {
        $this->actingAs($this->tenant2Admin);
        app(TenantContext::class)->setTenant($this->tenant2);

        $deleteResponse = $this->delete('/admin/users/' . $this->superAdmin->id);

        $this->assertTrue(
            User::where('id', $this->superAdmin->id)->exists(),
            'CRITICAL SECURITY FLAW: Super Admin user was deleted by Tenant Admin via DELETE /admin/users/{id}!'
        );
        $deleteResponse->assertStatus(403);
    }

    /**
     * Requirement 2c: HTTP PUT /admin/users/{superAdmin->id}
     */
    public function test_tenant_admin_cannot_mutate_super_admin_via_http_put(): void
    {
        $this->actingAs($this->tenant2Admin);
        app(TenantContext::class)->setTenant($this->tenant2);

        $originalEmail = $this->superAdmin->email;
        $originalName = $this->superAdmin->name;

        $putResponse = $this->put('/admin/users/' . $this->superAdmin->id, [
            'name' => 'Overwritten Admin',
            'email' => 'overwritten@poultry.com',
            'role' => 'staff',
            'password' => 'newpassword123',
        ]);

        $this->superAdmin->refresh();
        $this->assertEquals($originalEmail, $this->superAdmin->email, 'CRITICAL SECURITY FLAW: Super Admin email was mutated by Tenant Admin!');
        $this->assertEquals($originalName, $this->superAdmin->name, 'CRITICAL SECURITY FLAW: Super Admin name was mutated by Tenant Admin!');
        $putResponse->assertStatus(403);
    }

    /**
     * Requirement 2c: HTTP POST /admin/users/{superAdmin->id}/toggle-status
     */
    public function test_tenant_admin_cannot_deactivate_super_admin_via_http_toggle_status(): void
    {
        $this->actingAs($this->tenant2Admin);
        app(TenantContext::class)->setTenant($this->tenant2);

        $postResponse = $this->post('/admin/users/' . $this->superAdmin->id . '/toggle-status');

        $this->superAdmin->refresh();
        $this->assertTrue($this->superAdmin->is_active, 'CRITICAL SECURITY FLAW: Super Admin was deactivated by Tenant Admin!');
        $postResponse->assertStatus(403);
    }

    /**
     * Requirement 2c: Cross-tenant isolation on user mutation/deletion
     */
    public function test_tenant_admin_cannot_delete_or_mutate_other_tenant_users(): void
    {
        // Create a staff user in Tenant #1 (MSR Broilers)
        $t1Staff = User::create([
            'name' => 'T1 Staff',
            'username' => 't1_staff_' . uniqid(),
            'email' => 'staff_' . uniqid() . '@poultry.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_super_admin' => false,
            'current_tenant_id' => $this->tenant1->id,
        ]);
        DB::table('tenant_user')->insert([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $t1Staff->id,
            'username' => 'tenant1_staff',
            'role' => 'staff',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Attempt deletion from Tenant #2 Admin
        $this->actingAs($this->tenant2Admin);
        app(TenantContext::class)->setTenant($this->tenant2);

        $response = $this->delete('/admin/users/' . $t1Staff->id);

        $this->assertTrue(
            User::where('id', $t1Staff->id)->exists(),
            'CRITICAL SECURITY FLAW: Tenant #2 Admin deleted Tenant #1 user via IDOR in UserManagementController!'
        );
        $response->assertStatus(403);
    }

    /**
     * Requirement 3: Parameter tampering on user creation cannot elevate to super admin
     */
    public function test_tenant_admin_cannot_create_super_admin_via_parameter_tampering(): void
    {
        $this->actingAs($this->tenant2Admin);
        app(TenantContext::class)->setTenant($this->tenant2);

        $response = $this->post('/admin/users', [
            'name' => 'Rogue Super Admin',
            'username' => 'rogue_admin_' . uniqid(),
            'email' => 'rogue_' . uniqid() . '@poultry.com',
            'password' => 'password123',
            'role' => 'admin',
            'is_super_admin' => 1,
            'is_super_admin' => true,
            'current_tenant_id' => $this->tenant1->id,
        ]);

        $createdUser = User::where('email', 'like', 'rogue_%@poultry.com')->latest()->first();
        $this->assertNotNull($createdUser);
        $this->assertFalse((bool) $createdUser->is_super_admin, 'User created by Tenant Admin MUST NOT have is_super_admin = true.');
        $this->assertEquals($this->tenant2->id, $createdUser->current_tenant_id, 'User created by Tenant Admin MUST be bound to tenant2, not tenant1.');
    }
}
