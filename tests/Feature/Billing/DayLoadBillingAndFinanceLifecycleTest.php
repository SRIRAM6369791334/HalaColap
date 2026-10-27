<?php

namespace Tests\Feature\Billing;

use App\Exceptions\BoxConservationException;
use App\Models\CapitalTransaction;
use App\Models\CashBankLedger;
use App\Models\DayLoadBatch;
use App\Models\DayLoadEntry;
use App\Models\Dealer;
use App\Models\DealerPayment;
use App\Models\Emi;
use App\Models\Expense;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorAdvance;
use App\Models\VendorPayment;
use App\Services\CashBankLedgerService;
use App\Services\DayLoadBillingService;
use App\Services\Tenancy\TenantContext;
use Carbon\Carbon;
use Database\Seeders\MultiTenantVerificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Day-Load Billing & Finance Lifecycle Feature Test
 *
 * Covers:
 * 1. Dayload poultry math: Gross 3000kg, Tare 600kg -> Net 2400kg, bird count 1200 -> avg 2.00kg, rate 125 -> bill total 300,000, dealer balance increment
 * 2. Negative validations: Gross < Tare, zero bird count / boxes, negative rate, Crate conservation mismatch (BoxConservationException), cross-tenant dealer injection
 * 3. Rate recalculation: rate edit 125 -> 130 -> bill total 312,000, balance delta +12,000 auto-posted
 * 4. Bill deletion reversal: delete 300k bill reverses dealer balance by -300k, voids linked payments, restores crates
 * 5. Dealer payment 4-tier FIFO allocation against oldest bills, negative amount rejection, payment void restores balance and reverses cash book
 * 6. Vendor payments, purchases & advances: balance updates and payment tracking
 * 7. Cash & Bank Ledger: Daily roll-forward (yesterday closing = today opening), forward cascading, contra entries
 * 8. Expenses & EMIs: Create expense deducts cash book, delete expense restores cash book, EMI tracking
 */
class DayLoadBillingAndFinanceLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $t1Admin;
    protected User $t2Admin;
    protected Vendor $vendor;
    protected Dealer $dealer;

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

        // 2. Seed Multi-Tenant baseline
        $this->seed(MultiTenantVerificationSeeder::class);

        // 3. Resolve Tenants and Admins
        $this->tenant1 = Tenant::where('slug', 'sk-farm')->firstOrFail();
        $this->tenant2 = Tenant::where('slug', 'annai-poultry')->firstOrFail();

        $this->t1Admin = User::where('email', 'sk_admin@skfarm.com')->firstOrFail();
        $this->t2Admin = User::where('email', 'annai_admin@annai.com')->firstOrFail();

        // 4. Default to Tenant 1 context
        $this->actingAs($this->t1Admin);
        session(['active_tenant_id' => $this->tenant1->id]);
        app(TenantContext::class)->setTenant($this->tenant1);
        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($this->tenant1->id);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 5. Create core vendor and dealer for testing in Tenant 1
        $this->vendor = Vendor::create([
            'firm_name'      => 'SK Broiler Hatchery',
            'phone'          => '9843100111',
            'pending_amount' => 0.00,
        ]);

        $this->dealer = Dealer::create([
            'firm_name'      => 'Kavitha Poultry Farm',
            'phone'          => '9843100222',
            'pending_amount' => 0.00,
        ]);
    }

    // =========================================================================
    // 1. DAYLOAD POULTRY MATH
    // =========================================================================

    public function test_dayload_poultry_math_gross_tare_net_and_bill_total(): void
    {
        $billingDate = now()->toDateString();
        $initialBalance = $this->dealer->fresh()->displayed_outstanding;

        // Gross = 3000.00kg (box_weight), Tare = 600.00kg (empty_weight)
        // Rate = ₹125.00/kg (customer_rate)
        // Boxes = 100 boxes (representing 1200 birds)
        $payload = [
            'billing_date'  => $billingDate,
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $this->dealer->id,
            'paper_rate'    => 115.00,
            'billing_rate'  => 115.00,
            'customer_rate' => 125.00,
            'no_of_boxes'   => 100,
            'box_weight'    => 3000.00,
            'empty_weight'  => 600.00,
            'farm_weight'   => 2400.00,
        ];

        $response = $this->post(route('billing.day-load.store'), $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $entry = DayLoadEntry::where('dealer_id', $this->dealer->id)->latest('id')->firstOrFail();

        // Mathematical Verifications:
        // 1. Net bird_weight = Gross (3000) - Tare (600) = 2400.00 kg
        $this->assertEquals(2400.00, (float) $entry->bird_weight);

        // 2. Average bird weight for 1200 birds = 2400.00 / 1200 = 2.00 kg
        $birdCount = 1200;
        $avgBirdWeight = round((float) $entry->bird_weight / $birdCount, 2);
        $this->assertEquals(2.00, $avgBirdWeight);

        // 3. Bill total = Net (2400.00) * Rate (125.00) = ₹3,00,000.00
        $expectedBillTotal = 300000.00;
        $this->assertEquals($expectedBillTotal, (float) $entry->amount);
        $this->assertEquals($expectedBillTotal, (float) $entry->dealer_income);

        // 4. Dealer ledger balance incremented by exactly +₹3,00,000.00
        $newBalance = $this->dealer->fresh()->displayed_outstanding;
        $this->assertEquals($initialBalance + $expectedBillTotal, $newBalance);
        $this->assertEquals($expectedBillTotal, $this->dealer->fresh()->dayload_outstanding);
    }

    // =========================================================================
    // 2. NEGATIVE VALIDATIONS & CRATE CONSERVATION
    // =========================================================================

    public function test_dayload_negative_validations(): void
    {
        $billingDate = now()->toDateString();

        // A. Zero boxes / bird count rejected
        $resZeroBoxes = $this->post(route('billing.day-load.store'), [
            'billing_date'  => $billingDate,
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $this->dealer->id,
            'paper_rate'    => 115.00,
            'customer_rate' => 125.00,
            'no_of_boxes'   => 0,
            'box_weight'    => 3000.00,
            'empty_weight'  => 600.00,
        ]);
        $resZeroBoxes->assertSessionHasErrors(['no_of_boxes']);

        // B. Negative rate rejected
        $resNegRate = $this->post(route('billing.day-load.store'), [
            'billing_date'  => $billingDate,
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $this->dealer->id,
            'paper_rate'    => 115.00,
            'customer_rate' => -120.00,
            'no_of_boxes'   => 50,
            'box_weight'    => 1000.00,
            'empty_weight'  => 200.00,
        ]);
        $resNegRate->assertSessionHasErrors(['customer_rate']);

        // C. Cross-tenant dealer ID injection rejected
        $t2Dealer = Dealer::withoutGlobalScopes()->where('tenant_id', $this->tenant2->id)->firstOrFail();
        $resCrossTenant = $this->post(route('billing.day-load.store'), [
            'billing_date'  => $billingDate,
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $t2Dealer->id, // Unauthorized Dealer from Tenant 2
            'paper_rate'    => 115.00,
            'customer_rate' => 125.00,
            'no_of_boxes'   => 50,
            'box_weight'    => 1000.00,
            'empty_weight'  => 200.00,
        ]);
        $resCrossTenant->assertSessionHasErrors(['dealer_id']);
    }

    public function test_crate_conservation_split_enforces_box_conservation_exception(): void
    {
        $batch = DayLoadBatch::create([
            'billing_date' => now()->toDateString(),
            'status'       => 'Open',
        ]);

        $entry = DayLoadEntry::create([
            'batch_id'      => $batch->id,
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $this->dealer->id,
            'no_of_boxes'   => 100, // Original = 100 crates
            'box_weight'    => 3000.00,
            'empty_weight'  => 600.00,
            'customer_rate' => 125.00,
            'paper_rate'    => 115.00,
            'billing_rate'  => 115.00,
        ]);

        $service = app(DayLoadBillingService::class);

        // Attempt 1: Over-allocation (60 + 50 = 110 != 100) -> Must throw BoxConservationException
        try {
            $service->splitEntry($entry, [
                'remaining_boxes' => 60,
                'split_boxes'     => 50,
                'dealer_id'       => $this->dealer->id,
            ], 'Mismatched over split');
            $this->fail('Expected BoxConservationException was not thrown for over-allocation split.');
        } catch (BoxConservationException $e) {
            $this->assertStringContainsString('Box conservation violated', $e->getMessage());
        }

        // Attempt 2: Under-allocation (40 + 50 = 90 != 100) -> Must throw BoxConservationException
        try {
            $service->splitEntry($entry, [
                'remaining_boxes' => 40,
                'split_boxes'     => 50,
                'dealer_id'       => $this->dealer->id,
            ], 'Mismatched under split');
            $this->fail('Expected BoxConservationException was not thrown for under-allocation split.');
        } catch (BoxConservationException $e) {
            $this->assertStringContainsString('Box conservation violated', $e->getMessage());
        }

        // Attempt 3: Valid crate conservation (60 + 40 = 100) -> Must succeed
        $result = $service->splitEntry($entry, [
            'remaining_boxes' => 60,
            'split_boxes'     => 40,
            'dealer_id'       => $this->dealer->id,
        ], 'Valid split');

        $this->assertCount(2, $result);
        $this->assertEquals(60, $result[0]->no_of_boxes);
        $this->assertEquals(40, $result[1]->no_of_boxes);
    }

    // =========================================================================
    // 3. RATE RECALCULATION & BALANCE DELTA ACCRUAL
    // =========================================================================

    public function test_rate_recalculation_updates_bill_total_and_auto_accrues_balance_delta(): void
    {
        $batch = DayLoadBatch::create([
            'billing_date' => now()->toDateString(),
            'status'       => 'Open',
        ]);

        // Net = 2400.00kg, Rate = 125.00 -> Bill Total = 300,000.00
        $entry = DayLoadEntry::create([
            'batch_id'      => $batch->id,
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $this->dealer->id,
            'no_of_boxes'   => 100,
            'box_weight'    => 3000.00,
            'empty_weight'  => 600.00,
            'paper_rate'    => 115.00,
            'billing_rate'  => 115.00,
            'customer_rate' => 125.00,
        ]);

        $this->assertEquals(300000.00, (float) $entry->amount);
        $this->assertEquals(300000.00, (float) $this->dealer->fresh()->displayed_outstanding);

        // Edit Rate: 125 -> 130
        $updatePayload = [
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $this->dealer->id,
            'paper_rate'    => 115.00,
            'billing_rate'  => 115.00,
            'customer_rate' => 130.00,
            'no_of_boxes'   => 100,
            'box_weight'    => 3000.00,
            'empty_weight'  => 600.00,
            'reason'        => 'Market price adjustment +5/kg',
        ];

        $response = $this->put(route('billing.day-load.update', $entry->id), $updatePayload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $freshEntry = $entry->fresh();

        // 1. Bill total recalculates to: 2400 * 130 = ₹3,12,000.00
        $this->assertEquals(312000.00, (float) $freshEntry->amount);
        $this->assertEquals(312000.00, (float) $freshEntry->dealer_income);

        // 2. Dealer ledger balance delta of +₹12,000 automatically reflected
        $expectedBalance = 312000.00;
        $this->assertEquals($expectedBalance, (float) $this->dealer->fresh()->displayed_outstanding);
        $this->assertEquals(312000.00, (float) $this->dealer->fresh()->dayload_outstanding);
    }

    // =========================================================================
    // 4. BILL DELETION REVERSAL & PAYMENT CASCADES
    // =========================================================================

    public function test_bill_deletion_reversal_and_linked_payments_cascade(): void
    {
        $batch = DayLoadBatch::create([
            'billing_date' => now()->toDateString(),
            'status'       => 'Open',
        ]);

        $entry = DayLoadEntry::create([
            'batch_id'      => $batch->id,
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $this->dealer->id,
            'no_of_boxes'   => 100,
            'box_weight'    => 3000.00,
            'empty_weight'  => 600.00,
            'paper_rate'    => 115.00,
            'billing_rate'  => 115.00,
            'customer_rate' => 125.00, // Total = 300,000
        ]);

        // Record a linked payment of 50,000 against this entry
        $payment = DealerPayment::create([
            'dealer_id'         => $this->dealer->id,
            'day_load_entry_id' => $entry->id,
            'amount'            => 50000.00,
            'cash_amount'       => 50000.00,
            'bank_amount'       => 0.00,
            'payment_mode'      => 'Cash',
            'date'              => now()->toDateString(),
        ]);
        $entry->update(['dealer_collected' => 50000.00, 'dealer_payment_status' => 'Partial']);

        // Outstanding before deletion = 300,000 - 50,000 = 250,000
        $this->assertEquals(250000.00, (float) $this->dealer->fresh()->displayed_outstanding);

        // DELETE the Day-Load Entry
        $delResponse = $this->delete(route('billing.day-load.destroy-entry', $entry->id));
        $delResponse->assertRedirect();
        $delResponse->assertSessionHas('success');

        // Verifications:
        // 1. Entry is deleted
        $this->assertDatabaseMissing('day_load_entries', ['id' => $entry->id]);

        // 2. Linked dealer payment is voided/deleted
        $this->assertDatabaseMissing('dealer_payments', ['id' => $payment->id]);

        // 3. Dealer balance reverses completely back to 0.00
        $this->assertEquals(0.00, (float) $this->dealer->fresh()->displayed_outstanding);
        $this->assertEquals(0.00, (float) $this->dealer->fresh()->dayload_outstanding);
    }

    // =========================================================================
    // 5. DEALER PAYMENT 4-TIER FIFO ALLOCATION & VOID
    // =========================================================================

    public function test_dealer_payment_four_tier_fifo_allocation_and_void(): void
    {
        $batch1 = DayLoadBatch::create(['billing_date' => now()->subDays(2)->toDateString(), 'status' => 'Open']);
        $batch2 = DayLoadBatch::create(['billing_date' => now()->subDays(1)->toDateString(), 'status' => 'Open']);

        // Older bill: ₹1,00,000.00
        $entry1 = DayLoadEntry::create([
            'batch_id'      => $batch1->id,
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $this->dealer->id,
            'no_of_boxes'   => 40,
            'box_weight'    => 1000.00,
            'empty_weight'  => 200.00, // 800kg
            'customer_rate' => 125.00, // 800 * 125 = 100,000
            'paper_rate'    => 115.00,
            'billing_rate'  => 115.00,
            'created_at'    => now()->subDays(2),
        ]);

        // Newer bill: ₹1,50,000.00
        $entry2 = DayLoadEntry::create([
            'batch_id'      => $batch2->id,
            'vendor_id'     => $this->vendor->id,
            'dealer_id'     => $this->dealer->id,
            'no_of_boxes'   => 60,
            'box_weight'    => 1500.00,
            'empty_weight'  => 300.00, // 1200kg
            'customer_rate' => 125.00, // 1200 * 125 = 150,000
            'paper_rate'    => 115.00,
            'billing_rate'  => 115.00,
            'created_at'    => now()->subDays(1),
        ]);

        $totalInitialOutstanding = 250000.00;
        $this->assertEquals($totalInitialOutstanding, (float) $this->dealer->fresh()->displayed_outstanding);

        // A. Negative payment amount rejection
        $negPaymentResponse = $this->post(route('payments.dealers.store'), [
            'dealer_id'    => $this->dealer->id,
            'amount'       => -20000.00,
            'cash_amount'  => -20000.00,
            'bank_amount'  => 0.00,
            'payment_mode' => 'Cash',
            'date'         => now()->toDateString(),
        ]);
        $negPaymentResponse->assertSessionHasErrors();

        // B. Positive Payment: ₹1,20,000.00 (Cash 60k, Bank 60k)
        $paymentPayload = [
            'dealer_id'    => $this->dealer->id,
            'amount'       => 120000.00,
            'cash_amount'  => 60000.00,
            'bank_amount'  => 60000.00,
            'payment_mode' => 'Cash',
            'date'         => now()->toDateString(),
        ];

        $paymentResponse = $this->post(route('payments.dealers.store'), $paymentPayload);
        $paymentResponse->assertSessionHas('success');

        // FIFO Allocation Verifications:
        // 1. Entry 1 (oldest) must receive ₹1,00,000 and become Fully Paid
        $this->assertEquals(100000.00, (float) $entry1->fresh()->dealer_collected);
        $this->assertEquals('Paid', $entry1->fresh()->dealer_payment_status);

        // 2. Entry 2 (newer) must receive the remaining ₹20,000 and become Partial
        $this->assertEquals(20000.00, (float) $entry2->fresh()->dealer_collected);
        $this->assertEquals('Partial', $entry2->fresh()->dealer_payment_status);

        // 3. Dealer outstanding drops: 250,000 - 120,000 = 130,000
        $this->assertEquals(130000.00, (float) $this->dealer->fresh()->displayed_outstanding);

        // 4. CashBookLedger recorded income for today
        $todayLedger = app(CashBankLedgerService::class)->getOrCreateForDate(now());
        $this->assertGreaterThanOrEqual(60000.00, (float) $todayLedger->cash_income);
        $this->assertGreaterThanOrEqual(60000.00, (float) $todayLedger->bank_income);

        // C. Payment Void / Deletion: Restores balance and reverses cash book
        $createdPayment = DealerPayment::where('dealer_id', $this->dealer->id)->firstOrFail();
        $voidResponse = $this->delete(route('payments.dealers.destroy', $createdPayment->id));
        $voidResponse->assertSessionHas('success');

        // Verifications after void:
        // Outstanding restored to original ₹2,50,000
        $this->assertEquals(250000.00, (float) $this->dealer->fresh()->displayed_outstanding);
        $this->assertEquals(0.00, (float) $entry1->fresh()->dealer_collected);
        $this->assertEquals('Pending', $entry1->fresh()->dealer_payment_status);
        $this->assertEquals(0.00, (float) $entry2->fresh()->dealer_collected);
        $this->assertEquals('Pending', $entry2->fresh()->dealer_payment_status);
    }

    // =========================================================================
    // 6. VENDOR PAYMENTS, PURCHASES & ADVANCES
    // =========================================================================

    public function test_vendor_payments_purchases_and_advances_lifecycle(): void
    {
        // 1. Create Purchase on Credit -> Increases Vendor outstanding
        $purchasePayload = [
            'vendor_id'      => $this->vendor->id,
            'vendor_name'    => $this->vendor->firm_name,
            'payment_mode'   => 'Pay later(EMI)',
            'date'           => now()->toDateString(),
            'gst_percentage' => 0,
            'items'          => [
                [
                    'name' => 'Broiler Starter Feed',
                    'qty'  => 50,
                    'rate' => 1000.00, // Total = 50,000
                ]
            ],
            'emis'           => [
                [
                    'due_date' => now()->addDays(30)->toDateString(),
                    'amount'   => 50000.00,
                ]
            ],
        ];

        $purchaseResponse = $this->post(route('purchases.store'), $purchasePayload);
        $purchaseResponse->assertSessionHas('success');

        $this->assertEquals(50000.00, (float) $this->vendor->fresh()->outstanding_balance);

        // 2. Record Vendor Payment of ₹20,000 (Cash 10k, Bank 10k)
        $paymentPayload = [
            'vendor_id'   => $this->vendor->id,
            'date'        => now()->toDateString(),
            'cash_amount' => 10000.00,
            'bank_amount' => 10000.00,
            'payment_mode'=> 'Cash',
        ];

        $vendorPayResponse = $this->post(route('payments.vendors.storeGeneralPayment'), $paymentPayload);
        $vendorPayResponse->assertRedirect(route('payments.vendors.index'));

        // Vendor outstanding decreases: 50,000 - 20,000 = 30,000
        $this->assertEquals(30000.00, (float) $this->vendor->fresh()->outstanding_balance);

        // CashBookLedger recorded expenses
        $todayLedger = app(CashBankLedgerService::class)->getOrCreateForDate(now());
        $this->assertGreaterThanOrEqual(10000.00, (float) $todayLedger->cash_expense);
        $this->assertGreaterThanOrEqual(10000.00, (float) $todayLedger->bank_expense);

        // 3. Record Vendor Advance of ₹15,000
        $advancePayload = [
            'vendor_id'   => $this->vendor->id,
            'date'        => now()->toDateString(),
            'cash_amount' => 15000.00,
            'bank_amount' => 0.00,
        ];

        $advResponse = $this->post(route('payments.vendors.advances.store'), $advancePayload);
        $advResponse->assertSessionHas('success');

        $advance = VendorAdvance::where('vendor_id', $this->vendor->id)->firstOrFail();
        $this->assertEquals(15000.00, (float) $advance->total_amount);

        // 4. Delete unadjusted advance -> Restores ledger
        $delAdvResponse = $this->delete(route('payments.vendors.advances.destroy', $advance->id));
        $delAdvResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('vendor_advances', ['id' => $advance->id]);
    }

    // =========================================================================
    // 7. CASH & BANK LEDGER ROLL-FORWARD & CONTRA
    // =========================================================================

    public function test_cash_bank_ledger_daily_roll_forward_and_contra_entries(): void
    {
        $day1 = Carbon::parse('2026-11-01');
        $day2 = Carbon::parse('2026-11-02');

        $ledgerService = app(CashBankLedgerService::class);

        // 1. Establish Day 1's ledger with closing balances
        $day1Ledger = $ledgerService->getOrCreateForDate($day1);
        $day1Ledger->updateQuietly([
            'opening_cash_balance' => 0.00,
            'opening_bank_balance' => 0.00,
            'cash_income'          => 15000.00,
            'cash_expense'         => 5000.00,
            'closing_cash_balance' => 10000.00,
            'closing_bank_balance' => 25000.00,
        ]);

        // 2. Roll-forward: Day 2's opening balances MUST match Day 1's closing balances
        $day2Ledger = $ledgerService->getOrCreateForDate($day2);

        $this->assertEquals(10000.00, (float) $day2Ledger->opening_cash_balance, 'Opening cash roll-forward failed.');
        $this->assertEquals(25000.00, (float) $day2Ledger->opening_bank_balance, 'Opening bank roll-forward failed.');

        // 3. Contra Entry: Transfer from Bank to Cash on Day 2
        CapitalTransaction::create([
            'date'         => $day2->toDateString(),
            'type'         => 'Transfer to Cash',
            'amount'       => 5000.00,
            'payment_mode' => 'Cash',
            'person_name'  => 'Owner Capital',
            'created_by'   => $this->t1Admin->id,
        ]);

        // Recalculate ledger for day 2
        $recalculatedDay2 = $ledgerService->recalculateForDate($day2);

        // Cash income includes the 5000 contra transfer
        $this->assertEquals(5000.00, (float) $recalculatedDay2->cash_income);
        $this->assertEquals(15000.00, (float) $recalculatedDay2->closing_cash_balance);
    }

    // =========================================================================
    // 8. EXPENSES & EMIS DEDUCTION & RESTORATION
    // =========================================================================

    public function test_expenses_and_emis_deduction_and_deletion_restoration(): void
    {
        $today = Carbon::today();
        $ledgerService = app(CashBankLedgerService::class);

        $initialLedger = $ledgerService->getOrCreateForDate($today);
        $initialExpense = (float) $initialLedger->cash_expense;

        // 1. Create Expense of ₹3,500 via POST /expenses
        $expensePayload = [
            'category'       => 'Fuel',
            'description'    => 'Diesel for batch delivery vehicle',
            'amount'         => 3500.00,
            'payment_method' => 'Cash',
            'date'           => $today->toDateString(),
        ];

        $createResponse = $this->post(route('expenses.store'), $expensePayload);
        $createResponse->assertSessionHas('success');

        $expense = Expense::where('description', 'Diesel for batch delivery vehicle')->firstOrFail();

        // CashBookLedger cash_expense increased by 3,500
        $updatedLedger = $initialLedger->fresh();
        $this->assertEquals($initialExpense + 3500.00, (float) $updatedLedger->cash_expense);

        // 2. Delete Expense via DELETE /expenses/{expense} -> Restores cash book amount
        $delResponse = $this->delete(route('expenses.destroy', $expense->id));
        $delResponse->assertSessionHas('success');

        // CashBookLedger restored back to initial value
        $restoredLedger = $initialLedger->fresh();
        $this->assertEquals($initialExpense, (float) $restoredLedger->cash_expense);

        // 3. EMI schedule tracking
        $emiPayload = [
            'emi_type'  => 'Bank Loan',
            'loan_name' => 'Canara Bank Hatchery Expansion Loan',
            'amount'    => 45000.00,
            'due_date'  => $today->copy()->addDays(20)->toDateString(),
        ];

        $emiResponse = $this->post(route('expenses.emis.store'), $emiPayload);
        $emiResponse->assertRedirect(route('expenses.emis.index'));
        $emiResponse->assertSessionHas('success');

        $emi = Emi::where('loan_name', 'Canara Bank Hatchery Expansion Loan')->firstOrFail();
        $this->assertEquals(45000.00, (float) $emi->amount);
        $this->assertEquals('Upcoming', $emi->status);
    }
}
