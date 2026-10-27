<?php

namespace Tests\Feature\Tenancy;

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

class TenantAdminAccessAndSidebarTest extends TestCase
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

        // 1. Setup or retrieve Tenant #1 (Primary - MSR Broilers)
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
                'password' => bcrypt('password'),
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

        // 2. Setup Tenant #2 (Secondary - Kongu Broilers)
        $this->tenant2 = Tenant::create([
            'name' => 'Kongu Broilers',
            'company_name' => 'Kongu Broilers Pvt Ltd',
            'slug' => 'kongu-broilers-' . uniqid(),
            'is_active' => true,
        ]);

        $this->tenant2Admin = User::create([
            'name' => 'Kongu MD',
            'username' => 'kongu_md_' . uniqid(),
            'email' => 'md_' . uniqid() . '@kongubroilers.com',
            'password' => bcrypt('password'),
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

        // 3. Setup Tenant #2 Sub-user (Staff with limited permissions)
        $this->tenant2Staff = User::create([
            'name' => 'Kongu Billing Clerk',
            'username' => 'kongu_clerk_' . uniqid(),
            'email' => 'clerk_' . uniqid() . '@kongubroilers.com',
            'password' => bcrypt('password'),
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

        // 4. Configure Spatie team roles and permissions if tables exist
        if (class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($this->tenant2->id);
            }

            try {
                $viewBills = Permission::firstOrCreate(['name' => 'view bills', 'guard_name' => 'web']);
                $manageUsers = Permission::firstOrCreate(['name' => 'manage users', 'guard_name' => 'web']);
                $viewReports = Permission::firstOrCreate(['name' => 'view reports', 'guard_name' => 'web']);

                $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
                $staffRole->syncPermissions([$viewBills]);

                $this->tenant2Staff->assignRole($staffRole);
            } catch (\Throwable $e) {
                // Ignore if Spatie schema differences in test
            }
        }
    }

    /**
     * Test 1: Tenant Admin has 100% full operational access across all operational modules.
     */
    public function test_tenant_admin_has_full_operational_access_across_all_operational_modules(): void
    {
        $this->actingAs($this->tenant2Admin);
        app(TenantContext::class)->setTenant($this->tenant2);

        $operationalAbilities = [
            // Dealers & Customers
            'view dealers', 'create dealers', 'edit dealers', 'delete dealers', 'view dealer ledger',
            'view customers', 'create customers', 'edit customers', 'delete customers',
            // Dayload Billing & Bills
            'view bills', 'create bills', 'edit bills', 'delete bills',
            // Purchases & Vendors
            'view purchases', 'create purchases', 'edit purchases', 'delete purchases',
            'view vendors', 'create vendors', 'edit vendors', 'delete vendors',
            // Finance, Ledger & Payments
            'view payments', 'create payments', 'delete payments', 'view vendor payments',
            // Expenses & EMIs
            'view expenses', 'create expenses', 'edit expenses', 'delete expenses',
            'view emis', 'create emis', 'edit emis', 'delete emis',
            // Reports & Analytics
            'view reports', 'view profit dashboard',
            // Staff & User Management within Tenant
            'manage users', 'view users', 'create users', 'edit users', 'delete users',
            'manage roles', 'view roles', 'manage permissions', 'view activity logs',
            // Stock & Logistics
            'view stock', 'create stock', 'view analytics',
            'view routes', 'create routes', 'view vehicles', 'create vehicles', 'view drivers', 'create drivers',
        ];

        foreach ($operationalAbilities as $ability) {
            $this->assertTrue(
                Gate::allows($ability),
                "Tenant Admin must be granted operational access for ability: {$ability}"
            );
        }
    }

    /**
     * Test 2: Tenant Admin is strictly denied platform Super Admin abilities.
     */
    public function test_tenant_admin_is_strictly_denied_platform_super_admin_abilities(): void
    {
        $this->actingAs($this->tenant2Admin);
        app(TenantContext::class)->setTenant($this->tenant2);

        $platformAbilities = [
            'manage-platform-tenants',
            'create-tenants',
            'resell-saas',
            'manage-tenants',
            'manage-resellers',
            'manage-platform-settings',
            'system-level-override',
            'platform-admin',
        ];

        foreach ($platformAbilities as $ability) {
            $this->assertFalse(
                Gate::allows($ability),
                "Tenant Admin must be strictly prohibited from platform ability: {$ability}"
            );
        }
    }

    /**
     * Test 3: Platform Super Admin retains universal bypass across all abilities.
     */
    public function test_platform_super_admin_retains_universal_bypass(): void
    {
        $this->actingAs($this->superAdmin);
        app(TenantContext::class)->setTenant($this->tenant1);

        $this->assertTrue(Gate::allows('manage-platform-tenants'));
        $this->assertTrue(Gate::allows('create-tenants'));
        $this->assertTrue(Gate::allows('resell-saas'));
        $this->assertTrue(Gate::allows('system-level-override'));
        $this->assertTrue(Gate::allows('view dealers'));
        $this->assertTrue(Gate::allows('arbitrary-platform-gate-xyz'));
    }

    /**
     * Test 4: Tenant Admin is strictly blocked from all platform admin HTTP routes with 403 Forbidden.
     */
    public function test_tenant_admin_is_strictly_blocked_from_platform_admin_http_routes(): void
    {
        $this->actingAs($this->tenant2Admin);
        app(TenantContext::class)->setTenant($this->tenant2);

        // GET /admin/tenants (index)
        $this->get(route('admin.tenants.index'))->assertStatus(403);

        // GET /admin/tenants/create
        $this->get(route('admin.tenants.create'))->assertStatus(403);

        // POST /admin/tenants (store)
        $this->post(route('admin.tenants.store'), [
            'name' => 'Unauthorized Farm',
            'slug' => 'unauthorized-farm',
        ])->assertStatus(403);

        // GET /admin/tenants/{id}/edit (edit)
        $this->get(route('admin.tenants.edit', $this->tenant1->id))->assertStatus(403);

        // PUT /admin/tenants/{id} (update)
        $this->put(route('admin.tenants.update', $this->tenant1->id), [
            'name' => 'Tampered Name',
        ])->assertStatus(403);

        // POST /admin/tenants/{id}/toggle-status
        $this->post(route('admin.tenants.toggle-status', $this->tenant1->id))->assertStatus(403);
    }

    /**
     * Test 5: Sidebar dynamically renders tenant company name for Tenant #2 and suppresses SaaS link.
     */
    public function test_sidebar_dynamically_renders_tenant_company_name_and_hides_saas_link(): void
    {
        // 1. Render sidebar for Tenant #2 Admin
        $this->actingAs($this->tenant2Admin);
        app(TenantContext::class)->setTenant($this->tenant2);

        $html = view('partials.sidebar')->render();

        // Must display Tenant #2 company name
        $this->assertStringContainsString('Kongu Broilers Pvt Ltd', $html);
        $this->assertStringNotContainsString('title="MSR BROILERS"', $html);

        // Must NOT render SaaS platform management link
        $this->assertStringNotContainsString('Business Accounts (SaaS)', $html);
        $this->assertStringNotContainsString(route('admin.tenants.index'), $html);

        // Must render operational navigation items
        $this->assertStringContainsString('Dayload Billing', $html);
        $this->assertStringContainsString('Customers', $html);
        $this->assertStringContainsString('Dealers', $html);
        $this->assertStringContainsString('Vendors', $html);
        $this->assertStringContainsString('Daily Billing Generate', $html);
        $this->assertStringContainsString('Weekly Billing Generate', $html);
        $this->assertStringContainsString('Purchases', $html);
        $this->assertStringContainsString('Dealer Payments', $html);
        $this->assertStringContainsString('Vendor Payments', $html);
        $this->assertStringContainsString('Expenses', $html);
        $this->assertStringContainsString('EMI Records', $html);
        $this->assertStringContainsString('Profit &amp; Loss', $html);
        $this->assertStringContainsString('Reports', $html);
        $this->assertStringContainsString('User Management', $html);
        $this->assertStringContainsString('Role Management', $html);

        // Residual hardcoding must be purged
        $this->assertStringNotContainsString('admin@MSR BROILERS.com', $html);
    }

    /**
     * Test 6: Sidebar for Platform Super Admin displays SaaS management link.
     */
    public function test_sidebar_renders_saas_link_for_platform_super_admin(): void
    {
        $this->actingAs($this->superAdmin);
        app(TenantContext::class)->setTenant($this->tenant1);

        $html = view('partials.sidebar')->render();

        $this->assertStringContainsString('MSR Broilers', $html);
        $this->assertStringContainsString('Business Accounts (SaaS)', $html);
        $this->assertStringContainsString(route('admin.tenants.index'), $html);
    }

    /**
     * Test 7: Sub-users still strictly obey granular Spatie permissions.
     */
    public function test_sub_users_strictly_obey_granular_spatie_permissions(): void
    {
        $this->actingAs($this->tenant2Staff);
        app(TenantContext::class)->setTenant($this->tenant2);

        // The staff user has 'view bills' assigned
        $this->assertTrue(Gate::allows('view bills'));

        // The staff user does NOT have 'manage users' or 'delete bills'
        $this->assertFalse(Gate::allows('delete bills'));
        $this->assertFalse(Gate::allows('manage users'));
        $this->assertFalse(Gate::allows('manage-platform-tenants'));

        // In sidebar, staff user sees Dayload Billing, but Administration section is hidden
        $html = view('partials.sidebar')->render();
        $this->assertStringContainsString('Dayload Billing', $html);
        $this->assertStringNotContainsString('Business Accounts (SaaS)', $html);
        $this->assertStringNotContainsString('User Management', $html);
    }

    /**
     * Test 8: User helper isTenantAdmin() correctly checks memoization and cross-tenant boundaries.
     */
    public function test_user_is_tenant_admin_helper_method_and_memoization(): void
    {
        app(TenantContext::class)->setTenant($this->tenant2);

        // 1. Initial evaluation
        $this->assertNull($this->tenant2Admin->_cached_is_tenant_admin);
        $isAdmin = $this->tenant2Admin->isTenantAdmin();
        $this->assertTrue($isAdmin);
        $this->assertTrue($this->tenant2Admin->_cached_is_tenant_admin);

        // 2. Memoized evaluation (does not query database)
        DB::enableQueryLog();
        $secondCheck = $this->tenant2Admin->isTenantAdmin();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertTrue($secondCheck);
        $this->assertEmpty($queries, 'Subsequent isTenantAdmin() calls must use memoized result with 0 queries.');

        // 3. Sub-user is not tenant admin
        $this->assertFalse($this->tenant2Staff->isTenantAdmin());

        // 4. Super Admin is always tenant admin
        $this->assertTrue($this->superAdmin->isTenantAdmin());

        // 5. Cross-tenant check: Tenant #2 admin is NOT admin of Tenant #1
        $this->assertFalse($this->tenant2Admin->isTenantAdmin($this->tenant1->id));
    }
}
