<?php

namespace App\Console\Commands;

use App\Models\CashBankLedger;
use App\Models\DealerPayment;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillCashBankLedger extends Command
{
    protected $signature = 'cash-bank-ledger:backfill
        {--from= : Start date (Y-m-d). Defaults to earliest transaction date.}
        {--to= : End date (Y-m-d). Defaults to yesterday.}';

    protected $description = 'Backfill historical cash & bank ledger for dates with transactions only';

    public function handle(): int
    {
        $from = $this->option('from') ? Carbon::parse($this->option('from')) : $this->getEarliestDate();
        $to   = $this->option('to')   ? Carbon::parse($this->option('to'))   : Carbon::yesterday();

        if (!$from) {
            $this->warn('No transaction data found — nothing to backfill.');
            return 0;
        }

        // Collect all dates that have transactions
        $transactionDates = collect()
            ->merge(DealerPayment::whereDate('date', '>=', $from)->whereDate('date', '<=', $to)->pluck('date'))
            ->merge(Expense::whereDate('date', '>=', $from)->whereDate('date', '<=', $to)->pluck('date'))
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->unique()
            ->sort()
            ->values();

        // Also include today so the current balance is queryable
        $today = Carbon::today()->format('Y-m-d');
        if ($today < $from->format('Y-m-d') || $today > $to->format('Y-m-d')) {
            $transactionDates->push($today);
        }

        $transactionDates = $transactionDates->unique()->sort()->values();

        if ($transactionDates->isEmpty()) {
            $this->warn('No transaction dates found in range.');
            return 0;
        }

        $bar = $this->output->createProgressBar($transactionDates->count());
        $bar->start();

        foreach ($transactionDates as $dateStr) {
            $this->recalculateForDate(Carbon::parse($dateStr));
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Backfilled {$transactionDates->count()} dates with transactions.");

        return 0;
    }

    private function getEarliestDate(): ?Carbon
    {
        $dealerMin = DealerPayment::min('date');
        $expenseMin = Expense::min('date');

        $dates = collect();
        if ($dealerMin) $dates->push(Carbon::parse($dealerMin));
        if ($expenseMin) $dates->push(Carbon::parse($expenseMin));

        return $dates->min();
    }

    private function recalculateForDate(Carbon $date): CashBankLedger
    {
        $ledger = CashBankLedger::whereDate('ledger_date', $date)->first();

        if (!$ledger) {
            $previous = CashBankLedger::whereDate('ledger_date', '<', $date)
                ->orderBy('ledger_date', 'desc')
                ->first();

            if ($previous) {
                $openingCash = $previous->is_approved
                    ? 0
                    : $previous->closing_cash_balance;
                $openingBank = $previous->closing_bank_balance;
            } else {
                $openingCash = 0;
                $openingBank = 0;
            }

            $ledger = CashBankLedger::create([
                'ledger_date'           => $date,
                'opening_cash_balance'  => $openingCash,
                'opening_bank_balance'  => $openingBank,
                'cash_income'           => 0,
                'bank_income'           => 0,
                'cash_expense'          => 0,
                'closing_cash_balance'  => $openingCash,
                'closing_bank_balance'  => $openingBank,
                'is_approved'           => false,
            ]);
        }

        $dateStr = $date->format('Y-m-d');

        $cashIncome = (float) DealerPayment::whereDate('date', $dateStr)
            ->sum('cash_amount');

        $bankIncome = (float) DealerPayment::whereDate('date', $dateStr)
            ->sum('bank_amount');

        $cashExpense = (float) Expense::whereDate('date', $dateStr)
            ->where('payment_method', 'Cash')
            ->sum('amount');

        $ledger->update([
            'cash_income'  => $cashIncome,
            'bank_income'  => $bankIncome,
            'cash_expense' => $cashExpense,
        ]);

        if (!$ledger->is_approved) {
            $closingCash = round(
                (float) $ledger->opening_cash_balance + $cashIncome - $cashExpense,
                2
            );
            $closingBank = round(
                (float) $ledger->opening_bank_balance + $bankIncome,
                2
            );

            $ledger->updateQuietly([
                'closing_cash_balance' => $closingCash,
                'closing_bank_balance' => $closingBank,
            ]);
        }

        return $ledger->fresh();
    }
}
