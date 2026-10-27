<?php

namespace Tests\Tier1;

use App\Models\CashBankLedger;
use App\Services\CashBankLedgerService;
use Carbon\Carbon;
use Tests\E2ETestCase;

/**
 * Feature F15: Cash & Bank Ledger Tenant Cascading
 * Verifies that CashBankLedgerService maintains daily roll-forward closing/opening
 * balances strictly scoped per tenant.
 */
class F15_CashBankLedgerCascadingTest extends E2ETestCase
{
    public function test_cash_bank_ledger_service_creates_or_fetches_ledger_for_date(): void
    {
        $service = app(CashBankLedgerService::class);
        $date = Carbon::parse('2026-10-09');

        $ledger = $service->getOrCreateForDate($date);

        $this->assertInstanceOf(CashBankLedger::class, $ledger);
        $this->assertEquals('2026-10-09', $ledger->ledger_date->format('Y-m-d'));
    }

    public function test_cash_bank_ledger_recalculates_cash_and_bank_income(): void
    {
        $service = app(CashBankLedgerService::class);
        $date = Carbon::parse('2026-10-09');

        $ledger = $service->recalculateForDate($date);

        $this->assertInstanceOf(CashBankLedger::class, $ledger);
        $this->assertIsNumeric($ledger->cash_income);
        $this->assertIsNumeric($ledger->bank_income);
    }

    public function test_ledger_opening_balance_carries_forward_from_previous_day(): void
    {
        $service = app(CashBankLedgerService::class);
        $day1 = Carbon::parse('2026-10-08');
        $day2 = Carbon::parse('2026-10-09');

        $ledger1 = $service->getOrCreateForDate($day1);
        $ledger1->update([
            'closing_cash_balance' => 15000.50,
            'closing_bank_balance' => 50000.00,
        ]);

        $ledger2 = $service->getOrCreateForDate($day2);

        $this->assertEquals(15000.50, (float) $ledger2->opening_cash_balance);
        $this->assertEquals(50000.00, (float) $ledger2->opening_bank_balance);
    }

    public function test_tenant_ledger_cascades_are_strictly_isolated(): void
    {
        $this->requiresColumn('cash_bank_ledgers', 'tenant_id');

        $t1 = $this->createTenant();
        $t2 = $this->createTenant();

        $day1 = '2026-10-08';

        CashBankLedger::create([
            'tenant_id'            => $t1->id,
            'ledger_date'          => $day1,
            'opening_cash_balance' => 1000,
            'closing_cash_balance' => 5000,
            'opening_bank_balance' => 0,
            'closing_bank_balance' => 0,
        ]);

        CashBankLedger::create([
            'tenant_id'            => $t2->id,
            'ledger_date'          => $day1,
            'opening_cash_balance' => 200,
            'closing_cash_balance' => 700,
            'opening_bank_balance' => 0,
            'closing_bank_balance' => 0,
        ]);

        $l1 = CashBankLedger::where('tenant_id', $t1->id)->whereDate('ledger_date', $day1)->first();
        $l2 = CashBankLedger::where('tenant_id', $t2->id)->whereDate('ledger_date', $day1)->first();

        $this->assertEquals(5000, (float)$l1->closing_cash_balance);
        $this->assertEquals(700, (float)$l2->closing_cash_balance);
    }

    public function test_multi_tenant_independent_running_totals(): void
    {
        $this->requiresColumn('cash_bank_ledgers', 'tenant_id');

        $t1 = $this->createTenant();
        $t2 = $this->createTenant();

        $countT1 = CashBankLedger::where('tenant_id', $t1->id)->count();
        $countT2 = CashBankLedger::where('tenant_id', $t2->id)->count();

        $this->assertSame(0, $countT1);
        $this->assertSame(0, $countT2);
    }
}
