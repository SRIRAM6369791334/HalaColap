<?php

namespace App\Console\Commands;

use App\Models\CashBankLedger;
use App\Models\CustomerPayment;
use App\Models\DealerPayment;
use App\Models\Expense;
use App\Models\VendorPayment;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AuditCashBankLedger extends Command
{
    protected $signature = 'ledger:audit {--fix : Automatically recalculate and fix discrepancies}';

    protected $description = 'Audit all payment database tables and verify daily Cash & Bank Ledger consistency';

    public function handle(): int
    {
        $this->info("===============================================================================");
        $this->info("             CASH & BANK LEDGER DATABASE ANALYSIS & AUDIT REPORT               ");
        $this->info("===============================================================================\n");

        // 1. Collect all distinct dates from all payment tables & ledger
        $dealerDates   = DealerPayment::selectRaw('DATE(date) as d')->pluck('d');
        $customerDates = CustomerPayment::selectRaw('DATE(date) as d')->pluck('d');
        $vendorDates   = VendorPayment::selectRaw('DATE(date) as d')->pluck('d');
        $expenseDates  = Expense::selectRaw('DATE(date) as d')->pluck('d');
        $ledgerDates   = CashBankLedger::selectRaw('DATE(ledger_date) as d')->pluck('d');

        $allDates = $dealerDates
            ->concat($customerDates)
            ->concat($vendorDates)
            ->concat($expenseDates)
            ->concat($ledgerDates)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $this->info("Found " . $allDates->count() . " date(s) with transaction activity.\n");

        $rows = [];
        $issuesCount = 0;

        foreach ($allDates as $dateStr) {
            $date = Carbon::parse($dateStr);

            // DB Calculations from source tables
            $dealerCash   = (float) DealerPayment::whereDate('date', $dateStr)->sum('cash_amount');
            $dealerBank   = (float) DealerPayment::whereDate('date', $dateStr)->sum('bank_amount');
            $customerCash = (float) CustomerPayment::whereDate('date', $dateStr)->sum('cod_amount');
            $customerBank = (float) CustomerPayment::whereDate('date', $dateStr)->sum('bank_transfer_amount');

            $actualCashIncome = round($dealerCash + $customerCash, 2);
            $actualBankIncome = round($dealerBank + $customerBank, 2);

            $expenseCash = (float) Expense::whereDate('date', $dateStr)->where('payment_method', 'Cash')->sum('amount');
            $expenseBank = (float) Expense::whereDate('date', $dateStr)->where('payment_method', 'Bank Transfer')->sum('amount');
            $vendorCash  = (float) VendorPayment::whereDate('date', $dateStr)->sum('cash_amount');
            $vendorBank  = (float) VendorPayment::whereDate('date', $dateStr)->sum('bank_amount');

            $actualCashExpense = round($expenseCash + $vendorCash, 2);
            $actualBankExpense = round($expenseBank + $vendorBank, 2);

            // Ledger stored record
            $ledger = CashBankLedger::whereDate('ledger_date', $dateStr)->first();

            $storedCashIncome  = $ledger ? (float) $ledger->cash_income : 0.0;
            $storedBankIncome  = $ledger ? (float) $ledger->bank_income : 0.0;
            $storedCashExpense = $ledger ? (float) $ledger->cash_expense : 0.0;
            $storedBankExpense = $ledger ? (float) $ledger->bank_expense : 0.0;

            $incomeDiff  = ($actualCashIncome != $storedCashIncome) || ($actualBankIncome != $storedBankIncome);
            $expenseDiff = ($actualCashExpense != $storedCashExpense) || ($actualBankExpense != $storedBankExpense);

            $status = "OK";
            if (!$ledger) {
                $status = "MISSING LEDGER";
                $issuesCount++;
            } elseif ($incomeDiff || $expenseDiff) {
                $status = "DISCREPANCY";
                $issuesCount++;
            }

            $rows[] = [
                'date'              => $dateStr,
                'status'            => $status,
                'is_approved'       => $ledger ? ($ledger->is_approved ? 'APPROVED' : 'PENDING') : 'NONE',
                'actual_cash_inc'   => 'Rs ' . number_format($actualCashIncome, 0),
                'stored_cash_inc'   => 'Rs ' . number_format($storedCashIncome, 0),
                'actual_bank_inc'   => 'Rs ' . number_format($actualBankIncome, 0),
                'stored_bank_inc'   => 'Rs ' . number_format($storedBankIncome, 0),
                'actual_cash_exp'   => 'Rs ' . number_format($actualCashExpense, 0),
                'stored_cash_exp'   => 'Rs ' . number_format($storedCashExpense, 0),
                'closing_cash'      => $ledger ? 'Rs ' . number_format((float) $ledger->closing_cash_balance, 0) : 'N/A',
                'closing_bank'      => $ledger ? 'Rs ' . number_format((float) $ledger->closing_bank_balance, 0) : 'N/A',
            ];
        }

        $this->table(
            ['Date', 'Audit Status', 'Approval', 'Cash Inc (Act / Store)', 'Bank Inc (Act / Store)', 'Cash Exp (Act / Store)', 'Closing Cash', 'Closing Bank'],
            array_map(fn($r) => [
                $r['date'],
                $r['status'],
                $r['is_approved'],
                $r['actual_cash_inc'] . ' / ' . $r['stored_cash_inc'],
                $r['actual_bank_inc'] . ' / ' . $r['stored_bank_inc'],
                $r['actual_cash_exp'] . ' / ' . $r['stored_cash_exp'],
                $r['closing_cash'],
                $r['closing_bank'],
            ], $rows)
        );

        $this->info("\nSummary:");
        $this->info(" - Total Active Dates: " . count($rows));
        $this->info(" - Issues / Discrepancies Found: {$issuesCount}");

        if ($this->option('fix')) {
            $this->info("\nFixing discrepancies and updating CashBankLedger Service...");
            $service = app(\App\Services\CashBankLedgerService::class);
            foreach ($allDates as $dStr) {
                $service->recalculateForDate(Carbon::parse($dStr));
            }
            $this->info("ALL LEDGER DATES RECALCULATED AND SYNCHRONIZED SUCCESSFULLY!");
        }

        return 0;
    }
}
