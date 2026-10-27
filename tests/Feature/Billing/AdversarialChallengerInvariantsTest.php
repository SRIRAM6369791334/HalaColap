<?php

namespace Tests\Feature\Billing;

use App\Exceptions\BoxConservationException;
use App\Models\Batch;
use App\Models\CashBankLedger;
use App\Models\DailyBill;
use App\Models\DayLoadBatch;
use App\Models\DayLoadEntry;
use App\Models\Dealer;
use App\Models\DealerPayment;
use App\Models\Emi;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\StockLedger;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorAdvance;
use App\Models\VendorPayment;
use App\Services\CashBankLedgerService;
use App\Services\DayLoadBillingService;
use App\Services\DealerPaymentService;
use App\Services\Tenancy\TenantContext;
use Carbon\Carbon;
use Database\Seeders\MultiTenantVerificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Challenger 1 Empirical Adversarial Stress Test Suite
 *
 * Explicitly probes invariants and edge cases requested in ORIGINAL_REQUEST.md & DISPATCH.md:
 * 1. Crate conservation invariance in DayLoadBillingService (BoxConservationException).
 * 2. Gross vs Tare boundary conditions (Gross < Tare 500kg vs 1000kg, 0 kg net, negative rate).
 * 3. Rate delta recalculation accuracy and bill deletion reversal.
 * 4. 4-Tier FIFO payment allocation correctness and void reversal.
 * 5. Dependency deletion protection on Dealers, Vendors, and Items.
 */
class AdversarialChallengerInvariantsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $t1Admin;
    protected Vendor $vendor;
    protected Dealer $dealer;

    protected function setUp(): void
    {
        parent::setUp();

        $primaryTenant = Tenant::where('slug', 'msr-broilers')->first() ?? Tenant::create([
            'name'         => 'MSR Broilers',
            'company_name' => 'MSR Broilers',
            'slug'         => 'msr-broilers',
            'is_active'    => true,
            'currency'     => 'INR',
        ]);

        $superAdmin = User::where('email', 'admin@poultry.com')->first() ?? User::create([
            'name'              => 'Super Admin',
            'username'          => 'superadmin',
            'email'             => 'admin@poultry.com',
            'password'          => bcrypt('Admin@1234'),
            'is_active'         => true,
            'is_super_admin'    => true,
            'current_tenant_id' => $primaryTenant->id,
        ]);

        if (! DB::table('tenant_user')->where('tenant_id', $primaryTenant->id)->where('user_id', $superAdmin->id)->exists()) {
            DB::table('tenant_user')->insert([
                'tenant_id'  => $primaryTenant->id,
                'user_id'    => $superAdmin->id,
                'username'   => 'admin',
                'role'       => 'admin',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->seed(MultiTenantVerificationSeeder::class);

        $this->tenant1 = Tenant::where('slug', 'sk-farm')->firstOrFail();
        $this->tenant2 = Tenant::where('slug', 'annai-poultry')->firstOrFail();

        $this->t1Admin = User::where('email', 'sk_admin@skfarm.com')->firstOrFail();

        $this->actingAs($this->t1Admin);
        session(['active_tenant_id' => $this->tenant1->id]);
        app(TenantContext::class)->setTenant($this->tenant1);
        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($this->tenant1->id);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->vendor = Vendor::create([
            'firm_name'      => 'Challenger Supply Vendor',
            'phone'          => '9843911111',
            'pending_amount' => 0.00,
        ]);

        $this->dealer = Dealer::create([
            'firm_name'      => 'Challenger Retail Dealer',
            'phone'          => '9843922222',
            'pending_amount' => 0.00,
        ]);
    }

    // =========================================================================
    // 1. INVARIANT: CRATE CONSERVATION (BoxConservationException)
    // =========================================================================

    public function test_crate_conservation_strict_conservation_and_exceptions(): void
    {
        $batch = DayLoadBatch::create([
            'billing_date' => now()->toDateString(),
            'status'       => 'Open',
        ]);

        $entry = DayLoadEntry::create([
            'batch_id'      => $batch->id,
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $this->dealer->id,
            'no_of_boxes'   => 100, // Initial 100 crates
            'box_weight'    => 3000.00,
            'empty_weight'  => 600.00,
            'customer_rate' => 125.00,
            'paper_rate'    => 115.00,
            'billing_rate'  => 115.00,
        ]);

        $service = app(DayLoadBillingService::class);

        // Negative Case A: 100 loaded vs 150 split (over-allocation)
        try {
            $service->splitEntry($entry, [
                'remaining_boxes' => 50,
                'split_boxes'     => 100, // 50 + 100 = 150 != 100
                'dealer_id'       => $this->dealer->id,
            ], 'Over-allocation test');
            $this->fail('BoxConservationException was expected but not thrown.');
        } catch (BoxConservationException $e) {
            $this->assertStringContainsString('Box conservation violated', $e->getMessage());
        }

        // Negative Case B: 100 loaded vs 90 split (under-allocation)
        try {
            $service->splitEntry($entry, [
                'remaining_boxes' => 50,
                'split_boxes'     => 40, // 50 + 40 = 90 != 100
                'dealer_id'       => $this->dealer->id,
            ], 'Under-allocation test');
            $this->fail('BoxConservationException was expected but not thrown.');
        } catch (BoxConservationException $e) {
            $this->assertStringContainsString('Box conservation violated', $e->getMessage());
        }

        // Positive Case C: Valid conservation (65 + 35 = 100)
        [$originalAfter, $child1] = $service->splitEntry($entry, [
            'remaining_boxes' => 65,
            'split_boxes'     => 35,
            'dealer_id'       => $this->dealer->id,
        ], 'First valid split');

        $this->assertEquals(65, $originalAfter->no_of_boxes);
        $this->assertEquals(35, $child1->no_of_boxes);
        $this->assertEquals(100, $originalAfter->no_of_boxes + $child1->no_of_boxes);

        // Multi-level cascading split: split child1 (35 boxes) into 20 and 15
        [$child1After, $grandChild] = $service->splitEntry($child1, [
            'remaining_boxes' => 20,
            'split_boxes'     => 15,
            'dealer_id'       => $this->dealer->id,
        ], 'Second level cascaded split');

        $this->assertEquals(20, $child1After->no_of_boxes);
        $this->assertEquals(15, $grandChild->no_of_boxes);

        // Sum of all surviving leaves: 65 + 20 + 15 = 100 crates (Perfect conservation)
        $totalCrates = $originalAfter->no_of_boxes + $child1After->no_of_boxes + $grandChild->no_of_boxes;
        $this->assertEquals(100, $totalCrates);
    }

    // =========================================================================
    // 2. INVARIANT: GROSS VS TARE BOUNDARY CONDITIONS
    // =========================================================================

    public function test_gross_vs_tare_boundary_conditions(): void
    {
        $billingDate = now()->toDateString();

        // Boundary A: Gross < Tare (box_weight 500kg, empty_weight 1000kg)
        // Check how controller handles this boundary condition
        $responseGrossLessTare = $this->post(route('billing.day-load.store'), [
            'billing_date'  => $billingDate,
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $this->dealer->id,
            'paper_rate'    => 115.00,
            'customer_rate' => 125.00,
            'no_of_boxes'   => 50,
            'box_weight'    => 500.00,
            'empty_weight'  => 1000.00,
        ]);
        // Under R2, Gross < Tare (500kg vs 1000kg) MUST be rejected by validation
        // Expected: session error on box_weight (e.g. gt:empty_weight or custom validation)
        $responseGrossLessTare->assertSessionHasErrors(['box_weight']);

        // Boundary B: Negative rate (-120.00) must be rejected
        $responseNegativeRate = $this->post(route('billing.day-load.store'), [
            'billing_date'  => $billingDate,
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $this->dealer->id,
            'paper_rate'    => 115.00,
            'customer_rate' => -120.00,
            'no_of_boxes'   => 50,
            'box_weight'    => 1000.00,
            'empty_weight'  => 200.00,
        ]);
        $responseNegativeRate->assertSessionHasErrors(['customer_rate']);

        // Boundary C: Zero bird count / boxes (0) must be rejected
        $responseZeroBoxes = $this->post(route('billing.day-load.store'), [
            'billing_date'  => $billingDate,
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $this->dealer->id,
            'paper_rate'    => 115.00,
            'customer_rate' => 125.00,
            'no_of_boxes'   => 0,
            'box_weight'    => 1000.00,
            'empty_weight'  => 200.00,
        ]);
        $responseZeroBoxes->assertSessionHasErrors(['no_of_boxes']);

        // Boundary D: Gross == Tare (1000kg vs 1000kg -> net 0kg)
        $entryZeroNet = DayLoadEntry::create([
            'batch_id'      => DayLoadBatch::create(['billing_date' => $billingDate, 'status' => 'Open'])->id,
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $this->dealer->id,
            'no_of_boxes'   => 10,
            'box_weight'    => 1000.00,
            'empty_weight'  => 1000.00,
            'customer_rate' => 125.00,
            'paper_rate'    => 115.00,
        ]);
        $this->assertEquals(0.00, (float) $entryZeroNet->bird_weight);
        $this->assertEquals(0.00, (float) $entryZeroNet->amount);
    }

    // =========================================================================
    // 3. INVARIANT: RATE DELTA RECALCULATION & BILL DELETION REVERSAL
    // =========================================================================

    public function test_rate_change_recalculation_delta_and_deletion_reversal(): void
    {
        $batch = DayLoadBatch::create([
            'billing_date' => now()->toDateString(),
            'status'       => 'Open',
        ]);

        // Net 2400kg, Rate 125 -> Bill Total = 300,000
        $entry = DayLoadEntry::create([
            'batch_id'      => $batch->id,
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $this->dealer->id,
            'no_of_boxes'   => 100,
            'box_weight'    => 3000.00,
            'empty_weight'  => 600.00,
            'customer_rate' => 125.00,
            'paper_rate'    => 115.00,
            'billing_rate'  => 115.00,
        ]);

        $this->assertEquals(300000.00, (float) $entry->amount);
        $this->assertEquals(300000.00, (float) $this->dealer->fresh()->displayed_outstanding);

        // Step 1: Recalculate rate from 125 to 130 (+5 delta)
        $this->put(route('billing.day-load.update', $entry->id), [
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $this->dealer->id,
            'paper_rate'    => 115.00,
            'billing_rate'  => 115.00,
            'customer_rate' => 130.00,
            'no_of_boxes'   => 100,
            'box_weight'    => 3000.00,
            'empty_weight'  => 600.00,
            'reason'        => 'Adversarial rate probe 125 -> 130',
        ])->assertSessionHas('success');

        // Verify recalculated total = 312,000 and delta of +12,000 reflected in outstanding
        $this->assertEquals(312000.00, (float) $entry->fresh()->amount);
        $this->assertEquals(312000.00, (float) $this->dealer->fresh()->displayed_outstanding);

        // Step 2: Recalculate rate downwards from 130 to 120 (-10 delta)
        $this->put(route('billing.day-load.update', $entry->id), [
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $this->dealer->id,
            'paper_rate'    => 115.00,
            'billing_rate'  => 115.00,
            'customer_rate' => 120.00,
            'no_of_boxes'   => 100,
            'box_weight'    => 3000.00,
            'empty_weight'  => 600.00,
            'reason'        => 'Adversarial rate probe 130 -> 120',
        ])->assertSessionHas('success');

        // Total = 2400 * 120 = 288,000
        $this->assertEquals(288000.00, (float) $entry->fresh()->amount);
        $this->assertEquals(288000.00, (float) $this->dealer->fresh()->displayed_outstanding);

        // Step 3: Deletion Reversal -> Deleting entry MUST reverse full ₹2,88,000 back to 0.00
        $this->delete(route('billing.day-load.destroy-entry', $entry->id))->assertSessionHas('success');

        $this->assertDatabaseMissing('day_load_entries', ['id' => $entry->id]);
        $this->assertEquals(0.00, (float) $this->dealer->fresh()->displayed_outstanding);
        $this->assertEquals(0.00, (float) $this->dealer->fresh()->dayload_outstanding);
    }

    // =========================================================================
    // 4. INVARIANT: 4-TIER FIFO PAYMENT WATERFALL
    // =========================================================================

    public function test_four_tier_fifo_payment_waterfall_ordering_and_void(): void
    {
        // Setup 4 distinct obligations across different tiers:
        // Tier 1: EMI obligation of ₹20,000
        $emi = Emi::create([
            'emi_type'     => 'Dealer',
            'entity_id'    => $this->dealer->id,
            'amount'       => 20000.00,
            'paid_amount'  => 0.00,
            'due_date'     => now()->addDays(15)->toDateString(),
            'status'       => 'Upcoming',
            'loan_name'    => 'Dealer Advance Loan',
        ]);

        // Tier 2: Base pending opening balance of ₹15,000
        $this->dealer->update(['pending_amount' => 15000.00]);

        // Tier 3: DayLoad Entries (2 bills with different timestamps)
        $batchOld = DayLoadBatch::create(['billing_date' => now()->subDays(3)->toDateString(), 'status' => 'Open']);
        $entryOld = DayLoadEntry::create([
            'batch_id'      => $batchOld->id,
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $this->dealer->id,
            'no_of_boxes'   => 40,
            'box_weight'    => 1000.00,
            'empty_weight'  => 200.00, // 800kg
            'customer_rate' => 100.00, // 80,000
            'paper_rate'    => 90.00,
            'billing_rate'  => 90.00,
            'created_at'    => now()->subDays(3),
        ]);

        $batchNew = DayLoadBatch::create(['billing_date' => now()->subDays(1)->toDateString(), 'status' => 'Open']);
        $entryNew = DayLoadEntry::create([
            'batch_id'      => $batchNew->id,
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $this->dealer->id,
            'no_of_boxes'   => 50,
            'box_weight'    => 1200.00,
            'empty_weight'  => 200.00, // 1000kg
            'customer_rate' => 100.00, // 100,000
            'paper_rate'    => 90.00,
            'billing_rate'  => 90.00,
            'created_at'    => now()->subDays(1),
        ]);

        // Total initial outstanding = 15,000 (pending) + 20,000 (EMI) + 80,000 (DL1) + 100,000 (DL2) = 215,000
        $this->assertEquals(215000.00, (float) $this->dealer->fresh()->displayed_outstanding);

        // Execute Payment of ₹1,25,000 without specific entry selection
        // Waterfall expectation:
        // 1. Base Pending ₹15,000 paid -> Remaining ₹1,10,000
        // 2. Oldest DayLoad ($entryOld, ₹80,000) fully paid -> Remaining ₹30,000
        // 3. Newer DayLoad ($entryNew, ₹1,00,000) partially paid by ₹30,000 -> Remaining ₹0
        $paymentData = [
            'dealer_id'    => $this->dealer->id,
            'amount'       => 125000.00,
            'cash_amount'  => 75000.00,
            'bank_amount'  => 50000.00,
            'payment_mode' => 'Cash',
            'date'         => now()->toDateString(),
        ];

        $payment = app(DealerPaymentService::class)->record($paymentData);

        // Assertions for Waterfall:
        // A. Oldest entry is Paid (80,000 collected)
        $this->assertEquals(80000.00, (float) $entryOld->fresh()->dealer_collected);
        $this->assertEquals('Paid', $entryOld->fresh()->dealer_payment_status);

        // B. Newer entry is Partial (30,000 collected, 70,000 due)
        $this->assertEquals(30000.00, (float) $entryNew->fresh()->dealer_collected);
        $this->assertEquals('Partial', $entryNew->fresh()->dealer_payment_status);

        // C. Dealer outstanding reduced exactly: 215,000 - 125,000 = 90,000 (70,000 DL + 20,000 EMI)
        $this->assertEquals(90000.00, (float) $this->dealer->fresh()->displayed_outstanding);

        // D. Voiding payment group restores all entries
        app(DealerPaymentService::class)->deletePayment($payment);

        $this->assertEquals(0.00, (float) $entryOld->fresh()->dealer_collected);
        $this->assertEquals('Pending', $entryOld->fresh()->dealer_payment_status);
        $this->assertEquals(0.00, (float) $entryNew->fresh()->dealer_collected);
        $this->assertEquals('Pending', $entryNew->fresh()->dealer_payment_status);
        $this->assertEquals(215000.00, (float) $this->dealer->fresh()->displayed_outstanding);
    }

    // =========================================================================
    // 5. INVARIANT: DEPENDENCY DELETION PROTECTION FOR DEALERS, VENDORS, ITEMS
    // =========================================================================

    public function test_dependency_deletion_protection_all_entities(): void
    {
        // 1. DEALER PROTECTION
        // A. Unreferenced Dealer: deletion succeeds
        $freeDealer = Dealer::create([
            'firm_name'      => 'Free Unreferenced Dealer',
            'phone'          => '9843933333',
            'pending_amount' => 0.00,
        ]);
        $this->delete(route('masters.dealers.destroy', $freeDealer->id))->assertRedirect();
        $this->assertSoftDeleted('dealers', ['id' => $freeDealer->id]);

        // B. Dealer with DayLoadEntry: deletion MUST be blocked
        $dlDealer = Dealer::create([
            'firm_name'      => 'DL Protected Dealer',
            'phone'          => '9843944444',
            'pending_amount' => 0.00,
        ]);
        DayLoadEntry::create([
            'batch_id'      => DayLoadBatch::create(['billing_date' => now()->toDateString(), 'status' => 'Open'])->id,
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $dlDealer->id,
            'no_of_boxes'   => 10,
            'box_weight'    => 200.00,
            'empty_weight'  => 50.00,
            'customer_rate' => 120.00,
            'paper_rate'    => 100.00,
            'billing_rate'  => 100.00,
        ]);
        $this->delete(route('masters.dealers.destroy', $dlDealer->id))->assertSessionHas('error');
        $this->assertDatabaseHas('dealers', ['id' => $dlDealer->id, 'deleted_at' => null]);

        // C. Dealer with DealerPayment: deletion MUST be blocked
        $payDealer = Dealer::create([
            'firm_name'      => 'Pay Protected Dealer',
            'phone'          => '9843955555',
            'pending_amount' => 0.00,
        ]);
        DealerPayment::create([
            'dealer_id'    => $payDealer->id,
            'amount'       => 5000.00,
            'cash_amount'  => 5000.00,
            'bank_amount'  => 0.00,
            'payment_mode' => 'Cash',
            'date'         => now()->toDateString(),
        ]);
        $this->delete(route('masters.dealers.destroy', $payDealer->id))->assertSessionHas('error');
        $this->assertDatabaseHas('dealers', ['id' => $payDealer->id, 'deleted_at' => null]);

        // 2. VENDOR PROTECTION
        // A. Unreferenced Vendor: deletion succeeds
        $freeVendor = Vendor::create([
            'firm_name'      => 'Free Unreferenced Vendor',
            'phone'          => '9843966666',
            'pending_amount' => 0.00,
        ]);
        $this->delete(route('masters.vendors.destroy', $freeVendor->id))->assertRedirect();
        $this->assertSoftDeleted('vendors', ['id' => $freeVendor->id]);

        // B. Vendor with Purchase: deletion MUST be blocked
        $purVendor = Vendor::create([
            'firm_name'      => 'Purchase Protected Vendor',
            'phone'          => '9843977777',
            'pending_amount' => 0.00,
        ]);
        Purchase::create([
            'vendor_id'    => $purVendor->id,
            'vendor_name'  => $purVendor->firm_name,
            'date'         => now()->toDateString(),
            'total_amount' => 10000.00,
            'payment_mode' => 'Credit',
            'invoice_no'   => 'PUR-INV-CHALLENGE',
        ]);
        $this->delete(route('masters.vendors.destroy', $purVendor->id))->assertSessionHas('error');
        $this->assertDatabaseHas('vendors', ['id' => $purVendor->id, 'deleted_at' => null]);

        // C. Vendor with VendorAdvance: deletion MUST be blocked
        $advVendor = Vendor::create([
            'firm_name'      => 'Advance Protected Vendor',
            'phone'          => '9843988888',
            'pending_amount' => 0.00,
        ]);
        VendorAdvance::create([
            'vendor_id'       => $advVendor->id,
            'total_amount'    => 12000.00,
            'adjusted_amount' => 0.00,
            'date'            => now()->toDateString(),
            'status'          => 'Pending',
        ]);
        $this->delete(route('masters.vendors.destroy', $advVendor->id))->assertSessionHas('error');
        $this->assertDatabaseHas('vendors', ['id' => $advVendor->id, 'deleted_at' => null]);

        // 3. ITEM PROTECTION
        // A. Unreferenced Item: deletion succeeds
        $freeItem = Item::create([
            'name'            => 'Unreferenced Feed Item',
            'type'            => 'Feed',
            'category'        => 'Poultry Feed',
            'base_unit'       => 'bag',
            'conversion_rate' => 50.00,
        ]);
        $this->delete(route('inventory.items.destroy', $freeItem->id))->assertSessionHas('success');
        $this->assertDatabaseMissing('items', ['id' => $freeItem->id]);

        // B. Item with StockLedger: deletion MUST be blocked
        $stockItem = Item::create([
            'name'            => 'Stock Protected Item',
            'type'            => 'Medicine',
            'category'        => 'Antibiotics',
            'base_unit'       => 'pack',
            'conversion_rate' => 1.00,
        ]);
        StockLedger::create([
            'item_id'          => $stockItem->id,
            'quantity'         => 25,
            'type'             => 'IN',
            'source_type'      => 'Purchase',
            'source_id'        => 999,
            'unit'             => 'pack',
            'transaction_date' => now()->toDateString(),
        ]);
        $this->delete(route('inventory.items.destroy', $stockItem->id))->assertSessionHas('error');
        $this->assertDatabaseHas('items', ['id' => $stockItem->id]);
    }
}
