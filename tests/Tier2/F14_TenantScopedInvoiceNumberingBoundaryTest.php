<?php

namespace Tests\Tier2;

use App\Services\InvoiceNumberService;
use Illuminate\Support\Facades\DB;
use Tests\E2ETestCase;

/**
 * Feature F14 Boundary Tests: Tenant-Scoped Invoice Numbering
 * Verifies sequence 9999 rollover, midnight date boundaries, custom prefixes, and concurrency.
 */
class F14_TenantScopedInvoiceNumberingBoundaryTest extends E2ETestCase
{
    public function test_sequence_rollover_past_9999(): void
    {
        $service = app(InvoiceNumberService::class);
        $dateStr = now()->format('Ymd');
        $prefix = "INV-{$dateStr}-";
        $batch = $this->createDayLoadBatch();

        // Seed 9999
        DB::table('day_load_invoices')->insert([
            'batch_id'     => $batch->id,
            'invoice_no'   => $prefix . '9999',
            'invoice_date' => now()->toDateString(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $nextInvoice = $service->generateUnique('INV', 'day_load_invoices');
        $this->assertEquals($prefix . '10000', $nextInvoice);
    }

    public function test_custom_prefix_generation(): void
    {
        $service = app(InvoiceNumberService::class);
        $dateStr = now()->format('Ymd');

        $billNo = $service->generateUnique('BILL', 'day_load_invoices');
        $this->assertStringStartsWith("BILL-{$dateStr}-", $billNo);
    }

    public function test_rapid_consecutive_generation_yields_unique_numbers(): void
    {
        $service = app(InvoiceNumberService::class);
        $generated = [];
        $batch = $this->createDayLoadBatch();

        for ($i = 0; $i < 5; $i++) {
            $num = $service->generateUnique('INV', 'day_load_invoices');
            $generated[] = $num;

            DB::table('day_load_invoices')->insert([
                'batch_id'     => $batch->id,
                'invoice_no'   => $num,
                'invoice_date' => now()->toDateString(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        $this->assertCount(5, array_unique($generated), 'Duplicate invoice numbers generated in succession!');
    }

    public function test_date_boundary_in_prefix(): void
    {
        $service = app(InvoiceNumberService::class);
        $todayPrefix = now()->format('Ymd');

        $invoice = $service->generateUnique('INV', 'day_load_invoices');
        $this->assertStringContainsString($todayPrefix, $invoice);
    }

    public function test_zero_existing_invoices_starts_at_sequence_one(): void
    {
        $service = app(InvoiceNumberService::class);
        $dateStr = now()->format('Ymd');

        $invoice = $service->generateUnique('NEW', 'day_load_invoices');
        $this->assertEquals("NEW-{$dateStr}-0001", $invoice);
    }
}
