<?php

namespace Tests\Feature\Tenancy;

use App\Models\Customer;
use App\Models\Dealer;
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

class TenantAdminEmpiricalChallengeTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected Tenant $tenant3;
    protected User $superAdmin;
    protected User $tenant2Admin;
    protected User $tenant2BillingStaff;
    protected User $tenant2Accountant;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Tenant #1: Primary (MSR Broilers)
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

        // 2. Tenant #2: Secondary (Falcon Farms)
        $this->tenant2 = Tenant::create([
            'name' => 'Falcon Farms',
            'company_name' => 'Falcon Farms Poultry LLP',
            'slug' => 'falcon-farms-' . uniqid(),
            'is_active' => true,
        ]);

        $this->tenant2Admin = User::create([
            'name' => 'Falcon MD',
            'username' => 'falcon_md_' . uniqid(),
            'email' => 'md_' . uniqid() . '@falcon.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_super_admin' => false,
            'current_tenant_id' => $this->tenant2->id,
        ]);

        DB::table('tenant_user')->insert([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->tenant2Admin->id,
            'username' => 'falcon_admin',
            'role' => 'admin',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Tenant #2 Sub-users: Billing Staff (only 'view bills') and Accountant (only 'view expenses', 'view payments')
        $this->tenant2BillingStaff = User::create([
            'name' => 'Falcon Clerk',
            'username' => 'falcon_clerk_' . uniqid(),
            'email' => 'clerk_' . uniqid() . '@falcon.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_super_admin' => false,
            'current_tenant_id' => $this->tenant2->id,
        ]);

        DB::table('tenant_user')->insert([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->tenant2BillingStaff->id,
            'username' => 'falcon_billing',
            'role' => 'staff',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->tenant2Accountant = User::create([
            'name' => 'Falcon Accountant',
            'username' => 'falcon_acct_' . uniqid(),
            'email' => 'acct_' . uniqid() . '@falcon.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_super_admin' => false,
            'current_tenant_id' => $this->tenant2->id,
        ]);

        DB::table('tenant_user')->insert([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->tenant2Accountant->id,
            'username' => 'falcon_accountant',
            'role' => 'accountant',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Setup Tenant #3 for cross-tenant multi-org testing
        $this->tenant3 = Tenant::create([
            'name' => 'demo',
            'company_name' => null, // Test company_name null fallback to name
            'slug' => 'demo-' . uniqid(),
            'is_active' => true,
        ]);

        // Spatie setup for Tenant #2
        if (class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($this->tenant2->id);
            }

            try {
                $pBills = Permission::firstOrCreate(['name' => 'view bills', 'guard_name' => 'web']);
                $pExpenses = Permission::firstOrCreate(['name' => 'view expenses', 'guard_name' => 'web']);
                $pPayments = Permission::firstOrCreate(['name' => 'view payments', 'guard_name' => 'web']);

                $rStaff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
                $rStaff->syncPermissions([$pBills]);
                $this->tenant2BillingStaff->assignRole($rStaff);

                $rAccountant = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
                $rAccountant->syncPermissions([$pExpenses, $pPayments]);
                $this->tenant2Accountant->assignRole($rAccountant);
            } catch (\Throwable $e) {
                // Ignore if Spatie teams already setup
            }
        }
    }

    /**
     * Empirical Test 1: Verify Gate::allows() returns TRUE for Tenant Admin across ALL 12 operational modules.
     */
    public function test_empirical_gate_verification_across_all_12_operational_modules(): void
    {
        $this->actingAs($this->tenant2Admin);
        app(TenantContext::class)->setTenant($this->tenant2);

        $moduleAbilities = [
            // 1. Dealers
            'view dealers', 'create dealers', 'edit dealers', 'delete dealers', 'view dealer ledger', 'view dealer purchases',
            // 2. Customers
            'view customers', 'create customers', 'edit customers', 'delete customers', 'view customer bills', 'view customer payments', 'view customer emis',
            // 3. Dayload Billing
            'view bills', 'create bills',
            // 4. Daily Bills
            'view bills', 'create bills',
            // 5. Weekly Bills
            'view bills', 'create bills',
            // 6. Purchases & Vendors
            'view purchases', 'create purchases', 'edit purchases', 'delete purchases',
            'view vendors', 'create vendors', 'edit vendors', 'delete vendors', 'view vendor purchases',
            // 7. Finance & Payments
            'view payments', 'create payments', 'delete payments', 'view vendor payments', 'view profit dashboard',
            // 8. Expenses
            'view expenses', 'create expenses', 'edit expenses', 'delete expenses',
            // 9. EMIs
            'view emis', 'create emis', 'edit emis', 'delete emis',
            // 10. Cash & Bank Ledger (uses view/create bills permissions)
            'view bills', 'create bills',
            // 11. Reports
            'view reports', 'view profit dashboard',
            // 12. Staff Management
            'manage users', 'view users', 'create users', 'edit users', 'delete users',
            'manage roles', 'view roles', 'manage permissions', 'view activity logs',
        ];

        foreach (array_unique($moduleAbilities) as $ability) {
            $this->assertTrue(
                Gate::allows($ability),
                "Empirical Gate Check Failed: Tenant Admin must have access to '{$ability}'"
            );
        }
    }

    /**
     * Empirical Test 2: Verify real HTTP GET route accessibility (HTTP 200) across all 12 operational modules for Tenant Admin.
     */
    public function test_empirical_http_route_accessibility_across_all_12_operational_modules(): void
    {
        $this->actingAs($this->tenant2Admin);
        app(TenantContext::class)->setTenant($this->tenant2);
        session(['active_tenant_id' => $this->tenant2->id]);

        $routesToTest = [
            // 1. Dealers
            'Dealers Index' => route('masters.dealers.index'),
            'Dealers Create' => route('masters.dealers.create'),

            // 2. Customers
            'Customers Index' => route('masters.customers.index'),
            'Customers Create' => route('masters.customers.create'),

            // 3. Dayload Billing
            'Dayload Billing Index' => route('billing.day-load.index'),
            'Dayload Rates Form' => route('billing.day-load.vendor-rates'),

            // 4. Daily Bills
            'Daily Bills Index' => route('billing.daily.index'),
            'Daily Bills GST' => route('billing.daily.gst'),

            // 5. Weekly Bills
            'Weekly Bills Index' => route('billing.weekly.index'),
            'Weekly Bulk View' => route('billing.weekly.bulk'),

            // 6. Purchases
            'Purchases Entry' => route('purchases.entry'),
            'Purchase Invoices' => route('purchases.invoices'),

            // 7. Finance
            'Dealer Payments' => route('payments.dealers.index'),
            'Dealer Payments Create' => route('payments.dealers.create'),
            'Vendor Payments' => route('payments.vendors.index'),
            'Investments' => route('billing.investments.index'),
            'Profit & Loss' => route('profit.index'),

            // 8. Expenses
            'Expenses Index' => route('expenses.index'),
            'Expenses Categories' => route('expenses.categories'),

            // 9. EMIs
            'EMIs Index' => route('expenses.emis.index'),
            'EMIs Create' => route('expenses.emis.create'),
            'EMIs Alerts' => route('expenses.emis.alerts'),

            // 10. Cash & Bank Ledger
            'Cash & Bank Ledger Index' => route('billing.cash-bank-ledger.index'),

            // 11. Reports
            'Reports Index' => route('reports.index'),

            // 12. Staff Management
            'User Management Index' => route('admin.users.index'),
            'Role Management Index' => route('admin.roles.index'),
            'Permission Management Index' => route('admin.permissions.index'),
        ];

        foreach ($routesToTest as $moduleName => $url) {
            $response = $this->get($url);
            $this->assertNotEquals(
                403,
                $response->status(),
                "Tenant Admin was forbidden (HTTP 403) from operational route '{$moduleName}' at URL: {$url}"
            );
            $this->assertEquals(
                200,
                $response->status(),
                "Operational route '{$moduleName}' at URL: {$url} did not return HTTP 200. Got: {$response->status()}"
            );
        }
    }

    /**
     * Empirical Test 3: Verify Tenant Admin is strictly blocked from ALL Platform Admin routes with HTTP 403.
     */
    public function test_empirical_platform_admin_strict_lockout_across_all_verbs(): void
    {
        $this->actingAs($this->tenant2Admin);
        app(TenantContext::class)->setTenant($this->tenant2);
        session(['active_tenant_id' => $this->tenant2->id]);

        // GET index
        $this->get(route('admin.tenants.index'))->assertStatus(403);

        // GET create
        $this->get(route('admin.tenants.create'))->assertStatus(403);

        // POST store
        $this->post(route('admin.tenants.store'), [
            'name' => 'Malicious Hijack Farm',
            'slug' => 'malicious-farm',
        ])->assertStatus(403);

        // GET edit
        $this->get(route('admin.tenants.edit', $this->tenant1->id))->assertStatus(403);

        // PUT update
        $this->put(route('admin.tenants.update', $this->tenant1->id), [
            'name' => 'Defaced Farm',
        ])->assertStatus(403);

        // POST toggle-status
        $this->post(route('admin.tenants.toggle-status', $this->tenant1->id))->assertStatus(403);

        // DELETE destroy
        $this->delete(route('admin.tenants.destroy', $this->tenant1->id))->assertStatus(403);

        // Gate blacklisted abilities
        $blacklistedAbilities = [
            'manage-platform-tenants', 'create-tenants', 'resell-saas', 'manage-tenants',
            'manage-resellers', 'manage-platform-settings', 'system-level-override', 'platform-admin',
        ];
        foreach ($blacklistedAbilities as $bAbility) {
            $this->assertFalse(
                Gate::allows($bAbility),
                "Tenant Admin must be denied platform ability '{$bAbility}'"
            );
        }
    }

    /**
     * Empirical Test 4: Dynamic sidebar branding across multiple tenant configurations.
     */
    public function test_empirical_dynamic_sidebar_branding_configurations(): void
    {
        // 1. Tenant #1 (Primary - MSR Broilers) viewed by Super Admin
        $this->actingAs($this->superAdmin);
        app(TenantContext::class)->setTenant($this->tenant1);
        session(['active_tenant_id' => $this->tenant1->id]);

        $htmlT1 = view('partials.sidebar')->render();
        $this->assertStringContainsString('MSR Broilers', $htmlT1);
        $this->assertStringContainsString('Business Accounts (SaaS)', $htmlT1);
        $this->assertStringContainsString(route('admin.tenants.index'), $htmlT1);

        // 2. Tenant #2 (Falcon Farms with custom company_name) viewed by Tenant #2 Admin
        $this->actingAs($this->tenant2Admin);
        app(TenantContext::class)->setTenant($this->tenant2);
        session(['active_tenant_id' => $this->tenant2->id]);

        $htmlT2 = view('partials.sidebar')->render();
        $this->assertStringContainsString('Falcon Farms Poultry LLP', $htmlT2);
        $this->assertStringContainsString('title="Falcon Farms Poultry LLP"', $htmlT2);
        $this->assertStringNotContainsString('title="MSR BROILERS"', $htmlT2);
        // Business Accounts (SaaS) MUST be completely absent
        $this->assertStringNotContainsString('Business Accounts (SaaS)', $htmlT2);
        $this->assertStringNotContainsString(route('admin.tenants.index'), $htmlT2);

        // 3. Tenant #3 ('demo' with company_name = null) -> fallback to name
        app(TenantContext::class)->setTenant($this->tenant3);
        session(['active_tenant_id' => $this->tenant3->id]);

        $htmlT3 = view('partials.sidebar')->render();
        $this->assertStringContainsString('demo', $htmlT3);
        $this->assertStringNotContainsString('title="MSR BROILERS"', $htmlT3);
        $this->assertStringNotContainsString('Business Accounts (SaaS)', $htmlT3);

        // 4. HTML entity and special character escaping check
        $specialTenant = Tenant::create([
            'name' => 'Special Farm',
            'company_name' => 'Kongu & Sons <Poultry>',
            'slug' => 'special-farm-' . uniqid(),
            'is_active' => true,
        ]);
        app(TenantContext::class)->setTenant($specialTenant);
        session(['active_tenant_id' => $specialTenant->id]);

        $htmlSpecial = view('partials.sidebar')->render();
        // Blade escaping must encode & and < >
        $this->assertStringContainsString('Kongu &amp; Sons &lt;Poultry&gt;', $htmlSpecial);
        $this->assertStringNotContainsString('<Poultry>', $htmlSpecial);
    }

    /**
     * Empirical Test 5: Sub-users (staff, accountant) are strictly constrained and obey granular Spatie permissions.
     */
    public function test_empirical_sub_users_containment_and_granular_spatie_permissions(): void
    {
        // 1. Billing Staff (only 'view bills')
        $this->actingAs($this->tenant2BillingStaff);
        app(TenantContext::class)->setTenant($this->tenant2);
        session(['active_tenant_id' => $this->tenant2->id]);

        $this->assertTrue(Gate::allows('view bills'));
        $this->assertFalse(Gate::allows('view expenses'));
        $this->assertFalse(Gate::allows('manage users'));
        $this->assertFalse(Gate::allows('create dealers'));
        $this->assertFalse(Gate::allows('manage-platform-tenants'));

        // Route level: Allowed
        $this->get(route('billing.day-load.index'))->assertStatus(200);

        // Route level: Forbidden
        $this->get(route('admin.users.index'))->assertStatus(403);
        $this->get(route('admin.roles.index'))->assertStatus(403);
        $this->get(route('expenses.index'))->assertStatus(403);
        $this->get(route('admin.tenants.index'))->assertStatus(403);

        // Sidebar level
        $staffHtml = view('partials.sidebar')->render();
        $this->assertStringContainsString('Dayload Billing', $staffHtml);
        $this->assertStringNotContainsString('User Management', $staffHtml);
        $this->assertStringNotContainsString('Business Accounts (SaaS)', $staffHtml);

        // 2. Accountant (only 'view expenses', 'view payments')
        $this->actingAs($this->tenant2Accountant);
        app(TenantContext::class)->setTenant($this->tenant2);
        session(['active_tenant_id' => $this->tenant2->id]);

        $this->assertTrue(Gate::allows('view expenses'));
        $this->assertTrue(Gate::allows('view payments'));
        $this->assertFalse(Gate::allows('view bills'));
        $this->assertFalse(Gate::allows('manage users'));

        // Route level
        $this->get(route('expenses.index'))->assertStatus(200);
        $this->get(route('payments.dealers.index'))->assertStatus(200);
        $this->get(route('billing.day-load.index'))->assertStatus(403);
        $this->get(route('admin.users.index'))->assertStatus(403);

        // 3. Inactive tenant_user membership: user is deactivated in business
        DB::table('tenant_user')->where('tenant_id', $this->tenant2->id)->where('user_id', $this->tenant2Admin->id)->update([
            'is_active' => false,
        ]);
        $this->tenant2Admin->_cached_is_tenant_admin = null;

        $this->assertFalse(
            $this->tenant2Admin->isTenantAdmin($this->tenant2->id),
            "Deactivated tenant admin must NOT evaluate as isTenantAdmin"
        );
    }

    /**
     * Empirical Test 6: Cross-tenant data isolation and Zero IDOR protection.
     */
    public function test_empirical_cross_tenant_data_isolation_and_zero_idor(): void
    {
        // 1. Create a Dealer under Tenant #1
        $dealerTenant1 = Dealer::create([
            'tenant_id' => $this->tenant1->id,
            'firm_name' => 'Dealer MSR Exclusive',
            'phone' => '9999988888',
        ]);

        // 2. Act as Tenant #2 Admin
        $this->actingAs($this->tenant2Admin);
        app(TenantContext::class)->setTenant($this->tenant2);
        session(['active_tenant_id' => $this->tenant2->id]);

        // Cross-tenant lookup: GET /masters/dealers/{id} MUST be 404 Not Found (Zero IDOR)
        $this->get(route('masters.dealers.show', $dealerTenant1->id))->assertStatus(404);

        // Cross-tenant edit: GET /masters/dealers/{id}/edit MUST be 404 Not Found
        $this->get(route('masters.dealers.edit', $dealerTenant1->id))->assertStatus(404);

        // Cross-tenant update: PUT /masters/dealers/{id} MUST be 404 Not Found
        $this->put(route('masters.dealers.update', $dealerTenant1->id), [
            'firm_name' => 'Hijacked Dealer',
        ])->assertStatus(404);

        // Cross-tenant delete: DELETE /masters/dealers/{id} MUST be 404 Not Found
        $this->delete(route('masters.dealers.destroy', $dealerTenant1->id))->assertStatus(404);

        // 3. Super Admin account protection against mutation/deletion
        $this->assertFalse(
            Gate::allows('delete', $this->superAdmin),
            "Tenant Admin must be barred from deleting Super Admin user"
        );
        $this->assertFalse(
            Gate::allows('update', $this->superAdmin),
            "Tenant Admin must be barred from updating Super Admin user"
        );
        $this->assertFalse(
            Gate::allows('toggleStatus', $this->superAdmin),
            "Tenant Admin must be barred from toggling Super Admin status"
        );
    }

    /**
     * Empirical Test 7: Multi-tenant memoization invalidation across tenant context switching.
     */
    public function test_empirical_memoization_invalidation_across_tenant_context_switch(): void
    {
        // Tenant 2 MD is admin of Tenant 2
        app(TenantContext::class)->setTenant($this->tenant2);
        $this->assertTrue($this->tenant2Admin->isTenantAdmin($this->tenant2->id));

        // In Tenant 3, Tenant 2 MD has NO membership or role
        app(TenantContext::class)->setTenant($this->tenant3);
        $this->assertFalse(
            $this->tenant2Admin->isTenantAdmin($this->tenant3->id),
            "Tenant 2 MD must NOT be an admin of Tenant 3"
        );

        // Switching back to Tenant 2
        $this->assertTrue(
            $this->tenant2Admin->isTenantAdmin($this->tenant2->id),
            "Tenant 2 MD must be an admin of Tenant 2"
        );
    }
}
