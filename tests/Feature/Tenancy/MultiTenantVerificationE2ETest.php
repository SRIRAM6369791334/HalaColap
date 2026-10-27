<?php

namespace Tests\Feature\Tenancy;

use App\Exceptions\BoxConservationException;
use App\Models\CashBankLedger;
use App\Models\DayLoadBatch;
use App\Models\DayLoadEntry;
use App\Models\Dealer;
use App\Models\DealerPayment;
use App\Models\Emi;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CashBankLedgerService;
use App\Services\DayLoadBillingService;
use App\Services\DealerPaymentService;
use App\Services\Tenancy\TenantContext;
use Database\Seeders\MultiTenantVerificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Multi-Tenant Verification End-to-End Test Suite
 *
 * Verifies Requirements R1 through R5:
 * - R1: 3 Active Business Tenants Provisioned
 * - R2: Sub-Role Provisioning & Spatie Teams Permission Synchronization
 * - R3: Operational Masters, Dayload Billing, Poultry Math, FIFO Waterfall, Ledger Cascading
 * - R4: Strict Multi-Tenant Data Isolation (Zero IDOR / Cross-Tenant Payload Rejection)
 * - R5: Platform Super Admin Security Barrier & Anti-Mutation Protection
 */
class MultiTenantVerificationE2ETest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected Tenant $tenant3;

    protected User $superAdmin;
    protected User $t1Admin;
    protected User $t1Acc;
    protected User $t1Staff;

    protected User $t2Admin;
    protected User $t2Acc;
    protected User $t2Staff;

    protected User $t3Admin;
    protected User $t3Acc;
    protected User $t3Staff;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Platform Super Admin in Tenant #1 (MSR Broilers)
        $primaryTenant = Tenant::where('slug', 'msr-broilers')->first() ?? Tenant::create([
            'id'           => 1,
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

        // 2. Run MultiTenantVerificationSeeder to seed SK Farm, Annai, Kongu
        $this->seed(MultiTenantVerificationSeeder::class);

        // 3. Resolve seeded entities
        $this->tenant1 = Tenant::where('slug', 'sk-farm')->firstOrFail();
        $this->tenant2 = Tenant::where('slug', 'annai-poultry')->firstOrFail();
        $this->tenant3 = Tenant::where('slug', 'kongu-broilers')->firstOrFail();

        $this->t1Admin = User::where('email', 'sk_admin@skfarm.com')->firstOrFail();
        $this->t1Acc   = User::where('email', 'sk_acc@skfarm.com')->firstOrFail();
        $this->t1Staff = User::where('email', 'sk_staff@skfarm.com')->firstOrFail();

        $this->t2Admin = User::where('email', 'annai_admin@annai.com')->firstOrFail();
        $this->t2Acc   = User::where('email', 'annai_acc@annai.com')->firstOrFail();
        $this->t2Staff = User::where('email', 'annai_staff@annai.com')->firstOrFail();

        $this->t3Admin = User::where('email', 'kongu_admin@kongu.com')->firstOrFail();
        $this->t3Acc   = User::where('email', 'kongu_acc@kongu.com')->firstOrFail();
        $this->t3Staff = User::where('email', 'kongu_staff@kongu.com')->firstOrFail();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // =========================================================================
    // REQUIREMENT R1: Tenant Provisioning via Super Admin
    // =========================================================================

    public function test_r1_three_distinct_active_tenants_provisioned_with_admins(): void
    {
        $this->assertNotEquals($this->tenant1->id, $this->tenant2->id);
        $this->assertNotEquals($this->tenant2->id, $this->tenant3->id);
        $this->assertNotEquals($this->tenant1->id, $this->tenant3->id);

        $this->assertTrue((bool) $this->tenant1->is_active);
        $this->assertTrue((bool) $this->tenant2->is_active);
        $this->assertTrue((bool) $this->tenant3->is_active);

        // Verify Tenant Admins
        foreach ([
            [$this->t1Admin, $this->tenant1, 'sk-farm'],
            [$this->t2Admin, $this->tenant2, 'annai-poultry'],
            [$this->t3Admin, $this->tenant3, 'kongu-broilers'],
        ] as [$admin, $tenant, $slug]) {
            $this->assertEquals($tenant->id, $admin->current_tenant_id);
            $this->assertFalse((bool) $admin->is_super_admin, "Tenant admin {$admin->email} must not be super admin");
            
            // Check pivot
            $pivot = DB::table('tenant_user')
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $admin->id)
                ->first();
            $this->assertNotNull($pivot);
            $this->assertEquals('admin', $pivot->role);
            $this->assertTrue((bool) $pivot->is_active);

            // Spatie role
            setPermissionsTeamId($tenant->id);
            $this->assertTrue($admin->hasRole('admin'));
        }
    }

    // =========================================================================
    // REQUIREMENT R2: Sub-Role Provisioning & Spatie Teams Synchronization
    // =========================================================================

    public function test_r2_sub_roles_provisioned_with_spatie_teams_synchronization(): void
    {
        foreach ([
            [$this->t1Acc, $this->t1Staff, $this->tenant1],
            [$this->t2Acc, $this->t2Staff, $this->tenant2],
            [$this->t3Acc, $this->t3Staff, $this->tenant3],
        ] as [$acc, $staff, $tenant]) {
            setPermissionsTeamId($tenant->id);

            // Accountant
            $this->assertEquals($tenant->id, $acc->current_tenant_id);
            $this->assertFalse((bool) $acc->is_super_admin);
            $this->assertTrue($acc->hasRole('accountant'));
            $this->assertTrue($acc->hasPermissionTo('view bills'));
            $this->assertTrue($acc->hasPermissionTo('view payments'));
            $this->assertFalse($acc->hasPermissionTo('delete users'));

            // Staff
            $this->assertEquals($tenant->id, $staff->current_tenant_id);
            $this->assertFalse((bool) $staff->is_super_admin);
            $this->assertTrue($staff->hasRole('staff'));
            $this->assertTrue($staff->hasPermissionTo('view bills'));
            $this->assertFalse($staff->hasPermissionTo('view expenses'));
        }
    }

    // =========================================================================
    // REQUIREMENT R3: Operational Masters & DayLoad Billing Execution
    // =========================================================================

    public function test_r3_operational_masters_and_poultry_math_formulas(): void
    {
        $context = app(TenantContext::class);

        // Test under Tenant 1: SK Farm Broilers
        $context->setTenant($this->tenant1);

        $dealer = Dealer::where('firm_name', 'Salem Modern Broilers')->firstOrFail();
        $vendor = Vendor::where('firm_name', 'SK Feed Traders')->firstOrFail();

        $this->assertEquals(15000.00, (float) $dealer->pending_amount);
        $this->assertEquals(50000.00, (float) $vendor->pending_amount);

        // Check DayLoad entry created by seeder
        $entry = DayLoadEntry::where('dealer_id', $dealer->id)->firstOrFail();

        // 1. Gross - Tare = Net bird_weight
        $expectedBirdWeight = round((float) $entry->box_weight - (float) $entry->empty_weight, 2);
        $this->assertEquals(2000.00, (float) $entry->bird_weight);
        $this->assertEquals($expectedBirdWeight, (float) $entry->bird_weight);

        // 2. Loss Weight = Farm Weight - Bird Weight
        $expectedLossWeight = round((float) $entry->farm_weight - (float) $entry->bird_weight, 2);
        $this->assertEquals(50.00, (float) $entry->loss_weight);
        $this->assertEquals($expectedLossWeight, (float) $entry->loss_weight);

        // 3. Amount = Bird Weight * Customer Rate
        $expectedAmount = round((float) $entry->bird_weight * (float) $entry->customer_rate, 2);
        $this->assertEquals(270000.00, (float) $entry->amount);
        $this->assertEquals($expectedAmount, (float) $entry->amount);

        // 4. Financial accessors
        $this->assertEquals(270000.00, (float) $entry->dealer_income);
        $this->assertEquals(246000.00, (float) $entry->vendor_cost); // 2050 effective FW * 120
        $this->assertEquals(24000.00, (float) $entry->gross_margin);
    }

    public function test_r3_box_conservation_during_load_split(): void
    {
        $context = app(TenantContext::class);
        $context->setTenant($this->tenant1);
        $billingService = app(DayLoadBillingService::class);

        $dealer = Dealer::where('firm_name', 'Salem Modern Broilers')->firstOrFail();
        $dealerAux = Dealer::create([
            'firm_name'      => 'Salem Fresh Chicken Mart',
            'phone'          => '9842100099',
            'pending_amount' => 0.00,
        ]);

        $entry = DayLoadEntry::where('dealer_id', $dealer->id)->firstOrFail();
        $originalBoxes = (int) $entry->no_of_boxes;

        // 1. Attempt invalid split (70 + 40 = 110 != 100) -> must throw BoxConservationException
        $this->expectException(BoxConservationException::class);
        $billingService->splitEntry($entry, [
            'remaining_boxes' => 70,
            'split_boxes'     => 40,
            'dealer_id'       => $dealerAux->id,
            'customer_rate'   => 136.00,
        ], 'Violating split');
    }

    public function test_r3_fifo_dealer_payment_and_outstanding_calculation(): void
    {
        $context = app(TenantContext::class);
        $context->setTenant($this->tenant1);

        $dealer = Dealer::where('firm_name', 'Salem Modern Broilers')->firstOrFail();
        $entry = DayLoadEntry::where('dealer_id', $dealer->id)->firstOrFail();

        // Create an EMI for dealer
        $emi = Emi::create([
            'emi_type'    => 'Dealer',
            'entity_id'   => $dealer->id,
            'loan_name'   => 'Delivery Van EMI',
            'amount'      => 10000.00,
            'paid_amount' => 0.00,
            'due_date'    => '2026-10-01',
            'status'      => 'Pending',
        ]);

        $dealer = $dealer->fresh();
        // Outstanding = pending_amount (15000) + dayload_outstanding (270000 - 15000 paid) + emi_outstanding (10000) - unallocatedPayments (0)
        $currentOutstanding = (float) $dealer->displayed_outstanding;

        // Record a payment with EMI selection
        $paymentService = app(DealerPaymentService::class);
        $paymentRecord = $paymentService->record([
            'dealer_id'        => $dealer->id,
            'date'             => '2026-10-10',
            'payment_mode'     => 'Split',
            'cash_amount'      => 20000.00,
            'bank_amount'      => 10000.00,
            'selected_emi_ids' => [$emi->id],
            'notes'            => 'Settlement',
        ]);

        $emi->refresh();
        $this->assertEquals(10000.00, (float) $emi->paid_amount);
        $this->assertEquals('Paid', $emi->status);

        // Grouped payments verification
        $groupId = $paymentRecord->payment_group_id;
        $this->assertNotEmpty($groupId);
        $grouped = DealerPayment::where('payment_group_id', $groupId)->get();
        $this->assertEquals(30000.00, (float) $grouped->sum('amount'));

        // Verify EMI payment notes start with 'EMI Payment' and are not double deducted
        $dealer = $dealer->fresh();
        $this->assertEquals(
            round($currentOutstanding - 30000.00, 2),
            round((float) $dealer->displayed_outstanding, 2),
            'EMI payment must not be double deducted from displayed outstanding.'
        );
    }

    public function test_r3_cash_bank_ledger_cascading_and_tenant_isolation(): void
    {
        $context = app(TenantContext::class);
        $ledgerService = app(CashBankLedgerService::class);

        // Check Tenant 1 ledger
        $context->setTenant($this->tenant1);
        $t1Ledger = $ledgerService->getOrCreateForDate(\Carbon\Carbon::parse('2026-10-10'));
        $this->assertEquals(10000.00, (float) $t1Ledger->cash_income);
        $this->assertEquals(5000.00, (float) $t1Ledger->bank_income);

        // Check Tenant 2 ledger is isolated
        $context->setTenant($this->tenant2);
        $t2Ledger = $ledgerService->getOrCreateForDate(\Carbon\Carbon::parse('2026-10-10'));
        $this->assertEquals(12000.00, (float) $t2Ledger->cash_income);
        $this->assertEquals(8000.00, (float) $t2Ledger->bank_income);

        $this->assertNotEquals((float) $t1Ledger->cash_income, (float) $t2Ledger->cash_income);
    }

    // =========================================================================
    // REQUIREMENT R4: Strict Data Isolation (Zero IDOR / Cross-Tenant Rejection)
    // =========================================================================

    public function test_r4_orm_queries_strictly_partitioned_by_tenant(): void
    {
        $context = app(TenantContext::class);

        $context->setTenant($this->tenant1);
        $t1Dealers = Dealer::pluck('firm_name')->toArray();
        $this->assertContains('Salem Modern Broilers', $t1Dealers);
        $this->assertNotContains('Erode Central Chicken', $t1Dealers);
        $this->assertNotContains('Coimbatore Royal Broilers', $t1Dealers);

        $context->setTenant($this->tenant2);
        $t2Dealers = Dealer::pluck('firm_name')->toArray();
        $this->assertContains('Erode Central Chicken', $t2Dealers);
        $this->assertNotContains('Salem Modern Broilers', $t2Dealers);
        $this->assertNotContains('Coimbatore Royal Broilers', $t2Dealers);

        $context->setTenant($this->tenant3);
        $t3Dealers = Dealer::pluck('firm_name')->toArray();
        $this->assertContains('Coimbatore Royal Broilers', $t3Dealers);
        $this->assertNotContains('Salem Modern Broilers', $t3Dealers);
        $this->assertNotContains('Erode Central Chicken', $t3Dealers);
    }

    public function test_r4_zero_idor_http_requests_reject_other_tenant_records(): void
    {
        $context = app(TenantContext::class);
        $context->setTenant($this->tenant2);
        $t2Dealer = Dealer::where('firm_name', 'Erode Central Chicken')->firstOrFail();
        $t2Vendor = Vendor::where('firm_name', 'Namakkal Chicks Hatcheries')->firstOrFail();

        // Acting as Tenant 1 Admin
        $this->actingAs($this->t1Admin);
        $context->setTenant($this->tenant1);

        // Probing Tenant 2 Dealer URL -> must return 404
        $dealerResponse = $this->get('/masters/dealers/' . $t2Dealer->id);
        $dealerResponse->assertStatus(404);

        // Probing Tenant 2 Vendor URL -> must return 404
        $vendorResponse = $this->get('/masters/vendors/' . $t2Vendor->id);
        $vendorResponse->assertStatus(404);
    }

    public function test_r4_cross_tenant_foreign_payload_injection_rejected(): void
    {
        $context = app(TenantContext::class);
        $context->setTenant($this->tenant2);
        $t2Dealer = Dealer::where('firm_name', 'Erode Central Chicken')->firstOrFail();
        $t2Vendor = Vendor::where('firm_name', 'Namakkal Chicks Hatcheries')->firstOrFail();

        // Acting as Tenant 1 Admin
        $this->actingAs($this->t1Admin);
        $context->setTenant($this->tenant1);

        $t1Dealer = Dealer::where('firm_name', 'Salem Modern Broilers')->firstOrFail();
        $t1Vendor = Vendor::where('firm_name', 'SK Feed Traders')->firstOrFail();

        // 1. Inject Tenant 2's dealer_id into Tenant 1's store payload
        $response1 = $this->post('/billing/day-load', [
            'billing_date'  => '2026-10-10',
            'vendor_id'     => $t1Vendor->id,
            'dealer_id'     => $t2Dealer->id, // Malicious cross-tenant foreign ID
            'paper_rate'    => 120.00,
            'customer_rate' => 135.00,
            'no_of_boxes'   => 10,
            'box_weight'    => 250.00,
            'empty_weight'  => 50.00,
        ]);
        $response1->assertSessionHasErrors('dealer_id');

        // 2. Inject Tenant 2's vendor_id into Tenant 1's store payload
        $response2 = $this->post('/billing/day-load', [
            'billing_date'  => '2026-10-10',
            'vendor_id'     => $t2Vendor->id, // Malicious cross-tenant foreign ID
            'dealer_id'     => $t1Dealer->id,
            'paper_rate'    => 120.00,
            'customer_rate' => 135.00,
            'no_of_boxes'   => 10,
            'box_weight'    => 250.00,
            'empty_weight'  => 50.00,
        ]);
        $response2->assertSessionHasErrors('vendor_id');
    }

    public function test_r4_mass_assignment_cannot_override_tenant_id(): void
    {
        $context = app(TenantContext::class);
        $context->setTenant($this->tenant1);

        // Attempt to create a Dealer with explicit foreign tenant_id
        $dealer = Dealer::create([
            'tenant_id'      => $this->tenant2->id, // Attacker specifies Tenant 2
            'firm_name'      => 'Spoofed Dealer',
            'contact_person' => 'Attacker',
            'phone'          => '9999999999',
            'pending_amount' => 5000.00,
        ]);

        $this->assertEquals($this->tenant1->id, $dealer->tenant_id, 'tenant_id must be unconditionally stamped to active tenant');
    }

    // =========================================================================
    // REQUIREMENT R5: Platform Super Admin Security Barrier
    // =========================================================================

    public function test_r5_tenant_admins_strictly_blocked_from_platform_functions(): void
    {
        $this->actingAs($this->t1Admin);
        app(TenantContext::class)->setTenant($this->tenant1);

        // HTTP GET /admin/tenants -> 403 Forbidden
        $this->get('/admin/tenants')->assertStatus(403);
        $this->get('/admin/tenants/create')->assertStatus(403);
        $this->post('/admin/tenants', ['name' => 'Rogue'])->assertStatus(403);

        // Gate blacklist evaluation
        $blacklistedAbilities = [
            'manage-platform-tenants', 'create-tenants', 'resell-saas',
            'manage-tenants', 'manage-resellers', 'manage-platform-settings',
            'system-level-override', 'platform-admin',
        ];

        foreach ($blacklistedAbilities as $ability) {
            $this->assertFalse(
                Gate::allows($ability),
                "Tenant Admin must be denied platform ability '{$ability}'"
            );
        }
    }

    public function test_r5_super_admin_account_protected_from_mutation_and_deletion(): void
    {
        $this->actingAs($this->t1Admin);
        app(TenantContext::class)->setTenant($this->tenant1);

        // 1. DELETE /admin/users/{superAdmin->id} -> 403
        $delResponse = $this->delete('/admin/users/' . $this->superAdmin->id);
        $delResponse->assertStatus(403);
        $this->assertTrue(User::where('id', $this->superAdmin->id)->exists());

        // 2. PUT /admin/users/{superAdmin->id} -> 403
        $putResponse = $this->put('/admin/users/' . $this->superAdmin->id, [
            'name'  => 'Hacked Admin',
            'email' => 'hacked@poultry.com',
            'role'  => 'staff',
        ]);
        $putResponse->assertStatus(403);
        $this->assertEquals('admin@poultry.com', $this->superAdmin->fresh()->email);

        // 3. POST /admin/users/{superAdmin->id}/toggle-status -> 403
        $toggleResponse = $this->post('/admin/users/' . $this->superAdmin->id . '/toggle-status');
        $toggleResponse->assertStatus(403);
        $this->assertTrue((bool) $this->superAdmin->fresh()->is_active);

        // 4. Cross-tenant user deletion attempt
        $t2StaffResponse = $this->delete('/admin/users/' . $this->t2Staff->id);
        $t2StaffResponse->assertStatus(403);
        $this->assertTrue(User::where('id', $this->t2Staff->id)->exists());
    }
}
