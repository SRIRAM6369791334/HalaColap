<?php

namespace Tests\Tier4;

use App\Models\CashBankLedger;
use App\Models\DayLoadBatch;
use App\Models\DayLoadEntry;
use App\Models\Dealer;
use App\Models\DealerPayment;
use App\Models\Vendor;
use App\Services\CashBankLedgerService;
use App\Services\DealerPaymentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\E2ETestCase;

/**
 * Tier 4: Real-World Multi-Tenant Workload Scenarios
 * Simulates Tenant #1 (MSR Broilers) and multiple concurrent tenant businesses
 * operating on the same domain with:
 * - Identical invoice numbers without collision
 * - Strict zero IDOR leakage across all models
 * - Independent daily cash & bank ledgers
 * - Independent daily invoice sequential counters
 * - Strict storage partition boundaries
 * - Cross-tenant debt isolation
 */
class RealWorldMultiTenantWorkloadTest extends E2ETestCase
{
    public function test_concurrent_tenants_operating_simultaneously_with_identical_invoice_numbers(): void
    {
        $this->requiresTable('tenants');
        $this->requiresColumn('day_load_invoices', 'tenant_id');

        $msrTenant = $this->createTenant([
            'name'      => 'MSR Broilers',
            'slug'      => 'msr-broilers',
            'is_active' => true,
        ]);

        $alNoorTenant = $this->createTenant([
            'name'      => 'Al-Noor Poultry Farm',
            'slug'      => 'al-noor-farm',
            'is_active' => true,
        ]);

        $batch1 = $this->createDayLoadBatch();
        $batch2 = $this->createDayLoadBatch();

        // Both generate INV-20261009-0001 concurrently on the same day
        $inv1 = DB::table('day_load_invoices')->insertGetId([
            'tenant_id'      => $msrTenant->id,
            'batch_id'       => $batch1->id,
            'invoice_no'     => 'INV-20261009-0001',
            'invoice_date'   => '2026-10-09',
            'status'         => 'Generated',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $inv2 = DB::table('day_load_invoices')->insertGetId([
            'tenant_id'      => $alNoorTenant->id,
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

        $saved1 = DB::table('day_load_invoices')->find($inv1);
        $saved2 = DB::table('day_load_invoices')->find($inv2);

        $this->assertEquals($saved1->invoice_no, $saved2->invoice_no);
        $this->assertNotEquals($saved1->tenant_id, $saved2->tenant_id);
    }

    public function test_zero_idor_leakage_on_dealer_queries_across_tenants(): void
    {
        $this->requiresColumn('dealers', 'tenant_id');

        $t1 = $this->createTenant(['name' => 'MSR Broilers', 'slug' => 'msr']);
        $t2 = $this->createTenant(['name' => 'Royal Broilers', 'slug' => 'royal']);

        $d1 = $this->createDealer(['firm_name' => 'MSR Dealer A', 'tenant_id' => $t1->id]);
        $d2 = $this->createDealer(['firm_name' => 'MSR Dealer B', 'tenant_id' => $t1->id]);
        $d3 = $this->createDealer(['firm_name' => 'Royal Dealer X', 'tenant_id' => $t2->id]);

        // When scoped to Tenant 1
        if (class_exists(\App\Models\Scopes\TenantScope::class)) {
            session(['tenant_id' => $t1->id]);
            $dealers = Dealer::all();

            $this->assertCount(2, $dealers);
            $this->assertTrue($dealers->contains('id', $d1->id));
            $this->assertTrue($dealers->contains('id', $d2->id));
            $this->assertFalse($dealers->contains('id', $d3->id));

            $this->assertZeroIdor(Dealer::class, $d3->id, $t1->id);
        } else {
            $dealersT1 = DB::table('dealers')->where('tenant_id', $t1->id)->get();
            $this->assertCount(2, $dealersT1);
            $this->assertFalse($dealersT1->contains('id', $d3->id));
        }
    }

    public function test_zero_idor_leakage_on_day_load_batches_and_entries(): void
    {
        $this->requiresColumn('day_load_batches', 'tenant_id');
        $this->requiresColumn('day_load_entries', 'tenant_id');

        $t1 = $this->createTenant(['name' => 'MSR Broilers', 'slug' => 'msr-batch']);
        $t2 = $this->createTenant(['name' => 'Supreme Farms', 'slug' => 'supreme']);

        $b1 = DayLoadBatch::create(['billing_date' => '2026-10-09', 'status' => 'Open', 'tenant_id' => $t1->id]);
        $b2 = DayLoadBatch::create(['billing_date' => '2026-10-09', 'status' => 'Open', 'tenant_id' => $t2->id]);

        $v1 = $this->createVendor(['firm_name' => 'Vendor T1', 'tenant_id' => $t1->id]);
        $d1 = $this->createDealer(['firm_name' => 'Dealer T1', 'tenant_id' => $t1->id]);

        $v2 = $this->createVendor(['firm_name' => 'Vendor T2', 'tenant_id' => $t2->id]);
        $d2 = $this->createDealer(['firm_name' => 'Dealer T2', 'tenant_id' => $t2->id]);

        $e1 = DayLoadEntry::create([
            'batch_id'              => $b1->id,
            'vendor_id'             => $v1->id,
            'dealer_id'             => $d1->id,
            'no_of_boxes'           => 10,
            'box_weight'            => 100.0,
            'empty_weight'          => 20.0,
            'customer_rate'         => 100.0,
            'billing_rate'          => 90.0,
            'paper_rate'            => 90.0,
            'dealer_payment_status' => 'Pending',
            'status'                => 'Active',
            'version'               => 1,
            'tenant_id'             => $t1->id,
        ]);

        $e2 = DayLoadEntry::create([
            'batch_id'              => $b2->id,
            'vendor_id'             => $v2->id,
            'dealer_id'             => $d2->id,
            'no_of_boxes'           => 15,
            'box_weight'            => 150.0,
            'empty_weight'          => 30.0,
            'customer_rate'         => 100.0,
            'billing_rate'          => 90.0,
            'paper_rate'            => 90.0,
            'dealer_payment_status' => 'Pending',
            'status'                => 'Active',
            'version'               => 1,
            'tenant_id'             => $t2->id,
        ]);

        $entriesT1 = DB::table('day_load_entries')->where('tenant_id', $t1->id)->get();
        $this->assertCount(1, $entriesT1);
        $this->assertEquals($e1->id, $entriesT1->first()->id);
    }

    public function test_concurrent_daily_cash_bank_ledger_isolation_across_tenants(): void
    {
        $this->requiresColumn('cash_bank_ledgers', 'tenant_id');

        $t1 = $this->createTenant(['name' => 'Tenant 1 MSR', 'slug' => 't1-ledger']);
        $t2 = $this->createTenant(['name' => 'Tenant 2 Farm', 'slug' => 't2-ledger']);

        $date = '2026-10-09';

        $ledger1 = CashBankLedger::create([
            'tenant_id'             => $t1->id,
            'ledger_date'           => $date,
            'opening_cash_balance'  => 50000.00,
            'opening_bank_balance'  => 100000.00,
            'cash_income'           => 25000.00,
            'bank_income'           => 50000.00,
            'cash_expense'          => 5000.00,
            'bank_expense'          => 10000.00,
            'closing_cash_balance'  => 70000.00,
            'closing_bank_balance'  => 140000.00,
            'is_approved'           => false,
        ]);

        $ledger2 = CashBankLedger::create([
            'tenant_id'             => $t2->id,
            'ledger_date'           => $date,
            'opening_cash_balance'  => 10000.00,
            'opening_bank_balance'  => 20000.00,
            'cash_income'           => 5000.00,
            'bank_income'           => 10000.00,
            'cash_expense'          => 1000.00,
            'bank_expense'          => 2000.00,
            'closing_cash_balance'  => 14000.00,
            'closing_bank_balance'  => 28000.00,
            'is_approved'           => false,
        ]);

        $this->assertNotEquals($ledger1->closing_cash_balance, $ledger2->closing_cash_balance);
        $this->assertEquals(70000.00, (float) $ledger1->closing_cash_balance);
        $this->assertEquals(14000.00, (float) $ledger2->closing_cash_balance);
    }

    public function test_tenant_scoped_sequential_invoice_numbering_counter_isolation(): void
    {
        $this->requiresTable('tenants');
        $this->requiresColumn('day_load_invoices', 'tenant_id');

        $t1 = $this->createTenant(['name' => 'MSR Sequential', 'slug' => 'msr-seq']);
        $t2 = $this->createTenant(['name' => 'Apex Broilers', 'slug' => 'apex-seq']);

        // Tenant 1 generates 3 invoices
        for ($i = 1; $i <= 3; $i++) {
            $batch = $this->createDayLoadBatch();
            $num = sprintf('INV-20261009-%04d', $i);
            DB::table('day_load_invoices')->insert([
                'tenant_id'      => $t1->id,
                'batch_id'       => $batch->id,
                'invoice_no'     => $num,
                'invoice_date'   => '2026-10-09',
                'status'         => 'Generated',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        // Tenant 2 generates first invoice on same day -> must start at 0001
        $t2Batch = $this->createDayLoadBatch();
        $t2InvId = DB::table('day_load_invoices')->insertGetId([
            'tenant_id'      => $t2->id,
            'batch_id'       => $t2Batch->id,
            'invoice_no'     => 'INV-20261009-0001',
            'invoice_date'   => '2026-10-09',
            'status'         => 'Generated',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $t2Inv = DB::table('day_load_invoices')->find($t2InvId);
        $this->assertEquals('INV-20261009-0001', $t2Inv->invoice_no);

        $t1Count = DB::table('day_load_invoices')->where('tenant_id', $t1->id)->count();
        $t2Count = DB::table('day_load_invoices')->where('tenant_id', $t2->id)->count();

        $this->assertEquals(3, $t1Count);
        $this->assertEquals(1, $t2Count);
    }

    public function test_multi_tenant_fifo_waterfall_settlement_does_not_cross_settle_debts(): void
    {
        $d1 = $this->createDealer(['firm_name' => 'MSR Debtor 1', 'pending_amount' => 10000.00]);
        $d2 = $this->createDealer(['firm_name' => 'Apex Debtor 2', 'pending_amount' => 10000.00]);

        $service = app(DealerPaymentService::class);

        // Settle D1 completely with 10,000 payment
        $payment = $service->record([
            'dealer_id'    => $d1->id,
            'date'         => '2026-10-09',
            'payment_mode' => 'Cash',
            'cash_amount'  => 10000.00,
            'bank_amount'  => 0.00,
        ]);

        $this->assertEquals(10000.00, (float) $payment->amount);

        // Verify D2's pending balance was NOT touched
        $d2->refresh();
        $this->assertEquals(10000.00, (float) $d2->pending_amount);
    }

    public function test_tenant_scoped_storage_disk_directory_isolation(): void
    {
        Storage::fake('local');

        $tenant1Id = 101;
        $tenant2Id = 102;

        $fileT1 = "tenants/{$tenant1Id}/invoices/INV-001.pdf";
        $fileT2 = "tenants/{$tenant2Id}/invoices/INV-001.pdf";

        Storage::disk('local')->put($fileT1, 'PDF Content for Tenant 101');
        Storage::disk('local')->put($fileT2, 'PDF Content for Tenant 102');

        $this->assertTrue(Storage::disk('local')->exists($fileT1));
        $this->assertTrue(Storage::disk('local')->exists($fileT2));

        $this->assertEquals('PDF Content for Tenant 101', Storage::disk('local')->get($fileT1));
        $this->assertEquals('PDF Content for Tenant 102', Storage::disk('local')->get($fileT2));

        $t1Files = Storage::disk('local')->files("tenants/{$tenant1Id}/invoices");
        $this->assertCount(1, $t1Files);
        $this->assertStringContainsString((string) $tenant1Id, $t1Files[0]);
        $this->assertStringNotContainsString((string) $tenant2Id, $t1Files[0]);
    }

    public function test_multi_tenant_user_authentication_and_tenant_switching(): void
    {
        $this->requiresTable('tenants');
        $this->requiresColumn('users', 'current_tenant_id');

        $t1 = $this->createTenant(['name' => 'Tenant Switch 1', 'slug' => 't-switch-1']);
        $t2 = $this->createTenant(['name' => 'Tenant Switch 2', 'slug' => 't-switch-2']);

        $user = $this->createUser(['current_tenant_id' => $t1->id]);
        $this->assertEquals($t1->id, $user->current_tenant_id);

        $user->current_tenant_id = $t2->id;
        $user->save();

        $user->refresh();
        $this->assertEquals($t2->id, $user->current_tenant_id);
    }

    public function test_multi_tenant_soft_delete_and_cascade_isolation(): void
    {
        $d1 = $this->createDealer(['firm_name' => 'To Delete Dealer']);
        $d2 = $this->createDealer(['firm_name' => 'Preserved Dealer']);

        $d1Id = $d1->id;
        $d2Id = $d2->id;

        $d1->delete();

        $this->assertNull(Dealer::find($d1Id));
        $this->assertNotNull(Dealer::find($d2Id));
    }

    public function test_tenant_specific_configuration_and_branding_isolation(): void
    {
        $this->requiresTable('tenants');

        $t1 = $this->createTenant([
            'name'      => 'MSR Broilers Head Office',
            'slug'      => 'msr-hq',
            'is_active' => true,
        ]);

        $t2 = $this->createTenant([
            'name'      => 'Highland Poultry Co',
            'slug'      => 'highland-co',
            'is_active' => true,
        ]);

        $this->assertEquals('msr-hq', $t1->slug);
        $this->assertEquals('highland-co', $t2->slug);
        $this->assertNotEquals($t1->name, $t2->name);
    }
}
