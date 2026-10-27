<?php

namespace Tests\Feature\Security;

use App\Models\Customer;
use App\Models\DailyBill;
use App\Models\DailyBillItem;
use App\Models\DayLoadBatch;
use App\Models\DayLoadEntry;
use App\Models\Dealer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Tenancy\TenantContext;
use Database\Seeders\MultiTenantVerificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Full-Spectrum Security, Super Admin Isolation & Cross-Tenant IDOR Test
 *
 * Covers:
 * 1. Staff user creation under tenant with Spatie team permissions, duplicate email/username rejection
 * 2. Platform Super Admin protection: HTTP 403 when non-super-admin attempts PUT/DELETE/toggleStatus on admin@poultry.com
 * 3. Tenant Management barrier: HTTP 403 when non-super-admin attempts GET/POST/PUT/DELETE on /admin/tenants
 * 4. Cross-Tenant IDOR: GET, PUT, DELETE on /masters/dealers/{other_id}, /masters/vendors/{other_id},
 *    /masters/customers/{other_id}, /billing/day-load/{other_id}, /billing/daily/{other_id} return HTTP 404
 * 5. Cross-Tenant foreign payload injection strictly rejected
 */
class FullSpectrumSecurityAndIdorTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $superAdmin;
    protected User $t1Admin;
    protected User $t2Admin;

    protected Dealer $t2Dealer;
    protected Vendor $t2Vendor;
    protected Customer $t2Customer;
    protected DailyBill $t2DailyBill;
    protected DayLoadEntry $t2DayLoadEntry;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Ensure Super Admin exists
        $primaryTenant = Tenant::where('slug', 'msr-broilers')->first() ?? Tenant::create([
            'name'         => 'MSR Broilers',
            'company_name' => 'MSR Broilers',
            'slug'         => 'msr-broilers',
            'is_active'    => true,
            'currency'     => 'INR',
        ]);

        $this->superAdmin = User::where('email', 'admin@poultry.com')->first() ?? User::create([
            'name'              => 'Super Admin',
            'username'          => 'superadmin',
            'email'             => 'admin@poultry.com',
            'password'          => bcrypt('Admin@1234'),
            'is_active'         => true,
            'is_super_admin'    => true,
            'current_tenant_id' => $primaryTenant->id,
        ]);

        $this->superAdmin->update([
            'is_super_admin'    => true,
            'current_tenant_id' => $primaryTenant->id,
        ]);

        if (! DB::table('tenant_user')->where('tenant_id', $primaryTenant->id)->where('user_id', $this->superAdmin->id)->exists()) {
            DB::table('tenant_user')->insert([
                'tenant_id'  => $primaryTenant->id,
                'user_id'    => $this->superAdmin->id,
                'username'   => 'admin',
                'role'       => 'admin',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Seed Multi-Tenant baseline
        $this->seed(MultiTenantVerificationSeeder::class);

        // 3. Resolve Tenants and Users
        $this->tenant1 = Tenant::where('slug', 'sk-farm')->firstOrFail();
        $this->tenant2 = Tenant::where('slug', 'annai-poultry')->firstOrFail();

        $this->t1Admin = User::where('email', 'sk_admin@skfarm.com')->firstOrFail();
        $this->t2Admin = User::where('email', 'annai_admin@annai.com')->firstOrFail();

        // 4. Create Tenant 2 Fixtures (targets for cross-tenant attack)
        app(TenantContext::class)->setTenant($this->tenant2);

        $this->t2Dealer = Dealer::where('firm_name', 'Erode Central Chicken')->firstOrFail();
        $this->t2Vendor = Vendor::where('firm_name', 'Namakkal Chicks Hatcheries')->firstOrFail();
        $this->t2DayLoadEntry = DayLoadEntry::where('dealer_id', $this->t2Dealer->id)->firstOrFail();

        $this->t2Customer = Customer::create([
            'name'    => 'Kongu Retail Customer',
            'phone'   => '9843299887',
            'type'    => 'Retail',
            'balance' => 4500.00,
        ]);

        $this->t2DailyBill = DailyBill::create([
            'customer_id'    => $this->t2Customer->id,
            'dealer_id'      => null,
            'date'           => now()->toDateString(),
            'invoice_no'     => 'INV-T2-001',
            'amount'         => 15000.00,
            'gst_percentage' => 0.00,
            'gst_amount'     => 0.00,
            'net_amount'     => 15000.00,
            'status'         => 'Pending',
            'payment_mode'   => 'Cash',
        ]);

        DailyBillItem::create([
            'daily_bill_id' => $this->t2DailyBill->id,
            'item_name'     => 'Live Broiler Bird',
            'quantity_kg'   => 120.00,
            'rate_per_kg'   => 125.00,
            'tax_amount'    => 0.00,
            'total_amount'  => 15000.00,
            'unit'          => 'kg',
        ]);

        // 5. Reset to Tenant 1 context for attack simulation
        $this->actingAs($this->t1Admin);
        session(['active_tenant_id' => $this->tenant1->id]);
        app(TenantContext::class)->setTenant($this->tenant1);
        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($this->tenant1->id);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // =========================================================================
    // 1. STAFF USER CREATION UNDER TENANT & PERMISSIONS
    // =========================================================================

    public function test_staff_user_creation_with_spatie_team_permissions_and_duplicate_rejection(): void
    {
        // 1. Positive Staff User Creation under Tenant 1
        $staffPayload = [
            'name'     => 'Dinesh Kumar Staff',
            'username' => 'dinesh_staff',
            'email'    => 'dinesh@skfarm.com',
            'password' => 'SecurePass@123',
            'role'     => 'accountant',
        ];

        $response = $this->post(route('admin.users.store'), $staffPayload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $createdUser = User::where('email', 'dinesh@skfarm.com')->firstOrFail();

        // A. User belongs to Tenant 1
        $this->assertEquals($this->tenant1->id, $createdUser->current_tenant_id);
        $this->assertFalse((bool) $createdUser->is_super_admin);

        // B. Pivot table tenant_user links user with role and active status
        $this->assertDatabaseHas('tenant_user', [
            'tenant_id' => $this->tenant1->id,
            'user_id'   => $createdUser->id,
            'role'      => 'accountant',
            'is_active' => true,
        ]);

        // C. Spatie Role assigned
        $this->assertTrue($createdUser->hasRole('accountant'));

        // 2. Negative: Duplicate Email Rejection
        $dupEmailResponse = $this->post(route('admin.users.store'), [
            'name'     => 'Another Staff',
            'username' => 'another_staff',
            'email'    => 'dinesh@skfarm.com', // Duplicate email
            'password' => 'Pass@123',
            'role'     => 'accountant',
        ]);
        $dupEmailResponse->assertSessionHasErrors(['email']);

        // 3. Negative: Duplicate Username Rejection
        $dupUserResponse = $this->post(route('admin.users.store'), [
            'name'     => 'Another Staff 2',
            'username' => 'dinesh_staff', // Duplicate username
            'email'    => 'different@skfarm.com',
            'password' => 'Pass@123',
            'role'     => 'accountant',
        ]);
        $dupUserResponse->assertSessionHasErrors(['username']);
    }

    // =========================================================================
    // 2. PLATFORM SUPER ADMIN PROTECTION (HTTP 403)
    // =========================================================================

    public function test_platform_super_admin_mutation_and_deletion_blocked_with_http_403(): void
    {
        // Verify acting user is non-super-admin
        $this->assertFalse((bool) $this->t1Admin->is_super_admin);
        $superAdminId = $this->superAdmin->id;

        // 1. Attempt PUT /admin/users/{superAdminId} -> Must strictly return HTTP 403 Forbidden
        $putResponse = $this->put(route('admin.users.update', $superAdminId), [
            'name'  => 'Hacked Super Admin',
            'email' => 'hacked@poultry.com',
            'role'  => 'delivery_staff',
        ]);
        $putResponse->assertStatus(403);

        // 2. Attempt DELETE /admin/users/{superAdminId} -> Must strictly return HTTP 403 Forbidden
        $delResponse = $this->delete(route('admin.users.destroy', $superAdminId));
        $delResponse->assertStatus(403);

        // 3. Attempt POST /admin/users/{superAdminId}/toggle-status -> Must strictly return HTTP 403 Forbidden
        $toggleResponse = $this->post(route('admin.users.toggle-status', $superAdminId));
        $toggleResponse->assertStatus(403);

        // Verify Super Admin account integrity preserved
        $freshSuperAdmin = $this->superAdmin->fresh();
        $this->assertEquals('Super Admin', $freshSuperAdmin->name);
        $this->assertEquals('admin@poultry.com', $freshSuperAdmin->email);
        $this->assertTrue((bool) $freshSuperAdmin->is_active);
        $this->assertTrue((bool) $freshSuperAdmin->is_super_admin);
        $this->assertDatabaseHas('users', ['id' => $superAdminId]);
    }

    // =========================================================================
    // 3. TENANT MANAGEMENT BARRIER (HTTP 403)
    // =========================================================================

    public function test_tenant_management_routes_strictly_return_http_403_for_non_super_admin(): void
    {
        $this->assertFalse((bool) $this->t1Admin->is_super_admin);

        // 1. GET /admin/tenants -> HTTP 403
        $this->get('/admin/tenants')->assertStatus(403);

        // 2. GET /admin/tenants/create -> HTTP 403
        $this->get('/admin/tenants/create')->assertStatus(403);

        // 3. POST /admin/tenants -> HTTP 403
        $this->post('/admin/tenants', [
            'name'           => 'Rogue Tenant Farm',
            'slug'           => 'rogue-tenant',
            'admin_name'     => 'Rogue Admin',
            'admin_username' => 'rogue_admin',
            'admin_email'    => 'rogue@farm.com',
            'admin_password' => 'secret123',
        ])->assertStatus(403);

        // 4. GET /admin/tenants/{tenant->id} -> HTTP 403
        $this->get("/admin/tenants/{$this->tenant2->id}")->assertStatus(403);

        // 5. GET /admin/tenants/{tenant->id}/edit -> HTTP 403
        $this->get("/admin/tenants/{$this->tenant2->id}/edit")->assertStatus(403);

        // 6. PUT /admin/tenants/{tenant->id} -> HTTP 403
        $this->put("/admin/tenants/{$this->tenant2->id}", [
            'name' => 'Tampered Tenant Name',
        ])->assertStatus(403);

        // 7. DELETE /admin/tenants/{tenant->id} -> HTTP 403
        $this->delete("/admin/tenants/{$this->tenant2->id}")->assertStatus(403);

        // 8. POST /admin/tenants/{tenant->id}/toggle-status -> HTTP 403
        $this->post("/admin/tenants/{$this->tenant2->id}/toggle-status")->assertStatus(403);

        // Verify target tenant remains active and untampered
        $this->assertTrue((bool) $this->tenant2->fresh()->is_active);
    }

    // =========================================================================
    // 4. CROSS-TENANT IDOR PROBES (HTTP 404)
    // =========================================================================

    public function test_cross_tenant_idor_dealers_return_404(): void
    {
        $targetId = $this->t2Dealer->id;

        // GET show
        $this->get("/masters/dealers/{$targetId}")->assertStatus(404);

        // GET edit
        $this->get("/masters/dealers/{$targetId}/edit")->assertStatus(404);

        // PUT update
        $this->put("/masters/dealers/{$targetId}", [
            'firm_name' => 'Hacked Dealer Firm',
            'phone'     => '9843999111',
        ])->assertStatus(404);

        // DELETE destroy
        $this->delete("/masters/dealers/{$targetId}")->assertStatus(404);

        // Verify record untouched
        $this->assertEquals('Erode Central Chicken', $this->t2Dealer->fresh()->firm_name);
    }

    public function test_cross_tenant_idor_vendors_return_404(): void
    {
        $targetId = $this->t2Vendor->id;

        // GET show
        $this->get("/masters/vendors/{$targetId}")->assertStatus(404);

        // GET edit
        $this->get("/masters/vendors/{$targetId}/edit")->assertStatus(404);

        // PUT update
        $this->put("/masters/vendors/{$targetId}", [
            'firm_name' => 'Hacked Vendor Firm',
            'phone'     => '9843999222',
        ])->assertStatus(404);

        // DELETE destroy
        $this->delete("/masters/vendors/{$targetId}")->assertStatus(404);

        // Verify record untouched
        $this->assertEquals('Namakkal Chicks Hatcheries', $this->t2Vendor->fresh()->firm_name);
    }

    public function test_cross_tenant_idor_customers_return_404(): void
    {
        $targetId = $this->t2Customer->id;

        // GET show
        $this->get("/masters/customers/{$targetId}")->assertStatus(404);

        // GET edit
        $this->get("/masters/customers/{$targetId}/edit")->assertStatus(404);

        // PUT update
        $this->put("/masters/customers/{$targetId}", [
            'name'  => 'Hacked Customer',
            'phone' => '9843999333',
            'type'  => 'Retail',
        ])->assertStatus(404);

        // DELETE destroy
        $this->delete("/masters/customers/{$targetId}")->assertStatus(404);

        // Verify record untouched
        $this->assertEquals('Kongu Retail Customer', $this->t2Customer->fresh()->name);
    }

    public function test_cross_tenant_idor_billing_and_day_load_return_404(): void
    {
        // 1. Daily Bill IDOR
        $dailyBillId = $this->t2DailyBill->id;

        // GET show
        $this->get("/billing/daily/{$dailyBillId}")->assertStatus(404);

        // GET pdf invoice download
        $this->get("/billing/daily/{$dailyBillId}/pdf")->assertStatus(404);

        // DELETE destroy
        $this->delete("/billing/daily/{$dailyBillId}")->assertStatus(404);

        // 2. Day-Load Entry IDOR
        $dayLoadEntryId = $this->t2DayLoadEntry->id;

        // DELETE destroy entry
        $this->delete("/billing/day-load/entry/{$dayLoadEntryId}")->assertStatus(404);

        // PUT update entry
        $this->put("/billing/day-load/{$dayLoadEntryId}/update", [
            'vendor_id'     => $this->t2Vendor->id,
            'dealer_id'     => $this->t2Dealer->id,
            'paper_rate'    => 100,
            'billing_rate'  => 100,
            'customer_rate' => 100,
            'no_of_boxes'   => 10,
            'box_weight'    => 200,
            'empty_weight'  => 50,
            'reason'        => 'IDOR attempt',
        ])->assertStatus(404);

        // Verify records in Tenant 2 exist untouched
        $this->assertDatabaseHas('daily_bills', ['id' => $dailyBillId]);
        $this->assertDatabaseHas('day_load_entries', ['id' => $dayLoadEntryId]);
    }

    public function test_cross_tenant_payload_injection_strictly_rejected(): void
    {
        $t1Vendor = Vendor::where('tenant_id', $this->tenant1->id)->firstOrFail();
        $t1Dealer = Dealer::where('tenant_id', $this->tenant1->id)->firstOrFail();

        // 1. Attempt to inject Tenant 2's dealer_id into DayLoad creation
        $resDealerInjection = $this->post(route('billing.day-load.store'), [
            'billing_date'  => now()->toDateString(),
            'vendor_id'     => $t1Vendor->id,
            'dealer_id'     => $this->t2Dealer->id, // Foreign Dealer from Tenant 2
            'paper_rate'    => 110.00,
            'billing_rate'  => 110.00,
            'customer_rate' => 125.00,
            'no_of_boxes'   => 50,
            'box_weight'    => 1200.00,
            'empty_weight'  => 200.00,
        ]);
        $resDealerInjection->assertSessionHasErrors(['dealer_id']);

        // 2. Attempt to inject Tenant 2's vendor_id into DayLoad creation
        $resVendorInjection = $this->post(route('billing.day-load.store'), [
            'billing_date'  => now()->toDateString(),
            'vendor_id'     => $this->t2Vendor->id, // Foreign Vendor from Tenant 2
            'dealer_id'     => $t1Dealer->id,
            'paper_rate'    => 110.00,
            'billing_rate'  => 110.00,
            'customer_rate' => 125.00,
            'no_of_boxes'   => 50,
            'box_weight'    => 1200.00,
            'empty_weight'  => 200.00,
        ]);
        $resVendorInjection->assertSessionHasErrors(['vendor_id']);
    }
}
