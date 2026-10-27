<?php

namespace Tests\Feature\Security;

use App\Models\Batch;
use App\Models\CashBankLedger;
use App\Models\Consumption;
use App\Models\Customer;
use App\Models\DailyBill;
use App\Models\DailyBillItem;
use App\Models\DayLoadBatch;
use App\Models\DayLoadEntry;
use App\Models\Dealer;
use App\Models\DealerPayment;
use App\Models\Emi;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Item;
use App\Models\Mortality;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Models\Warehouse;
use App\Models\WeeklyBill;
use App\Models\WeeklyBillItem;
use App\Services\Tenancy\TenantContext;
use Database\Seeders\MultiTenantVerificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Challenger 2: Empirical Stress Test Harness
 * 
 * Empirically tests:
 * 1. Super Admin isolation (admin@poultry.com HTTP 403 on edit/deactivate/delete; parameter tampering protection; Primary Tenant #1 protection)
 * 2. Tenant barrier (non-super-admin HTTP 403 on /admin/tenants)
 * 3. Cross-tenant IDOR probes across ALL 11 modules returning HTTP 404
 * 4. Cross-tenant payload injection protection
 */
class Challenger2SecurityStressTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $superAdmin;
    protected User $t1Admin;
    protected User $t2Admin;
    protected User $t2Staff;

    // Tenant 2 Resources across all 11 modules
    protected Dealer $t2Dealer;
    protected Vendor $t2Vendor;
    protected Customer $t2Customer;
    protected Item $t2Item;
    protected Warehouse $t2Warehouse;
    protected Batch $t2Batch;
    protected Consumption $t2Consumption;
    protected Mortality $t2Mortality;
    protected DayLoadEntry $t2DayLoadEntry;
    protected DailyBill $t2DailyBill;
    protected WeeklyBill $t2WeeklyBill;
    protected Purchase $t2Purchase;
    protected DealerPayment $t2DealerPayment;
    protected VendorPayment $t2VendorPayment;
    protected Expense $t2Expense;
    protected Emi $t2Emi;
    protected CashBankLedger $t2Ledger;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Ensure Super Admin exists under Primary Tenant #1
        $primaryTenant = Tenant::where('slug', 'msr-broilers')->first() ?? Tenant::create([
            'id'           => 1,
            'name'         => 'MSR Broilers',
            'company_name' => 'MSR Broilers',
            'slug'         => 'msr-broilers',
            'is_active'    => true,
            'currency'     => 'INR',
        ]);

        $this->superAdmin = User::where('email', 'admin@poultry.com')->first() ?? User::create([
            'id'                => 1,
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
        $this->t2Staff = User::where('email', 'annai_staff@annai.com')->firstOrFail();

        // 4. Create Tenant 2 Fixtures across all 11 modules
        app(TenantContext::class)->setTenant($this->tenant2);

        // Module 1: Dealer
        $this->t2Dealer = Dealer::where('tenant_id', $this->tenant2->id)->firstOrFail();

        // Module 2: Vendor
        $this->t2Vendor = Vendor::where('tenant_id', $this->tenant2->id)->firstOrFail();

        // Module 3: Customer
        $this->t2Customer = Customer::create([
            'tenant_id' => $this->tenant2->id,
            'name'      => 'Kongu Retail Customer',
            'phone'     => '9843299887',
            'type'      => 'Retail',
            'balance'   => 4500.00,
        ]);

        // Module 4: Item & Warehouse
        $this->t2Item = Item::create([
            'tenant_id'  => $this->tenant2->id,
            'name'       => 'Broiler Starter Feed',
            'code'       => 'FEED-T2-001',
            'type'       => 'Feed',
            'base_unit'  => 'kg',
            'is_active'  => true,
        ]);

        $this->t2Warehouse = Warehouse::create([
            'tenant_id' => $this->tenant2->id,
            'name'      => 'Erode Feed Shed A',
            'location'  => 'Erode',
            'is_active' => true,
        ]);

        // Module 5: Batch, Consumption, Mortality
        $this->t2Batch = Batch::create([
            'tenant_id'            => $this->tenant2->id,
            'batch_code'           => 'BATCH-T2-2026-01',
            'placement_date'       => now()->subDays(10)->toDateString(),
            'initial_count'        => 5000,
            'current_count'        => 4950,
            'breed'                => 'Cobb 430',
            'avg_placement_weight' => 45.0,
            'status'               => 'Active',
        ]);

        $this->t2Consumption = Consumption::create([
            'tenant_id'    => $this->tenant2->id,
            'batch_id'     => $this->t2Batch->id,
            'item_id'      => $this->t2Item->id,
            'warehouse_id' => $this->t2Warehouse->id,
            'date'         => now()->subDays(2)->toDateString(),
            'quantity'     => 20.0,
            'unit'         => 'kg',
        ]);

        $this->t2Mortality = Mortality::create([
            'tenant_id' => $this->tenant2->id,
            'batch_id'  => $this->t2Batch->id,
            'date'      => now()->subDays(1)->toDateString(),
            'count'     => 15,
            'reason'    => 'Heat stress',
        ]);

        // Module 6: DayLoad Entry
        $this->t2DayLoadEntry = DayLoadEntry::where('tenant_id', $this->tenant2->id)->firstOrFail();

        // Module 7: Daily & Weekly Bill
        $this->t2DailyBill = DailyBill::create([
            'tenant_id'      => $this->tenant2->id,
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
            'tenant_id'     => $this->tenant2->id,
            'daily_bill_id' => $this->t2DailyBill->id,
            'item_name'     => 'Live Broiler Bird',
            'quantity_kg'   => 120.00,
            'rate_per_kg'   => 125.00,
            'tax_amount'    => 0.00,
            'total_amount'  => 15000.00,
            'unit'          => 'kg',
        ]);

        $this->t2WeeklyBill = WeeklyBill::create([
            'tenant_id'    => $this->tenant2->id,
            'dealer_id'    => $this->t2Dealer->id,
            'invoice_no'   => 'WB-T2-001',
            'period_start' => now()->subDays(7)->toDateString(),
            'period_end'   => now()->toDateString(),
            'amount'       => 50000.00,
            'net_amount'   => 50000.00,
            'status'       => 'Pending',
            'payment_mode' => 'Cash',
        ]);

        WeeklyBillItem::create([
            'tenant_id'      => $this->tenant2->id,
            'weekly_bill_id' => $this->t2WeeklyBill->id,
            'item_name'      => 'Live Broiler Bird',
            'quantity_kg'    => 400.00,
            'rate_per_kg'    => 125.00,
            'tax_amount'     => 0.00,
            'total_amount'   => 50000.00,
        ]);

        // Module 8: Purchase
        $this->t2Purchase = Purchase::create([
            'tenant_id'      => $this->tenant2->id,
            'vendor_id'      => $this->t2Vendor->id,
            'vendor_name'    => $this->t2Vendor->firm_name,
            'invoice_no'     => 'PUR-T2-001',
            'date'           => now()->toDateString(),
            'due_date'       => now()->addDays(15)->toDateString(),
            'gst_percentage' => 0.00,
            'gst_amount'     => 0.00,
            'total_amount'   => 45000.00,
            'payment_mode'   => 'Credit',
        ]);

        PurchaseItem::create([
            'tenant_id'   => $this->tenant2->id,
            'purchase_id' => $this->t2Purchase->id,
            'item_name'   => 'Broiler Starter Feed',
            'quantity'    => 1000.00,
            'rate'        => 45.00,
            'unit'        => 'kg',
            'tax_amount'  => 0.00,
            'total_amount' => 45000.00,
        ]);

        // Module 9: Dealer & Vendor Payments
        $this->t2DealerPayment = DealerPayment::create([
            'tenant_id'    => $this->tenant2->id,
            'dealer_id'    => $this->t2Dealer->id,
            'date'         => now()->toDateString(),
            'amount'       => 10000.00,
            'payment_mode' => 'Cash',
            'notes'        => 'Initial advance',
        ]);

        $this->t2VendorPayment = VendorPayment::create([
            'tenant_id'      => $this->tenant2->id,
            'vendor_id'      => $this->t2Vendor->id,
            'date'           => now()->toDateString(),
            'amount'         => 20000.00,
            'payment_mode'   => 'Bank Transfer',
            'reference_number' => 'TXN-T2-001',
        ]);

        // Module 10: Expense, EMI, CashBankLedger
        $cat = ExpenseCategory::firstOrCreate(
            ['tenant_id' => $this->tenant2->id, 'name' => 'Farm Utilities'],
            ['description' => 'Electricity and water']
        );

        $this->t2Expense = Expense::create([
            'tenant_id'      => $this->tenant2->id,
            'category_id'    => $cat->id,
            'category'       => $cat->name,
            'date'           => now()->toDateString(),
            'amount'         => 3500.00,
            'payment_method' => 'Cash',
            'description'    => 'EB bill payment',
        ]);

        $this->t2Emi = Emi::create([
            'tenant_id'   => $this->tenant2->id,
            'loan_name'   => 'Tractor Loan',
            'bank_name'   => 'SBI Namakkal',
            'amount'      => 12000.00,
            'paid_amount' => 0.00,
            'due_date'    => now()->addDays(20)->toDateString(),
            'status'      => 'Pending',
            'emi_type'    => 'Equipment',
        ]);

        $this->t2Ledger = CashBankLedger::where('tenant_id', $this->tenant2->id)->firstOrFail();

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
    // 1. SUPER ADMIN ISOLATION & INTEGRITY (HTTP 403)
    // =========================================================================

    public function test_super_admin_isolation_and_immutability(): void
    {
        $this->assertFalse((bool) $this->t1Admin->is_super_admin);
        $superAdminId = $this->superAdmin->id;

        // A. Attempt PUT /admin/users/{superAdminId} -> HTTP 403 Forbidden
        $resPut = $this->put(route('admin.users.update', $superAdminId), [
            'name'  => 'Compromised Super Admin',
            'email' => 'compromised@poultry.com',
            'role'  => 'staff',
        ]);
        $resPut->assertStatus(403);

        // B. Attempt DELETE /admin/users/{superAdminId} -> HTTP 403 Forbidden
        $resDel = $this->delete(route('admin.users.destroy', $superAdminId));
        $resDel->assertStatus(403);

        // C. Attempt POST /admin/users/{superAdminId}/toggle-status -> HTTP 403 Forbidden
        $resToggle = $this->post(route('admin.users.toggle-status', $superAdminId));
        $resToggle->assertStatus(403);

        // D. Verify Super Admin remains untampered
        $freshSuper = $this->superAdmin->fresh();
        $this->assertEquals('Super Admin', $freshSuper->name);
        $this->assertEquals('admin@poultry.com', $freshSuper->email);
        $this->assertTrue((bool) $freshSuper->is_active);
        $this->assertTrue((bool) $freshSuper->is_super_admin);

        // E. Attempt privilege escalation: creating staff with is_super_admin = true
        $resCreate = $this->post(route('admin.users.store'), [
            'name'           => 'Escalated Admin',
            'username'       => 'escalated_admin',
            'email'          => 'escalated@skfarm.com',
            'password'       => 'Password@123',
            'role'           => 'admin',
            'is_super_admin' => 1,
        ]);
        $resCreate->assertRedirect();
        $escalatedUser = User::where('email', 'escalated@skfarm.com')->firstOrFail();
        $this->assertFalse((bool) $escalatedUser->is_super_admin);
        $this->assertEquals($this->tenant1->id, $escalatedUser->current_tenant_id);

        // F. Primary Tenant #1 deletion & deactivation protection
        $this->actingAs($this->superAdmin);
        session(['active_tenant_id' => 1]);
        app(TenantContext::class)->setTenant(Tenant::find(1));

        $resDelTenant1 = $this->delete('/admin/tenants/1');
        $resDelTenant1->assertRedirect();
        $resDelTenant1->assertSessionHas('error');
        $this->assertDatabaseHas('tenants', ['id' => 1]);

        $resToggleTenant1 = $this->post('/admin/tenants/1/toggle-status');
        $resToggleTenant1->assertRedirect();
        $resToggleTenant1->assertSessionHas('error');
        $this->assertTrue((bool) Tenant::find(1)->is_active);
    }

    // =========================================================================
    // 2. TENANT MANAGEMENT BARRIER (HTTP 403)
    // =========================================================================

    public function test_tenant_management_barrier_strictly_blocks_non_super_admin(): void
    {
        $this->actingAs($this->t1Admin);
        session(['active_tenant_id' => $this->tenant1->id]);
        app(TenantContext::class)->setTenant($this->tenant1);

        $targetTenantId = $this->tenant2->id;

        // 1. GET /admin/tenants
        $this->get('/admin/tenants')->assertStatus(403);

        // 2. GET /admin/tenants/create
        $this->get('/admin/tenants/create')->assertStatus(403);

        // 3. POST /admin/tenants
        $this->post('/admin/tenants', [
            'name'           => 'Rogue Poultry',
            'slug'           => 'rogue-poultry',
            'admin_name'     => 'Rogue Admin',
            'admin_username' => 'rogue_admin',
            'admin_email'    => 'rogue@poultry.local',
            'admin_password' => 'secret123',
        ])->assertStatus(403);

        // 4. GET /admin/tenants/{id}
        $this->get("/admin/tenants/{$targetTenantId}")->assertStatus(403);

        // 5. GET /admin/tenants/{id}/edit
        $this->get("/admin/tenants/{$targetTenantId}/edit")->assertStatus(403);

        // 6. PUT /admin/tenants/{id}
        $this->put("/admin/tenants/{$targetTenantId}", [
            'name' => 'Hostile Takeover',
        ])->assertStatus(403);

        // 7. DELETE /admin/tenants/{id}
        $this->delete("/admin/tenants/{$targetTenantId}")->assertStatus(403);

        // 8. POST /admin/tenants/{id}/toggle-status
        $this->post("/admin/tenants/{$targetTenantId}/toggle-status")->assertStatus(403);

        // Super Admin access must succeed
        $this->actingAs($this->superAdmin);
        session(['active_tenant_id' => 1]);
        app(TenantContext::class)->setTenant(Tenant::find(1));
        $this->get('/admin/tenants')->assertStatus(200);
    }

    // =========================================================================
    // 3. CROSS-TENANT IDOR PROBES ACROSS ALL 11 MODULES (HTTP 404)
    // =========================================================================

    public function test_cross_tenant_idor_probes_across_all_11_modules(): void
    {
        $this->actingAs($this->t1Admin);
        session(['active_tenant_id' => $this->tenant1->id]);
        app(TenantContext::class)->setTenant($this->tenant1);

        // Module 1: Dealers (Route Model Binding scoped by tenant)
        $this->get("/masters/dealers/{$this->t2Dealer->id}")->assertStatus(404);
        $this->get("/masters/dealers/{$this->t2Dealer->id}/edit")->assertStatus(404);
        $this->put("/masters/dealers/{$this->t2Dealer->id}", ['firm_name' => 'Hacked Dealer'])->assertStatus(404);
        $this->delete("/masters/dealers/{$this->t2Dealer->id}")->assertStatus(404);

        // Module 2: Vendors
        $this->get("/masters/vendors/{$this->t2Vendor->id}")->assertStatus(404);
        $this->get("/masters/vendors/{$this->t2Vendor->id}/edit")->assertStatus(404);
        $this->put("/masters/vendors/{$this->t2Vendor->id}", ['firm_name' => 'Hacked Vendor'])->assertStatus(404);
        $this->delete("/masters/vendors/{$this->t2Vendor->id}")->assertStatus(404);

        // Module 3: Customers
        $this->get("/masters/customers/{$this->t2Customer->id}")->assertStatus(404);
        $this->get("/masters/customers/{$this->t2Customer->id}/edit")->assertStatus(404);
        $this->put("/masters/customers/{$this->t2Customer->id}", ['name' => 'Hacked Cust'])->assertStatus(404);
        $this->delete("/masters/customers/{$this->t2Customer->id}")->assertStatus(404);

        // Module 4: Items & Warehouses
        $this->get("/inventory/items/{$this->t2Item->id}/edit")->assertStatus(404);
        $this->put("/inventory/items/{$this->t2Item->id}", ['name' => 'Hacked Item'])->assertStatus(404);
        $this->delete("/inventory/items/{$this->t2Item->id}")->assertStatus(404);

        $this->get("/inventory/warehouses/{$this->t2Warehouse->id}/edit")->assertStatus(404);
        $this->put("/inventory/warehouses/{$this->t2Warehouse->id}", ['name' => 'Hacked Shed'])->assertStatus(404);
        $this->delete("/inventory/warehouses/{$this->t2Warehouse->id}")->assertStatus(404);

        // Module 5: Batches, Consumptions, Mortalities
        $this->get("/inventory/batches/{$this->t2Batch->id}")->assertStatus(404);
        $this->get("/inventory/batches/{$this->t2Batch->id}/edit")->assertStatus(404);
        $this->put("/inventory/batches/{$this->t2Batch->id}", ['status' => 'Closed'])->assertStatus(404);
        $this->delete("/inventory/batches/{$this->t2Batch->id}")->assertStatus(404);
        $this->delete("/inventory/consumptions/{$this->t2Consumption->id}")->assertStatus(404);
        $this->delete("/inventory/mortalities/{$this->t2Mortality->id}")->assertStatus(404);

        // Module 6: DayLoad Billing
        $this->put("/billing/day-load/{$this->t2DayLoadEntry->id}/update", ['no_of_boxes' => 50])->assertStatus(404);
        $this->delete("/billing/day-load/entry/{$this->t2DayLoadEntry->id}")->assertStatus(404);
        $this->post("/billing/day-load/{$this->t2DayLoadEntry->id}/transfer", ['target_dealer_id' => 1])->assertStatus(404);

        // Module 7: Daily & Weekly Billing
        $this->get("/billing/daily/{$this->t2DailyBill->id}")->assertStatus(404);
        $this->get("/billing/daily/{$this->t2DailyBill->id}/pdf")->assertStatus(404);
        $this->delete("/billing/daily/{$this->t2DailyBill->id}")->assertStatus(404);

        $this->get("/billing/weekly/{$this->t2WeeklyBill->id}")->assertStatus(404);
        $this->get("/billing/weekly/{$this->t2WeeklyBill->id}/pdf")->assertStatus(404);
        $this->delete("/billing/weekly/{$this->t2WeeklyBill->id}")->assertStatus(404);

        // Module 8: Purchases
        $this->get("/purchases/{$this->t2Purchase->id}")->assertStatus(404);
        $this->get("/purchases/{$this->t2Purchase->id}/edit")->assertStatus(404);
        $this->put("/purchases/{$this->t2Purchase->id}", [
            'vendor_name'    => 'Namakkal Chicks Hatcheries',
            'payment_mode'   => 'Cash',
            'date'           => now()->toDateString(),
            'gst_percentage' => 0,
            'items'          => [
                [
                    'name' => 'Broiler Starter Feed',
                    'qty'  => 10,
                    'rate' => 45,
                    'unit' => 'kg',
                ],
            ],
        ])->assertStatus(404);
        $this->delete("/purchases/{$this->t2Purchase->id}")->assertStatus(404);

        // Module 9: Payments
        $this->get("/payments/dealers/{$this->t2Dealer->id}/ledger")->assertStatus(404);
        $this->delete("/payments/dealers/{$this->t2DealerPayment->id}")->assertStatus(404);
        $this->get("/payments/vendors/{$this->t2Vendor->id}/ledger")->assertStatus(404);
        $this->delete("/payments/vendors/{$this->t2Vendor->id}/payments/{$this->t2VendorPayment->id}")->assertStatus(404);

        // Module 10: Finance, Expenses, EMIs & CashBankLedger
        $this->get("/expenses/{$this->t2Expense->id}/edit")->assertStatus(404);
        $this->put("/expenses/{$this->t2Expense->id}", ['amount' => 100])->assertStatus(404);
        $this->delete("/expenses/{$this->t2Expense->id}")->assertStatus(404);

        $this->get("/expenses/emis/{$this->t2Emi->id}/edit")->assertStatus(404);
        $this->put("/expenses/emis/{$this->t2Emi->id}", ['amount' => 100])->assertStatus(404);
        $this->delete("/expenses/emis/{$this->t2Emi->id}")->assertStatus(404);

        $this->post("/billing/cash-bank-ledger/{$this->t2Ledger->id}/approve")->assertStatus(404);

        // Module 11: Admin & Users (Unauthorized mutation on other tenant staff returns 403)
        $this->put("/admin/users/{$this->t2Staff->id}", ['name' => 'Hacked Staff'])->assertStatus(403);
        $this->delete("/admin/users/{$this->t2Staff->id}")->assertStatus(403);
        $this->post("/admin/users/{$this->t2Staff->id}/toggle-status")->assertStatus(403);
    }

    // =========================================================================
    // 4. CROSS-TENANT PAYLOAD INJECTION REJECTION
    // =========================================================================

    public function test_cross_tenant_payload_injection_rejected(): void
    {
        $this->actingAs($this->t1Admin);
        session(['active_tenant_id' => $this->tenant1->id]);
        app(TenantContext::class)->setTenant($this->tenant1);

        $t1Vendor = Vendor::where('tenant_id', $this->tenant1->id)->firstOrFail();
        $t1Dealer = Dealer::where('tenant_id', $this->tenant1->id)->firstOrFail();

        // Inject foreign Dealer from Tenant 2 into DayLoad creation
        $resDealerInjection = $this->post(route('billing.day-load.store'), [
            'billing_date'  => now()->toDateString(),
            'vendor_id'     => $t1Vendor->id,
            'dealer_id'     => $this->t2Dealer->id,
            'paper_rate'    => 110.00,
            'billing_rate'  => 110.00,
            'customer_rate' => 125.00,
            'no_of_boxes'   => 50,
            'box_weight'    => 1200.00,
            'empty_weight'  => 200.00,
        ]);
        $resDealerInjection->assertSessionHasErrors(['dealer_id']);

        // Inject foreign Vendor from Tenant 2 into DayLoad creation
        $resVendorInjection = $this->post(route('billing.day-load.store'), [
            'billing_date'  => now()->toDateString(),
            'vendor_id'     => $this->t2Vendor->id,
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
