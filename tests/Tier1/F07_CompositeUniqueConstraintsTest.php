<?php

namespace Tests\Tier1;

use Illuminate\Support\Facades\DB;
use Tests\E2ETestCase;

/**
 * Feature F07: Composite Unique Constraints
 * Verifies that single-tenant global unique indexes are replaced with composite
 * UNIQUE(tenant_id, <column>) indexes.
 */
class F07_CompositeUniqueConstraintsTest extends E2ETestCase
{
    public function test_day_load_invoices_allow_duplicate_invoice_no_across_different_tenants(): void
    {
        $this->requiresColumn('day_load_invoices', 'tenant_id');

        $t1 = $this->createTenant();
        $t2 = $this->createTenant();
        $batch = $this->createDayLoadBatch();

        $invNo = 'INV-TEST-0001';

        // Insert into tenant 1
        DB::table('day_load_invoices')->insert([
            'tenant_id'    => $t1->id,
            'batch_id'     => $batch->id,
            'invoice_no'   => $invNo,
            'invoice_date' => '2026-10-09',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Insert into tenant 2 with identical invoice number
        DB::table('day_load_invoices')->insert([
            'tenant_id'    => $t2->id,
            'batch_id'     => $batch->id,
            'invoice_no'   => $invNo,
            'invoice_date' => '2026-10-09',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $this->assertDatabaseCount('day_load_invoices', 2);
    }

    public function test_day_load_invoices_reject_duplicate_invoice_no_within_same_tenant(): void
    {
        $this->requiresColumn('day_load_invoices', 'tenant_id');

        $t1 = $this->createTenant();
        $batch = $this->createDayLoadBatch();
        $invNo = 'INV-TEST-SAME-0001';

        DB::table('day_load_invoices')->insert([
            'tenant_id'    => $t1->id,
            'batch_id'     => $batch->id,
            'invoice_no'   => $invNo,
            'invoice_date' => '2026-10-09',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $this->expectException(\Exception::class);
        DB::table('day_load_invoices')->insert([
            'tenant_id'    => $t1->id,
            'batch_id'     => $batch->id,
            'invoice_no'   => $invNo,
            'invoice_date' => '2026-10-09',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    public function test_cash_bank_ledgers_allow_same_date_across_different_tenants(): void
    {
        $this->requiresColumn('cash_bank_ledgers', 'tenant_id');

        $t1 = $this->createTenant();
        $t2 = $this->createTenant();
        $date = '2026-10-09';

        DB::table('cash_bank_ledgers')->insert([
            'tenant_id'            => $t1->id,
            'ledger_date'          => $date,
            'opening_cash_balance' => 100,
            'closing_cash_balance' => 100,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        DB::table('cash_bank_ledgers')->insert([
            'tenant_id'            => $t2->id,
            'ledger_date'          => $date,
            'opening_cash_balance' => 200,
            'closing_cash_balance' => 200,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        $this->assertDatabaseCount('cash_bank_ledgers', 2);
    }

    public function test_cash_bank_ledgers_reject_duplicate_date_within_same_tenant(): void
    {
        $this->requiresColumn('cash_bank_ledgers', 'tenant_id');

        $t1 = $this->createTenant();
        $date = '2026-10-09';

        DB::table('cash_bank_ledgers')->insert([
            'tenant_id'            => $t1->id,
            'ledger_date'          => $date,
            'opening_cash_balance' => 100,
            'closing_cash_balance' => 100,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        $this->expectException(\Exception::class);
        DB::table('cash_bank_ledgers')->insert([
            'tenant_id'            => $t1->id,
            'ledger_date'          => $date,
            'opening_cash_balance' => 100,
            'closing_cash_balance' => 100,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
    }

    public function test_dealers_can_share_name_across_different_tenants(): void
    {
        $this->requiresColumn('dealers', 'tenant_id');

        $t1 = $this->createTenant();
        $t2 = $this->createTenant();

        $d1Id = DB::table('dealers')->insertGetId([
            'tenant_id'  => $t1->id,
            'firm_name'  => 'Standard Dealer',
            'phone'      => '9876543210',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $d2Id = DB::table('dealers')->insertGetId([
            'tenant_id'  => $t2->id,
            'firm_name'  => 'Standard Dealer',
            'phone'      => '9876543210',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNotEquals($d1Id, $d2Id);
    }
}
