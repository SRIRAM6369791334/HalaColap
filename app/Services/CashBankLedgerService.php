<?php

namespace App\Services;

use App\Models\CashBankLedger;
use App\Models\DealerPayment;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CashBankLedgerService
{
    public function getOrCreateForDate(Carbon $date): CashBankLedger
    {
        return DB::transaction(function () use ($date) {
            $ledger = CashBankLedger::whereDate('ledger_date', $date)->first();

            if ($ledger) {
                return $ledger;
            }

            $previous = CashBankLedger::whereDate('ledger_date', '<', $date)
                ->orderBy('ledger_date', 'desc')
                ->first();

            if ($previous) {
                // BUG 2 FIX: Always carry the actual closing balance.
                // After approval, closing_cash_balance is already 0 (swept to bank),
                // so we don't need a special case for is_approved.
                $openingCash = (float) $previous->closing_cash_balance;
                $openingBank = (float) $previous->closing_bank_balance;
            } else {
                $openingCash = 0;
                $openingBank = 0;
            }

            return CashBankLedger::create([
                'ledger_date'           => $date,
                'opening_cash_balance'  => $openingCash,
                'opening_bank_balance'  => $openingBank,
                'cash_income'           => 0,
                'bank_income'           => 0,
                'cash_expense'          => 0,
                'bank_expense'          => 0,
                'closing_cash_balance'  => $openingCash,
                'closing_bank_balance'  => $openingBank,
                'is_approved'           => false,
            ]);
        });
    }

    public function recalculateForDate(Carbon $date): CashBankLedger
    {
        return DB::transaction(function () use ($date) {
            $ledger = $this->getOrCreateForDate($date);

            $dateStr = $date->format('Y-m-d');

            // Cash Income = Dealer payments + Customer COD payments
            $rawDealerCashIncome = (float) DealerPayment::whereDate('date', $dateStr)->sum('cash_amount');
            
            // --- NEW: Calculate Dealer Adjustment Rebate ---
            $dealerAdjustments = []; // dealer_id => amount
            $dailyBills = \App\Models\DailyBill::with('items')
                ->whereDate('date', $dateStr)
                ->whereNotNull('dealer_id')
                ->whereNotNull('customer_id')
                ->where('status', '!=', 'Cancelled')
                ->get();
                
            foreach ($dailyBills as $bill) {
                $dayLoadEntry = \App\Models\DayLoadEntry::where('dealer_id', $bill->dealer_id)
                    ->whereHas('batch', function($q) use ($dateStr) {
                        $q->whereDate('billing_date', $dateStr);
                    })->first();
                
                $dealerRate = $dayLoadEntry ? (float) $dayLoadEntry->customer_rate : 0;
                if ($dealerRate > 0) {
                    $qtySold = $bill->items->sum('quantity_kg');
                    $amt = ($qtySold * $dealerRate);
                    
                    if (!isset($dealerAdjustments[$bill->dealer_id])) {
                        $dealerAdjustments[$bill->dealer_id] = 0;
                    }
                    $dealerAdjustments[$bill->dealer_id] += $amt;
                }
            }

            $dealerAdjustmentCash = 0;
            $dealerAdjustmentBank = 0;
            foreach ($dealerAdjustments as $dealerId => $adjustmentAmount) {
                $dealerCashPaid = (float) DealerPayment::whereDate('date', $dateStr)
                    ->where('dealer_id', $dealerId)
                    ->sum('cash_amount');
                $dealerBankPaid = (float) DealerPayment::whereDate('date', $dateStr)
                    ->where('dealer_id', $dealerId)
                    ->sum('bank_amount');
                    
                $adjCash = min($adjustmentAmount, $dealerCashPaid);
                $remainingAdj = $adjustmentAmount - $adjCash;
                $adjBank = min($remainingAdj, $dealerBankPaid);
                
                $dealerAdjustmentCash += $adjCash;
                $dealerAdjustmentBank += $adjBank;
            }

            // Capital Transfers into Operating Cash & Bank
            // ONLY 'Transfer to Cash' injects funds into operating Cash in Hand
            // ONLY 'Transfer to Bank' injects funds into operating Bank Accounts
            // ('Investment' deposits into the Capital Pool Reserve, NOT daily sales income)
            $capitalCashIn  = (float) \App\Models\CapitalTransaction::whereDate('date', $dateStr)
                ->where('type', 'Transfer to Cash')
                ->where('payment_mode', 'Cash')
                ->sum('amount');

            $capitalBankIn  = (float) \App\Models\CapitalTransaction::whereDate('date', $dateStr)
                ->where('type', 'Transfer to Bank')
                ->where('payment_mode', '!=', 'Cash')
                ->sum('amount');

            // Capital Outflows (Transfers from Cash/Bank back to Pool)
            // (Note: Owner 'Withdrawal' is drawn directly from Capital Pool Reserve, not from daily operating sales cash)
            $capitalCashOut = (float) \App\Models\CapitalTransaction::whereDate('date', $dateStr)
                ->where('type', 'Transfer from Cash')
                ->sum('amount');

            $capitalBankOut = (float) \App\Models\CapitalTransaction::whereDate('date', $dateStr)
                ->where('type', 'Transfer from Bank')
                ->sum('amount');

            // Vendor Advances (Cash & Bank funding)
            $vendorAdvanceCash = (float) \App\Models\VendorAdvance::whereDate('date', $dateStr)->sum('cash_amount');
            $vendorAdvanceBank = (float) \App\Models\VendorAdvance::whereDate('date', $dateStr)->sum('bank_amount');

            $dealerCashIncome   = round($rawDealerCashIncome - $dealerAdjustmentCash, 2);
            $customerCashIncome = (float) \App\Models\CustomerPayment::whereDate('date', $dateStr)->sum('cod_amount');
            $cashIncome         = round($dealerCashIncome + $customerCashIncome + $capitalCashIn, 2);

            // Bank Income = Dealer bank payments + Customer bank transfer payments + Capital Bank In
            $rawDealerBankIncome = (float) DealerPayment::whereDate('date', $dateStr)->sum('bank_amount');
            $dealerBankIncome   = round($rawDealerBankIncome - $dealerAdjustmentBank, 2);
            $customerBankIncome = (float) \App\Models\CustomerPayment::whereDate('date', $dateStr)->sum('bank_transfer_amount');
            $bankIncome         = round($dealerBankIncome + $customerBankIncome + $capitalBankIn, 2);

            // Cash Expense = Cash Expenses + Vendor Cash Payments + Vendor Advances (Cash) + Capital Cash Out
            $expenseCash = (float) Expense::whereDate('date', $dateStr)->where('payment_method', 'Cash')->sum('amount');
            $vendorCash  = (float) \App\Models\VendorPayment::whereDate('date', $dateStr)->sum('cash_amount');
            $cashExpense = round($expenseCash + $vendorCash + $vendorAdvanceCash + $capitalCashOut, 2);

            // Bank Expense = Bank Transfer Expenses + Vendor Bank Payments + Vendor Advances (Bank) + Capital Bank Out
            $expenseBank = (float) Expense::whereDate('date', $dateStr)->where('payment_method', 'Bank Transfer')->sum('amount');
            $vendorBank  = (float) \App\Models\VendorPayment::whereDate('date', $dateStr)->sum('bank_amount');
            $bankExpense = round($expenseBank + $vendorBank + $vendorAdvanceBank + $capitalBankOut, 2);

            $ledger->update([
                'cash_income'  => $cashIncome,
                'bank_income'  => $bankIncome,
                'cash_expense' => $cashExpense,
                'bank_expense' => $bankExpense,
            ]);

            // Only update closing balances if the day is NOT approved
            // (approved days have their closing balances frozen by approve())
            if (!$ledger->is_approved) {
                $closingCash = round(
                    (float) $ledger->opening_cash_balance + $cashIncome - $cashExpense,
                    2
                );
                $closingBank = round(
                    (float) $ledger->opening_bank_balance + $bankIncome - $bankExpense,
                    2
                );

                $ledger->updateQuietly([
                    'closing_cash_balance' => $closingCash,
                    'closing_bank_balance' => $closingBank,
                ]);

                // BUG 5 FIX: Cascade-update the next day's opening balances
                // so all subsequent days reflect the correct running balance.
                $this->cascadeNextDayOpening($date, $closingCash, $closingBank);
            } else {
                // For approved days, new backdated income/expense is automatically swept into bank balance.
                $approvedCashSwept = max(0, round($cashIncome - $cashExpense, 2));
                $newClosingBank = round(
                    (float) $ledger->opening_bank_balance + $bankIncome - $bankExpense + $approvedCashSwept,
                    2
                );

                $ledger->updateQuietly([
                    'approved_amount'      => $approvedCashSwept,
                    'closing_cash_balance' => 0.00,
                    'closing_bank_balance' => $newClosingBank,
                ]);

                // Cascade current closing balances forward to future days
                $this->cascadeNextDayOpening($date, 0.00, $newClosingBank);
            }

            return $ledger->fresh();
        });
    }

    /**
     * BUG 5 FIX: After recalculating a day, update the immediately next ledger row's
     * opening balances (if it is not yet approved). This keeps the running balance chain intact.
     */
    private function cascadeNextDayOpening(Carbon $date, float $closingCash, float $closingBank): void
    {
        $nextDay = CashBankLedger::whereDate('ledger_date', '>', $date)
            ->orderBy('ledger_date', 'asc')
            ->first();

        if (!$nextDay) {
            return;
        }

        $newOpeningCash = $closingCash;
        $newOpeningBank = $closingBank;

        if ($nextDay->is_approved) {
            // For approved days, closing cash was swept to bank via approved_amount.
            // So closing cash remains 0, and closing bank = newOpeningBank + bank_income - bank_expense + approved_amount
            $newClosingCash = 0.00;
            $newClosingBank = round(
                $newOpeningBank + (float) $nextDay->bank_income - (float) $nextDay->bank_expense + (float) ($nextDay->approved_amount ?? 0),
                2
            );

            if (
                (float) $nextDay->opening_cash_balance !== $newOpeningCash ||
                (float) $nextDay->opening_bank_balance !== $newOpeningBank ||
                (float) $nextDay->closing_bank_balance !== $newClosingBank
            ) {
                $nextDay->updateQuietly([
                    'opening_cash_balance' => $newOpeningCash,
                    'opening_bank_balance' => $newOpeningBank,
                    'closing_cash_balance' => $newClosingCash,
                    'closing_bank_balance' => $newClosingBank,
                ]);

                // Recurse forward to subsequent days
                $this->cascadeNextDayOpening(
                    Carbon::parse($nextDay->ledger_date),
                    $newClosingCash,
                    $newClosingBank
                );
            }
        } else {
            // Unapproved day
            $newClosingCash = round($newOpeningCash + (float) $nextDay->cash_income - (float) $nextDay->cash_expense, 2);
            $newClosingBank = round($newOpeningBank + (float) $nextDay->bank_income - (float) $nextDay->bank_expense, 2);

            if (
                (float) $nextDay->opening_cash_balance !== $newOpeningCash ||
                (float) $nextDay->opening_bank_balance !== $newOpeningBank ||
                (float) $nextDay->closing_cash_balance !== $newClosingCash ||
                (float) $nextDay->closing_bank_balance !== $newClosingBank
            ) {
                $nextDay->updateQuietly([
                    'opening_cash_balance' => $newOpeningCash,
                    'opening_bank_balance' => $newOpeningBank,
                    'closing_cash_balance' => $newClosingCash,
                    'closing_bank_balance' => $newClosingBank,
                ]);

                // Recurse forward to subsequent days
                $this->cascadeNextDayOpening(
                    Carbon::parse($nextDay->ledger_date),
                    $newClosingCash,
                    $newClosingBank
                );
            }
        }
    }

    /**
     * Approve a day's ledger, sweeping closing_cash_balance into the bank side.
     *
     * TODO: restrict to Admin role once role-based permissions are finalized.
     *
     * @throws \RuntimeException if the ledger is already approved
     */
    public function approve(CashBankLedger $ledger, int $approvedByUserId): CashBankLedger
    {
        if ($ledger->is_approved) {
            throw new \RuntimeException(
                "Ledger for {$ledger->ledger_date->format('Y-m-d')} is already approved."
            );
        }

        return DB::transaction(function () use ($ledger, $approvedByUserId) {
            $sweepAmount = (float) $ledger->closing_cash_balance;

            $newClosingBank = round(
                (float) $ledger->closing_bank_balance + $sweepAmount,
                2
            );

            $ledger->update([
                'is_approved'          => true,
                'approved_amount'      => $sweepAmount,
                'approved_by'          => $approvedByUserId,
                'approved_at'          => now(),
                'closing_cash_balance' => 0,
                'closing_bank_balance' => $newClosingBank,
            ]);

            // Cascade so the next day correctly starts with cash=0, bank=newClosingBank
            $this->cascadeNextDayOpening(
                Carbon::parse($ledger->ledger_date),
                0,
                $newClosingBank
            );

            return $ledger->fresh();
        });
    }
}
