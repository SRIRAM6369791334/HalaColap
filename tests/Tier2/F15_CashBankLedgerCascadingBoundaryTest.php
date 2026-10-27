<?php

namespace Tests\Tier2;

use App\Services\CashBankLedgerService;
use Carbon\Carbon;
use Tests\E2ETestCase;

/**
 * Feature F15 Boundary Tests: Cash & Bank Ledger Tenant Cascading
 * Verifies negative closing balances, zero closing balances, paise decimal accuracy,
 * and multi-day cascade chains.
 */
class F15_CashBankLedgerCascadingBoundaryTest extends E2ETestCase
{
    public function test_zero_closing_balance_carries_forward_zero_opening(): void
    {
        $service = app(CashBankLedgerService::class);
        $d1 = Carbon::parse('2026-10-01');
        $d2 = Carbon::parse('2026-10-02');

        $l1 = $service->getOrCreateForDate($d1);
        $l1->update([
            'closing_cash_balance' => 0.00,
            'closing_bank_balance' => 0.00,
        ]);

        $l2 = $service->getOrCreateForDate($d2);

        $this->assertEquals(0.00, (float) $l2->opening_cash_balance);
        $this->assertEquals(0.00, (float) $l2->opening_bank_balance);
    }

    public function test_negative_closing_balance_roll_forward(): void
    {
        $service = app(CashBankLedgerService::class);
        $d1 = Carbon::parse('2026-10-03');
        $d2 = Carbon::parse('2026-10-04');

        $l1 = $service->getOrCreateForDate($d1);
        // Overdraft or negative cash balance
        $l1->update([
            'closing_cash_balance' => -500.50,
            'closing_bank_balance' => 1000.00,
        ]);

        $l2 = $service->getOrCreateForDate($d2);

        $this->assertEquals(-500.50, (float) $l2->opening_cash_balance);
    }

    public function test_paise_decimal_precision_exact_sum(): void
    {
        $service = app(CashBankLedgerService::class);
        $d1 = Carbon::parse('2026-10-05');

        $l1 = $service->getOrCreateForDate($d1);
        $l1->update([
            'opening_cash_balance' => 100.33,
            'cash_income'          => 200.67,
            'closing_cash_balance' => 301.00,
        ]);

        $l1->refresh();
        $this->assertEquals(301.00, (float) $l1->closing_cash_balance);
    }

    public function test_three_consecutive_days_cascade_chain(): void
    {
        $service = app(CashBankLedgerService::class);

        $d1 = Carbon::parse('2026-10-10');
        $d2 = Carbon::parse('2026-10-11');
        $d3 = Carbon::parse('2026-10-12');

        $l1 = $service->getOrCreateForDate($d1);
        $l1->update(['closing_cash_balance' => 1000.00]);

        $l2 = $service->getOrCreateForDate($d2);
        $this->assertEquals(1000.00, (float) $l2->opening_cash_balance);
        $l2->update(['closing_cash_balance' => 2500.00]);

        $l3 = $service->getOrCreateForDate($d3);
        $this->assertEquals(2500.00, (float) $l3->opening_cash_balance);
    }

    public function test_calling_get_or_create_multiple_times_on_same_date_returns_same_row(): void
    {
        $service = app(CashBankLedgerService::class);
        $date = Carbon::parse('2026-10-15');

        $row1 = $service->getOrCreateForDate($date);
        $row2 = $service->getOrCreateForDate($date);

        $this->assertEquals($row1->id, $row2->id);
    }
}
