<?php

namespace Tests\Tier2;

use Illuminate\Support\Facades\DB;
use Tests\E2ETestCase;

/**
 * Feature F07 Boundary Tests: Composite Unique Constraints
 * Verifies high-scale collision, leap day boundaries, and year-end dates.
 */
class F07_CompositeUniqueConstraintsBoundaryTest extends E2ETestCase
{
    public function test_same_invoice_number_across_ten_tenants_simultaneously(): void
    {
        $this->requiresColumn('day_load_invoices', 'tenant_id');

        $invNo = 'INV-GLOBAL-SAME-999';
        $batch = $this->createDayLoadBatch();

        for ($i = 1; $i <= 10; $i++) {
            $tenant = $this->createTenant();
            DB::table('day_load_invoices')->insert([
                'tenant_id'    => $tenant->id,
                'batch_id'     => $batch->id,
                'invoice_no'   => $invNo,
                'invoice_date' => '2026-10-09',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        $count = DB::table('day_load_invoices')->where('invoice_no', $invNo)->count();
        $this->assertEquals(10, $count);
    }

    public function test_leap_day_date_boundary_across_tenants(): void
    {
        $this->requiresColumn('cash_bank_ledgers', 'tenant_id');

        $t1 = $this->createTenant();
        $t2 = $this->createTenant();
        $leapDay = '2028-02-29';

        DB::table('cash_bank_ledgers')->insert([
            'tenant_id'            => $t1->id,
            'ledger_date'          => $leapDay,
            'opening_cash_balance' => 0,
            'closing_cash_balance' => 0,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        DB::table('cash_bank_ledgers')->insert([
            'tenant_id'            => $t2->id,
            'ledger_date'          => $leapDay,
            'opening_cash_balance' => 0,
            'closing_cash_balance' => 0,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        $this->assertDatabaseCount('cash_bank_ledgers', 2);
    }

    public function test_year_end_and_year_start_boundaries(): void
    {
        $this->requiresColumn('cash_bank_ledgers', 'tenant_id');

        $t1 = $this->createTenant();

        DB::table('cash_bank_ledgers')->insert([
            'tenant_id'            => $t1->id,
            'ledger_date'          => '2026-12-31',
            'opening_cash_balance' => 0,
            'closing_cash_balance' => 500,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        DB::table('cash_bank_ledgers')->insert([
            'tenant_id'            => $t1->id,
            'ledger_date'          => '2027-01-01',
            'opening_cash_balance' => 500,
            'closing_cash_balance' => 1000,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        $this->assertDatabaseCount('cash_bank_ledgers', 2);
    }

    public function test_empty_invoice_number_is_rejected(): void
    {
        $this->requiresColumn('day_load_invoices', 'tenant_id');

        $t1 = $this->createTenant();
        $batch = $this->createDayLoadBatch();

        // Null invoice_no violates NOT NULL
        $this->expectException(\Exception::class);
        DB::table('day_load_invoices')->insert([
            'tenant_id'    => $t1->id,
            'batch_id'     => $batch->id,
            'invoice_no'   => null,
            'invoice_date' => '2026-10-09',
        ]);
    }

    public function test_duplicate_phone_across_tenants_is_permitted(): void
    {
        $this->requiresColumn('dealers', 'tenant_id');

        $t1 = $this->createTenant();
        $t2 = $this->createTenant();
        $sharedPhone = '9000000001';

        $d1 = $this->createDealer(['tenant_id' => $t1->id, 'phone' => $sharedPhone]);
        $d2 = $this->createDealer(['tenant_id' => $t2->id, 'phone' => $sharedPhone]);

        $this->assertNotEquals($d1->id, $d2->id);
    }
}
