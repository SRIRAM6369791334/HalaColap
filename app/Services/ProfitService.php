<?php

namespace App\Services;

use App\Models\WeeklyBill;
use App\Models\DailyBill;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\Emi;
use App\Models\CustomerPayment;
use App\Models\VendorPayment;
use App\Models\DealerPayment;
use App\Models\DayLoadInvoice;
use Illuminate\Support\Facades\DB;

class ProfitService
{
    private function getFormat($col) {
        return DB::connection()->getDriverName() === 'sqlite' 
            ? "strftime('%Y-%W', " . $col . ")" 
            : "DATE_FORMAT(" . $col . ", '%Y-%u')";
    }

    private function getMonthFormat($col) {
        return DB::connection()->getDriverName() === 'sqlite' 
            ? "strftime('%Y-%m', " . $col . ")" 
            : "DATE_FORMAT(" . $col . ", '%Y-%m')";
    }

    public function getWeeklyBreakdown(?string $start = null, ?string $end = null): array
    {
        $startDate = $start ? \Carbon\Carbon::parse($start)->startOfWeek() : now()->startOfYear();
        $endDate   = $end ? \Carbon\Carbon::parse($end)->endOfWeek() : now()->endOfWeek();

        $weekFormat = $this->getFormat('period_end');
        $weekFormatDate = $this->getFormat('date');
        $weekFormatEmi = $this->getFormat('due_date');

        $wBills = WeeklyBill::selectRaw($weekFormat . " as week_key, SUM(net_amount) as amount")
            ->whereNotIn('payment_mode', ['Credit', 'Pending'])
            ->whereBetween('period_end', [$startDate, $endDate])
            ->groupByRaw($weekFormat)->get()->keyBy('week_key');

        $dBills = DailyBill::selectRaw($weekFormatDate . " as week_key, SUM(net_amount) as amount")
            ->whereNotNull('dealer_id')
            ->whereNotIn('payment_mode', ['Credit', 'Pending'])
            ->whereBetween('date', [$startDate, $endDate])
            ->groupByRaw($weekFormatDate)->get()->keyBy('week_key');

        $dlEntries = \App\Models\DayLoadEntry::with('batch')
            ->whereHas('batch', fn($q) => $q->whereBetween('billing_date', [$startDate, $endDate]))
            ->get()
            ->groupBy(function($e) {
                $dt = $e->batch ? $e->batch->billing_date->format('Y-m-d') : $e->created_at->format('Y-m-d');
                return date('Y-W', strtotime($dt));
            })->map(function($group) {
                return $group->sum(fn($e) => (float)$e->bird_weight * (float)($e->customer_rate ?: $e->rate));
            });

        $cPayments = CustomerPayment::selectRaw($weekFormatDate . " as week_key, SUM(amount) as amount")
            ->whereBetween('date', [$startDate, $endDate])
            ->groupByRaw($weekFormatDate)->get()->keyBy('week_key');

        $purchases = Purchase::selectRaw($weekFormatDate . " as week_key, SUM(total_amount) as amount")
            ->whereNotIn('payment_mode', ['Credit', 'Pending'])
            ->whereBetween('date', [$startDate, $endDate])
            ->groupByRaw($weekFormatDate)->get()->keyBy('week_key');

        $vPayments = VendorPayment::selectRaw($weekFormatDate . " as week_key, SUM(amount) as amount")
            ->whereBetween('date', [$startDate, $endDate])
            ->groupByRaw($weekFormatDate)->get()->keyBy('week_key');

        $dPayments = DealerPayment::selectRaw($weekFormatDate . " as week_key, SUM(amount) as amount")
            ->whereBetween('date', [$startDate, $endDate])
            ->groupByRaw($weekFormatDate)->get()->keyBy('week_key');

        $expenses = Expense::selectRaw($weekFormatDate . " as week_key, SUM(amount) as amount")
            ->whereBetween('date', [$startDate, $endDate])
            ->groupByRaw($weekFormatDate)->get()->keyBy('week_key');

        $toPayEmis = Emi::selectRaw($weekFormatEmi . " as week_key, SUM(amount) as amount")
            ->where('status', 'Paid')
            ->whereIn('emi_type', ['Vendor', 'Bank Loan'])
            ->whereBetween('due_date', [$startDate, $endDate])
            ->groupByRaw($weekFormatEmi)->get()->keyBy('week_key');

        $toReceiveEmis = Emi::selectRaw($weekFormatEmi . " as week_key, SUM(amount) as amount")
            ->where('status', 'Paid')
            ->whereIn('emi_type', ['Customer', 'Dealer'])
            ->whereBetween('due_date', [$startDate, $endDate])
            ->groupByRaw($weekFormatEmi)->get()->keyBy('week_key');

        $allKeys = collect();
        $curr = $startDate->copy();
        while ($curr <= $endDate) {
            $allKeys->push($curr->format('Y-W'));
            $curr->addWeek();
        }
        $allKeys = $allKeys->unique()->sort()->values();

        return $allKeys->map(function ($wk) use ($wBills, $dBills, $dlEntries, $cPayments, $purchases, $vPayments, $dPayments, $expenses, $toPayEmis, $toReceiveEmis) {
            $inflow = (float)($dlEntries[$wk] ?? 0)
                    + (float)($wBills[$wk]->amount ?? 0)
                    + (float)($dBills[$wk]->amount ?? 0)
                    + (float)($cPayments[$wk]->amount ?? 0)
                    + (float)($dPayments[$wk]->amount ?? 0)
                    + (float)($toReceiveEmis[$wk]->amount ?? 0);

            $outflow = (float)($purchases[$wk]->amount ?? 0)
                     + (float)($vPayments[$wk]->amount ?? 0)
                     + (float)($expenses[$wk]->amount ?? 0)
                     + (float)($toPayEmis[$wk]->amount ?? 0);
            
            $year = (int) substr($wk, 0, 4);
            $weekNum = (int) substr($wk, -2);
            $dt = \Carbon\Carbon::now()->setISODate($year, $weekNum);
            $wStart = $dt->copy()->startOfWeek()->format('Y-m-d');
            $wEnd = $dt->copy()->endOfWeek()->format('Y-m-d');
            $wLabel = $dt->copy()->startOfWeek()->format('d M') . ' - ' . $dt->copy()->endOfWeek()->format('d M Y');

            return [
                'week_key'   => $wk,
                'week'       => $year . ' — Week ' . $weekNum,
                'week_label' => $wLabel,
                'start_date' => $wStart,
                'end_date'   => $wEnd,
                'revenue'    => $inflow,
                'purchase'   => (float)($purchases[$wk]->amount ?? 0) + (float)($vPayments[$wk]->amount ?? 0),
                'expenses'   => (float)($expenses[$wk]->amount ?? 0) + (float)($emis[$wk]->amount ?? 0),
                'profit'     => $inflow - $outflow,
                'has_data'   => ($inflow > 0 || $outflow > 0),
            ];
        })->values()->toArray();
    }

    public function getMonthlyTrend(int $months = 6): array
    {
        $startDate = now()->subMonths($months)->startOfMonth();
        $monthFormat = $this->getMonthFormat('period_end');
        $monthFormatDate = $this->getMonthFormat('date');

        $wBills = WeeklyBill::selectRaw($monthFormat . " as month_key, SUM(net_amount) as amount")
            ->whereNotIn('payment_mode', ['Credit', 'Pending'])
            ->whereDate('period_end', '>=', $startDate)
            ->groupByRaw($monthFormat)->get()->keyBy('month_key');

        $dBills = DailyBill::selectRaw($monthFormatDate . " as month_key, SUM(net_amount) as amount")
            ->whereNotNull('dealer_id')
            ->whereNotIn('payment_mode', ['Credit', 'Pending'])
            ->whereDate('date', '>=', $startDate)
            ->groupByRaw($monthFormatDate)->get()->keyBy('month_key');

        $cPayments = CustomerPayment::selectRaw($monthFormatDate . " as month_key, SUM(amount) as amount")
            ->whereDate('date', '>=', $startDate)
            ->groupByRaw($monthFormatDate)->get()->keyBy('month_key');

        $purchases = Purchase::selectRaw($monthFormatDate . " as month_key, SUM(total_amount) as amount")
            ->whereNotIn('payment_mode', ['Credit', 'Pending'])
            ->whereDate('date', '>=', $startDate)
            ->groupByRaw($monthFormatDate)->get()->keyBy('month_key');

        $vPayments = VendorPayment::selectRaw($monthFormatDate . " as month_key, SUM(amount) as amount")
            ->whereDate('date', '>=', $startDate)
            ->groupByRaw($monthFormatDate)->get()->keyBy('month_key');

        $dPayments = DealerPayment::selectRaw($monthFormatDate . " as month_key, SUM(amount) as amount")
            ->whereDate('date', '>=', $startDate)
            ->groupByRaw($monthFormatDate)->get()->keyBy('month_key');

        $expenses = Expense::selectRaw($monthFormatDate . " as month_key, SUM(amount) as amount")
            ->whereDate('date', '>=', $startDate)
            ->groupByRaw($monthFormatDate)->get()->keyBy('month_key');

        $allKeys = collect([])
            ->merge($wBills->keys())->merge($dBills->keys())->merge($cPayments->keys())
            ->merge($purchases->keys())->merge($vPayments->keys())->merge($dPayments->keys())
            ->merge($expenses->keys())
            ->unique()->sort();

        return $allKeys->map(function($mk) use ($wBills, $dBills, $cPayments, $purchases, $vPayments, $dPayments, $expenses) {
            $inflow = (float)($wBills[$mk]->amount ?? 0)
                    + (float)($dBills[$mk]->amount ?? 0)
                    + (float)($cPayments[$mk]->amount ?? 0)
                    + (float)($dPayments[$mk]->amount ?? 0);

            $outflow = (float)($purchases[$mk]->amount ?? 0)
                     + (float)($vPayments[$mk]->amount ?? 0)
                     + (float)($expenses[$mk]->amount ?? 0);
            return [
                'month'  => date('M', strtotime($mk . '-01')),
                'profit' => $inflow - $outflow,
            ];
        })->values()->toArray();
    }

    public function getSummary(): array
    {
        $month = sprintf('%02d', now()->month);
        $year  = (string)now()->year;

        $dayLoadBilled = \App\Models\DayLoadEntry::whereHas('batch', function($q) use ($month, $year) {
            $q->whereMonth('billing_date', $month)->whereYear('billing_date', $year);
        })->get()->sum(function($entry) {
            return (float)$entry->bird_weight * (float)($entry->customer_rate ?: $entry->rate);
        });

        $revenue = $dayLoadBilled
            + DailyBill::whereNotNull('dealer_id')->whereMonth('date', $month)->whereYear('date', $year)->sum('net_amount')
            + CustomerPayment::whereMonth('date', $month)->whereYear('date', $year)->sum('amount')
            + DealerPayment::whereMonth('date', $month)->whereYear('date', $year)->sum('amount');
        
        $vendorPay  = VendorPayment::whereMonth('date', $month)->whereYear('date', $year)->sum('amount')
            + Purchase::whereMonth('date', $month)->whereYear('date', $year)->whereNotIn('payment_mode', ['Credit', 'Pending'])->sum('total_amount');
        
        $expensesAmt = Expense::whereMonth('date', $month)->whereYear('date', $year)->sum('amount');
        
        // To-Pay EMIs
        $toPayEmisAmt = Emi::where('status', 'Paid')
            ->whereIn('emi_type', ['Vendor', 'Bank Loan'])
            ->whereMonth('due_date', $month)->whereYear('due_date', $year)
            ->sum('amount');
        
        $expenses = $expensesAmt + $toPayEmisAmt;

        // To-Receive EMIs (add to Revenue)
        $toReceiveEmisAmt = Emi::where('status', 'Paid')
            ->whereIn('emi_type', ['Customer', 'Dealer'])
            ->whereMonth('due_date', $month)->whereYear('due_date', $year)
            ->sum('amount');
        
        $revenue += $toReceiveEmisAmt;

        $profit = $revenue - $vendorPay - $expenses;
        
        return [
            'revenue'     => round($revenue, 2),
            'purchase'    => round($vendorPay, 2),
            'vendor_pay'  => round($vendorPay, 2),
            'expenses'    => round($expenses, 2),
            'profit'      => round($profit, 2),
        ];
    }

    public function getProfitBreakdown($startDate, $endDate): array
    {
        // 1. Total Billed Amount (Dealer & Customer Sales)
        $dayLoadBilled = \App\Models\DayLoadEntry::whereHas('batch', function($q) use ($startDate, $endDate) {
            $q->whereBetween('billing_date', [$startDate, $endDate]);
        })->get()->sum(function($entry) {
            return (float)$entry->bird_weight * (float)($entry->customer_rate ?: $entry->rate);
        });

        $totalBilled = $dayLoadBilled
            + DailyBill::whereNotNull('dealer_id')->whereBetween('date', [$startDate, $endDate])->sum('net_amount')
            + WeeklyBill::whereBetween('period_end', [$startDate, $endDate])->where('invoice_no', 'NOT LIKE', 'INV-DL-%')->sum('net_amount')
            + DayLoadInvoice::whereBetween('invoice_date', [$startDate, $endDate])->sum('total_amount');

        // 2. Dealer Paid Amount (Total Collections Received)
        $dealerPaid = DealerPayment::whereBetween('date', [$startDate, $endDate])->sum('amount')
            + CustomerPayment::whereBetween('date', [$startDate, $endDate])->sum('amount')
            + DailyBill::whereNotNull('dealer_id')->whereBetween('date', [$startDate, $endDate])->whereNotIn('payment_mode', ['Credit', 'Pending'])->sum('net_amount');

        // 3. Dealer Pending / Payable Amount
        $dealerPending = max(0, $totalBilled - $dealerPaid);

        // 4. Total Vendor Cost (Farm Weight * Vendor Rate + Purchases)
        $dayLoadVendorCost = \App\Models\DayLoadEntry::whereHas('batch', function($q) use ($startDate, $endDate) {
            $q->whereBetween('billing_date', [$startDate, $endDate]);
        })->get()->sum(function($entry) {
            $rate = $entry->billing_rate ?: ($entry->vendor_rate ?: $entry->paper_rate);
            return (float)$entry->farm_weight * (float)$rate;
        });

        $vendorCost = $dayLoadVendorCost
            + Purchase::whereBetween('date', [$startDate, $endDate])->sum('total_amount');

        // 5. Vendor Paid Amount
        $vendorPaid = VendorPayment::whereBetween('date', [$startDate, $endDate])->sum('amount')
            + Purchase::whereBetween('date', [$startDate, $endDate])->whereNotIn('payment_mode', ['Credit', 'Pending'])->sum('total_amount');

        // 6. Vendor Pending / Payable Amount
        $vendorPending = max(0, $vendorCost - $vendorPaid);

        // 7. Total Expenses (General + Weight Loss + To-Pay EMIs)
        $toPayEmisAmt = Emi::where('status', 'Paid')
            ->whereIn('emi_type', ['Vendor', 'Bank Loan'])
            ->whereBetween('due_date', [$startDate, $endDate])
            ->sum('amount');
        
        $totalExpenses = Expense::whereBetween('date', [$startDate, $endDate])->sum('amount') + $toPayEmisAmt;

        // Add To-Receive EMIs to Total Billed (Revenue) and Dealer Paid (Cash Flow)
        $toReceiveEmisAmt = Emi::where('status', 'Paid')
            ->whereIn('emi_type', ['Customer', 'Dealer'])
            ->whereBetween('due_date', [$startDate, $endDate])
            ->sum('amount');
        
        $totalBilled += $toReceiveEmisAmt;
        $dealerPaid += $toReceiveEmisAmt;

        // 8. Net Profit / Loss
        $netProfit = $totalBilled - $vendorCost - $totalExpenses;
        $cashProfit = $dealerPaid - $vendorPaid - $totalExpenses;

        // 9. Capital Inflow & Withdrawals
        $capitalInvested = (float) \App\Models\CapitalTransaction::whereBetween('date', [$startDate, $endDate])
            ->where('type', 'Investment')
            ->sum('amount');

        $capitalWithdrawn = (float) \App\Models\CapitalTransaction::whereBetween('date', [$startDate, $endDate])
            ->where('type', 'Withdrawal')
            ->sum('amount');

        return [
            'total_billed'        => round($totalBilled, 2),
            'dealer_paid'         => round($dealerPaid, 2),
            'dealer_pending'      => round($dealerPending, 2),
            'vendor_cost'         => round($vendorCost, 2),
            'vendor_paid'         => round($vendorPaid, 2),
            'vendor_pending'      => round($vendorPending, 2),
            'total_expenses'      => round($totalExpenses, 2),
            'net_profit'          => round($netProfit, 2),
            'cash_profit'         => round($cashProfit, 2),
            'capital_invested'    => round($capitalInvested, 2),
            'capital_withdrawn'   => round($capitalWithdrawn, 2),
            // Legacy fallbacks for compatibility
            'total_collections'   => round($dealerPaid, 2),
            'total_vendor_pay'    => round($vendorPaid, 2),
            'pending_collection'  => round($dealerPending, 2),
        ];
    }

    public function getAvailableYears(): array
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $yearExpr = $isSqlite ? "strftime('%Y', billing_date)" : "YEAR(billing_date)";
        $yearExprDate = $isSqlite ? "strftime('%Y', date)" : "YEAR(date)";
        $yearExprEnd = $isSqlite ? "strftime('%Y', period_end)" : "YEAR(period_end)";

        $dYears  = \App\Models\DayLoadBatch::selectRaw("DISTINCT {$yearExpr} as yr")->pluck('yr')->filter();
        $wYears  = WeeklyBill::selectRaw("DISTINCT {$yearExprEnd} as yr")->pluck('yr')->filter();
        $dBYears = DailyBill::selectRaw("DISTINCT {$yearExprDate} as yr")->pluck('yr')->filter();
        
        $years = collect([now()->year])
            ->merge($dYears)
            ->merge($wYears)
            ->merge($dBYears)
            ->map(fn($y) => (int)$y)
            ->unique()
            ->sortDesc()
            ->values()
            ->toArray();

        return $years;
    }
}
