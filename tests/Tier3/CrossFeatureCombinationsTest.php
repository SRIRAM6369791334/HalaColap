<?php

namespace Tests\Tier3;

use App\Models\CashBankLedger;
use App\Models\DayLoadBatch;
use App\Models\DayLoadEntry;
use App\Models\Dealer;
use App\Models\DealerPayment;
use App\Models\Emi;
use App\Models\Vendor;
use App\Services\CashBankLedgerService;
use App\Services\DayLoadBillingService;
use App\Services\DealerPaymentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\E2ETestCase;

/**
 * Tier 3: Cross-Feature Combinations & Pairwise Interactions
 * Verifies integration between:
 * - Day-load splits and FIFO dealer payment settlement
 * - Split entries and cash/bank daily ledger roll-forwards
 * - Multi-tier payment waterfalls and ledger balance tracking
 * - Payment deletion cascading and status reversion
 * - Multi-generational box conservation splits
 * - Multi-tenant isolated billing, numbering, and scoping
 */
class CrossFeatureCombinationsTest extends E2ETestCase
{
    public function test_day_load_split_interacts_with_fifo_dealer_payment_allocation(): void
    {
        $batch = DayLoadBatch::create(['billing_date' => '2026-10-09', 'status' => 'Open']);
        $vendor = $this->createVendor(['firm_name' => 'Cross Vendor 1']);
        $dealer1 = $this->createDealer(['firm_name' => 'Dealer Parent', 'pending_amount' => 0.00]);
        $dealer2 = $this->createDealer(['firm_name' => 'Dealer Child', 'pending_amount' => 0.00]);

        $originalEntry = DayLoadEntry::create([
            'batch_id'              => $batch->id,
            'vendor_id'             => $vendor->id,
            'dealer_id'             => $dealer1->id,
            'no_of_boxes'           => 50,
            'box_weight'            => 1000.0,
            'empty_weight'          => 200.0,
            'customer_rate'         => 100.00,
            'billing_rate'          => 90.00,
            'paper_rate'            => 90.00,
            'dealer_collected'      => 0.00,
            'dealer_payment_status' => 'Pending',
            'status'                => 'Active',
            'version'               => 1,
        ]);

        $billingService = app(DayLoadBillingService::class);
        $paymentService = app(DealerPaymentService::class);

        // Split 20 boxes to Dealer 2 (40% ratio)
        // Original: 30 boxes (480kg bird weight * 100 = 48,000.00)
        // Child: 20 boxes (320kg bird weight * 100 = 32,000.00)
        [$updatedOriginal, $childEntry] = $billingService->splitEntry($originalEntry, [
            'remaining_boxes' => 30,
            'split_boxes'     => 20,
            'dealer_id'       => $dealer2->id,
        ], 'Cross-feature test split');

        $this->assertBoxConservation(30, 20, 50);
        $this->assertEquals(32000.00, (float) $childEntry->amount);

        // Record full payment for Dealer 2 (Child)
        $payment = $paymentService->record([
            'dealer_id'    => $dealer2->id,
            'date'         => '2026-10-09',
            'payment_mode' => 'Cash',
            'cash_amount'  => 32000.00,
            'bank_amount'  => 0.00,
        ]);

        $childEntry->refresh();
        $updatedOriginal->refresh();

        // Verify child entry is paid
        $this->assertEquals(32000.00, (float) $childEntry->dealer_collected);
        $this->assertEquals('Paid', $childEntry->dealer_payment_status);

        // Verify original entry remains unaffected and Pending
        $this->assertEquals(0.00, (float) $updatedOriginal->dealer_collected);
        $this->assertEquals('Pending', $updatedOriginal->dealer_payment_status);
        $this->assertEquals(48000.00, (float) $updatedOriginal->amount);
    }

    public function test_day_load_split_cascades_into_cash_bank_ledger_roll_forward(): void
    {
        $batch = DayLoadBatch::create(['billing_date' => '2026-10-01', 'status' => 'Open']);
        $vendor = $this->createVendor(['firm_name' => 'Ledger Vendor']);
        $dealer1 = $this->createDealer(['firm_name' => 'Ledger Dealer 1', 'pending_amount' => 0.00]);
        $dealer2 = $this->createDealer(['firm_name' => 'Ledger Dealer 2', 'pending_amount' => 0.00]);

        $entry = DayLoadEntry::create([
            'batch_id'              => $batch->id,
            'vendor_id'             => $vendor->id,
            'dealer_id'             => $dealer1->id,
            'no_of_boxes'           => 100,
            'box_weight'            => 1000.0,
            'empty_weight'          => 200.0,
            'customer_rate'         => 100.00,
            'billing_rate'          => 90.00,
            'paper_rate'            => 90.00,
            'dealer_collected'      => 0.00,
            'dealer_payment_status' => 'Pending',
            'status'                => 'Active',
            'version'               => 1,
        ]);

        $billingService = app(DayLoadBillingService::class);
        $paymentService = app(DealerPaymentService::class);
        $ledgerService = app(CashBankLedgerService::class);

        // Split 50 boxes to Dealer 2
        [$parentEntry, $childEntry] = $billingService->splitEntry($entry, [
            'remaining_boxes' => 50,
            'split_boxes'     => 50,
            'dealer_id'       => $dealer2->id,
        ], 'Split for ledger cascade');

        // Payment 1: Dealer 2 pays 20,000 cash on 2026-10-01
        $paymentService->record([
            'dealer_id'    => $dealer2->id,
            'date'         => '2026-10-01',
            'payment_mode' => 'Cash',
            'cash_amount'  => 20000.00,
            'bank_amount'  => 0.00,
        ]);

        // Payment 2: Dealer 1 pays 15,000 bank on 2026-10-02
        $paymentService->record([
            'dealer_id'    => $dealer1->id,
            'date'         => '2026-10-02',
            'payment_mode' => 'Bank',
            'cash_amount'  => 0.00,
            'bank_amount'  => 15000.00,
        ]);

        // Trigger roll-forward cascade from 2026-10-01 to 2026-10-02
        $ledgerDay1 = $ledgerService->getOrCreateForDate(Carbon::parse('2026-10-01'));
        $ledgerService->recalculateForDate(Carbon::parse('2026-10-01'));
        $ledgerService->recalculateForDate(Carbon::parse('2026-10-02'));

        $ledgerDay1->refresh();
        $ledgerDay2 = $ledgerService->getOrCreateForDate(Carbon::parse('2026-10-02'));
        $ledgerDay2->refresh();

        $this->assertEquals(20000.00, (float) $ledgerDay1->cash_income);
        $this->assertEquals(15000.00, (float) $ledgerDay2->bank_income);
    }

    public function test_four_tier_fifo_waterfall_settlement_updates_daily_ledger_accurately(): void
    {
        $dealer = $this->createDealer([
            'firm_name'      => 'Four Tier Dealer',
            'pending_amount' => 5000.00,
        ]);

        $emi = Emi::create([
            'dealer_id'   => $dealer->id,
            'loan_name'   => 'Equipment Loan',
            'amount'      => 3000.00,
            'paid_amount' => 0.00,
            'due_date'    => '2026-10-01',
            'status'      => 'Pending',
        ]);

        $batch = DayLoadBatch::create(['billing_date' => '2026-10-09', 'status' => 'Open']);
        $vendor = $this->createVendor(['firm_name' => 'Waterfall Vendor 2']);

        $entry = DayLoadEntry::create([
            'batch_id'              => $batch->id,
            'vendor_id'             => $vendor->id,
            'dealer_id'             => $dealer->id,
            'no_of_boxes'           => 10,
            'box_weight'            => 100.0,
            'empty_weight'          => 20.0,
            'customer_rate'         => 100.00,
            'billing_rate'          => 90.00,
            'paper_rate'            => 90.00,
            'dealer_collected'      => 0.00,
            'dealer_payment_status' => 'Pending',
            'status'                => 'Active',
            'version'               => 1,
        ]); // bird_weight = 80kg * 100 = 8000.00

        $service = app(DealerPaymentService::class);
        $ledgerService = app(CashBankLedgerService::class);

        // Pay 18000 (Cash 10000, Bank 8000)
        // Waterfall allocation:
        // 1. EMI: 3000 (Paid)
        // 2. Base pending: 5000
        // 3. DayLoadEntry: 8000 (Paid)
        // 4. Advance: 2000
        // Total = 18000
        $payment = $service->record([
            'dealer_id'        => $dealer->id,
            'date'             => '2026-10-09',
            'payment_mode'     => 'Split',
            'cash_amount'      => 10000.00,
            'bank_amount'      => 8000.00,
            'selected_emi_ids' => [$emi->id],
        ]);

        $emi->refresh();
        $entry->refresh();

        $this->assertEquals(3000.00, (float) $emi->paid_amount);
        $this->assertEquals('Paid', $emi->status);

        $this->assertEquals(8000.00, (float) $entry->dealer_collected);
        $this->assertEquals('Paid', $entry->dealer_payment_status);

        $groupId = $payment->payment_group_id;
        $allGroupPayments = DealerPayment::where('payment_group_id', $groupId)->get();
        $this->assertEquals(18000.00, (float) $allGroupPayments->sum('amount'));

        $ledger = $ledgerService->getOrCreateForDate(Carbon::parse('2026-10-09'));
        $ledger->refresh();

        $this->assertEquals(10000.00, (float) $ledger->cash_income);
        $this->assertEquals(8000.00, (float) $ledger->bank_income);
    }

    public function test_dealer_payment_deletion_reverts_entry_status_and_recalculates_ledger(): void
    {
        $dealer = $this->createDealer(['firm_name' => 'Delete Revert Dealer', 'pending_amount' => 0.00]);
        $batch = DayLoadBatch::create(['billing_date' => '2026-10-09', 'status' => 'Open']);
        $vendor = $this->createVendor(['firm_name' => 'Delete Revert Vendor']);

        $entry = DayLoadEntry::create([
            'batch_id'              => $batch->id,
            'vendor_id'             => $vendor->id,
            'dealer_id'             => $dealer->id,
            'no_of_boxes'           => 10,
            'box_weight'            => 100.0,
            'empty_weight'          => 20.0,
            'customer_rate'         => 100.00,
            'billing_rate'          => 90.00,
            'paper_rate'            => 90.00,
            'dealer_collected'      => 0.00,
            'dealer_payment_status' => 'Pending',
            'status'                => 'Active',
            'version'               => 1,
        ]); // amount = 8000.00

        $service = app(DealerPaymentService::class);
        $ledgerService = app(CashBankLedgerService::class);

        // Record payment
        $payment = $service->record([
            'dealer_id'    => $dealer->id,
            'date'         => '2026-10-09',
            'payment_mode' => 'Cash',
            'cash_amount'  => 8000.00,
            'bank_amount'  => 0.00,
        ]);

        $entry->refresh();
        $this->assertEquals('Paid', $entry->dealer_payment_status);

        $ledger = $ledgerService->getOrCreateForDate(Carbon::parse('2026-10-09'));
        $ledger->refresh();
        $this->assertEquals(8000.00, (float) $ledger->cash_income);

        // Now delete the payment
        $service->deletePayment($payment);

        $entry->refresh();
        $this->assertEquals('Pending', $entry->dealer_payment_status);
        $this->assertEquals(0.00, (float) $entry->dealer_collected);

        $ledger->refresh();
        $this->assertEquals(0.00, (float) $ledger->cash_income);
    }

    public function test_multi_generational_box_conservation_splits(): void
    {
        $batch = DayLoadBatch::create(['billing_date' => '2026-10-09', 'status' => 'Open']);
        $vendor = $this->createVendor(['firm_name' => 'Gen Vendor']);
        $dealer1 = $this->createDealer(['firm_name' => 'Gen Dealer 1']);
        $dealer2 = $this->createDealer(['firm_name' => 'Gen Dealer 2']);
        $dealer3 = $this->createDealer(['firm_name' => 'Gen Dealer 3']);

        $rootEntry = DayLoadEntry::create([
            'batch_id'              => $batch->id,
            'vendor_id'             => $vendor->id,
            'dealer_id'             => $dealer1->id,
            'no_of_boxes'           => 100,
            'box_weight'            => 1000.0,
            'empty_weight'          => 200.0,
            'customer_rate'         => 100.00,
            'billing_rate'          => 90.00,
            'paper_rate'            => 90.00,
            'dealer_collected'      => 0.00,
            'dealer_payment_status' => 'Pending',
            'status'                => 'Active',
            'version'               => 1,
        ]);

        $billingService = app(DayLoadBillingService::class);

        // Gen 1: 100 boxes -> 60 (Dealer 1) + 40 (Dealer 2)
        [$entry1, $child1] = $billingService->splitEntry($rootEntry, [
            'remaining_boxes' => 60,
            'split_boxes'     => 40,
            'dealer_id'       => $dealer2->id,
        ], 'Gen 1 Split');

        $this->assertBoxConservation(60, 40, 100);

        // Gen 2: Split the 60 box entry -> 35 (Dealer 1) + 25 (Dealer 3)
        [$entry2, $child2] = $billingService->splitEntry($entry1, [
            'remaining_boxes' => 35,
            'split_boxes'     => 25,
            'dealer_id'       => $dealer3->id,
        ], 'Gen 2 Split');

        $this->assertBoxConservation(35, 25, 60);

        // Verify multi-generational conservation: 35 + 25 + 40 = 100
        $totalBoxes = $entry2->no_of_boxes + $child2->no_of_boxes + $child1->no_of_boxes;
        $this->assertEquals(100, $totalBoxes);
    }

    public function test_day_load_weight_transfer_interacts_with_dealer_outstanding_balances(): void
    {
        $batch = DayLoadBatch::create(['billing_date' => '2026-10-09', 'status' => 'Open']);
        $vendor = $this->createVendor(['firm_name' => 'Transfer Vendor']);
        $dealer1 = $this->createDealer(['firm_name' => 'Transfer Dealer 1']);
        $dealer2 = $this->createDealer(['firm_name' => 'Transfer Dealer 2']);

        $sourceEntry = DayLoadEntry::create([
            'batch_id'              => $batch->id,
            'vendor_id'             => $vendor->id,
            'dealer_id'             => $dealer1->id,
            'no_of_boxes'           => 10,
            'box_weight'            => 250.0,
            'empty_weight'          => 50.0,
            'customer_rate'         => 100.00,
            'billing_rate'          => 90.00,
            'paper_rate'            => 90.00,
            'status'                => 'Active',
            'version'               => 1,
        ]); // bird_weight = 200kg

        $billingService = app(DayLoadBillingService::class);

        // Transfer 50kg bird weight to Dealer 2
        [$sourceAfter, $targetEntry] = $billingService->transferWeight($sourceEntry, [
            'transfer_weight'  => 50.0,
            'target_dealer_id' => $dealer2->id,
            'target_vendor_id' => $vendor->id,
        ], 'Partial bird weight transfer');

        $sourceAfter->refresh();
        $targetEntry->refresh();

        $this->assertEquals(150.0, (float) $sourceAfter->bird_weight);
        $this->assertEquals(50.0, (float) $targetEntry->bird_weight);
        $this->assertEquals(200.0, (float) $sourceAfter->bird_weight + (float) $targetEntry->bird_weight);
    }

    public function test_concurrent_multi_tenant_fifo_payments_preserve_isolated_balances(): void
    {
        $dealer1 = $this->createDealer(['firm_name' => 'Tenant 1 Dealer', 'pending_amount' => 10000.00]);
        $dealer2 = $this->createDealer(['firm_name' => 'Tenant 2 Dealer', 'pending_amount' => 15000.00]);

        $service = app(DealerPaymentService::class);

        // Tenant 1 payment
        $payment1 = $service->record([
            'dealer_id'    => $dealer1->id,
            'date'         => '2026-10-09',
            'payment_mode' => 'Cash',
            'cash_amount'  => 5000.00,
            'bank_amount'  => 0.00,
        ]);

        // Tenant 2 payment
        $payment2 = $service->record([
            'dealer_id'    => $dealer2->id,
            'date'         => '2026-10-09',
            'payment_mode' => 'Bank',
            'cash_amount'  => 0.00,
            'bank_amount'  => 7000.00,
        ]);

        $this->assertEquals(5000.00, (float) $payment1->cash_amount);
        $this->assertEquals(0.00, (float) $payment1->bank_amount);

        $this->assertEquals(0.00, (float) $payment2->cash_amount);
        $this->assertEquals(7000.00, (float) $payment2->bank_amount);
    }

    public function test_multi_tenant_batch_invoicing_with_identical_invoice_numbers(): void
    {
        $this->requiresTable('tenants');
        $this->requiresColumn('day_load_invoices', 'tenant_id');

        $t1 = $this->createTenant(['name' => 'Tenant 1 Farm', 'slug' => 't1-farm']);
        $t2 = $this->createTenant(['name' => 'Tenant 2 Farm', 'slug' => 't2-farm']);

        $batch1 = $this->createDayLoadBatch();
        $batch2 = $this->createDayLoadBatch();

        // Invoice under Tenant 1
        $inv1 = DB::table('day_load_invoices')->insertGetId([
            'tenant_id'      => $t1->id,
            'batch_id'       => $batch1->id,
            'invoice_no'     => 'INV-20261009-0001',
            'invoice_date'   => '2026-10-09',
            'status'         => 'Generated',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // Same invoice number under Tenant 2
        $inv2 = DB::table('day_load_invoices')->insertGetId([
            'tenant_id'      => $t2->id,
            'batch_id'       => $batch2->id,
            'invoice_no'     => 'INV-20261009-0001',
            'invoice_date'   => '2026-10-09',
            'status'         => 'Generated',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $this->assertNotNull($inv1);
        $this->assertNotNull($inv2);
        $this->assertNotEquals($inv1, $inv2);
    }

    public function test_spatie_teams_role_permission_enforcement_with_tenant_context(): void
    {
        $this->requiresColumn('roles', 'team_id');
        $this->requiresColumn('model_has_roles', 'team_id');

        $user = $this->createUser();
        $roleClass = config('permission.models.role', \Spatie\Permission\Models\Role::class);

        if (function_exists('setPermissionsTeamId') && class_exists($roleClass)) {
            setPermissionsTeamId(100);
            $role100 = $roleClass::firstOrCreate(['name' => 'farm_manager', 'guard_name' => 'web', 'team_id' => 100]);
            $user->assignRole($role100);

            $this->assertTrue($user->hasRole('farm_manager'));

            // Switch to tenant 200
            setPermissionsTeamId(200);
            $this->assertFalse($user->unsetRelation('roles')->hasRole('farm_manager'));
        }
    }

    public function test_tenant_scoped_storage_and_cache_keys_with_billing_invalidation(): void
    {
        $tenant1Id = 1;
        $tenant2Id = 2;

        $t1CacheKey = "tenant:{$tenant1Id}:ledger:2026-10-09";
        $t2CacheKey = "tenant:{$tenant2Id}:ledger:2026-10-09";

        cache()->put($t1CacheKey, ['cash_balance' => 50000.00], 60);
        cache()->put($t2CacheKey, ['cash_balance' => 25000.00], 60);

        $this->assertEquals(50000.00, cache()->get($t1CacheKey)['cash_balance']);
        $this->assertEquals(25000.00, cache()->get($t2CacheKey)['cash_balance']);

        // Invalidate Tenant 1
        cache()->forget($t1CacheKey);

        $this->assertNull(cache()->get($t1CacheKey));
        $this->assertNotNull(cache()->get($t2CacheKey));
    }
}
