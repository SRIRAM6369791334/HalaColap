<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Dealer;
use App\Models\Purchase;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\WeeklyBill;
use App\Models\DailyBill;
use App\Models\DayLoadInvoice;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportService
{
    public function getIndexSummary(string $month, string $year): array
    {
        // INFLOW: Customer (DailyBill + CustomerPayment) + Dealer (WeeklyBill + DealerPayment) + Day-load invoices
        $dailyBillRevenue  = DailyBill::whereNotNull('dealer_id')->whereMonth('date', $month)->whereYear('date', $year)->sum('net_amount');
        $weeklyBillRevenue = WeeklyBill::whereMonth('period_end', $month)->whereYear('period_end', $year)->sum('net_amount');
        $cPayRevenue       = CustomerPayment::whereMonth('date', $month)->whereYear('date', $year)->sum('amount');
        $dPayRevenue       = \App\Models\DealerPayment::whereMonth('date', $month)->whereYear('date', $year)->whereNull('invoice_id')->sum('amount');
        $dlRevenue         = DayLoadInvoice::whereMonth('invoice_date', $month)->whereYear('invoice_date', $year)->sum('total_amount');

        return [
            'total_customers'       => Customer::count(),
            'total_dealers'         => Dealer::count(),
            'total_revenue_month'   => $dailyBillRevenue + $weeklyBillRevenue + $cPayRevenue + $dPayRevenue + $dlRevenue,
            'total_purchases_month' => Purchase::whereMonth('date', $month)->whereYear('date', $year)->sum('total_amount'),
            'total_expenses_month'  => Expense::whereMonth('date', $month)->whereYear('date', $year)->sum('amount'),
            'pending_receivables'   => Customer::where('balance', '>', 0)->sum('balance'),
            'pending_payables'      => Dealer::where('pending_amount', '>', 0)->sum('pending_amount'),
        ];
    }

    public function getTopCustomers(int $limit = 5)
    {
        return Customer::orderByDesc('balance')->limit($limit)->get();
    }

    public function getTopDealers(int $limit = 5)
    {
        return Dealer::orderByDesc('pending_amount')->limit($limit)->get();
    }

    public function getCustomerRanking(int $perPage = 20)
    {
        return Customer::orderByDesc('balance')->paginate($perPage);
    }

    public function getDailySales(string $date): array
    {
        $dailyBills = DailyBill::whereNotNull('dealer_id')->with('customer')
            ->whereDate('date', $date)
            ->get();

        $totalSale    = $dailyBills->sum('net_amount');
        $totalGST     = $dailyBills->sum('gst_amount');
        $cashSales    = $dailyBills->where('payment_mode', 'cash')->sum('net_amount');
        $creditSales  = $dailyBills->where('payment_mode', 'credit')->sum('net_amount');

        return compact('dailyBills', 'totalSale', 'totalGST', 'cashSales', 'creditSales', 'date');
    }

    public function getWeeklySales(string $startDate, string $endDate): array
    {
        $bills = WeeklyBill::with('dealer')
            ->whereBetween('period_start', [$startDate, $endDate])
            ->get();

        $totalSale = $bills->sum('net_amount');
        $routeWise = $bills->groupBy('dealer.route')
            ->map(fn($group) => $group->sum('net_amount'));

        return compact('bills', 'totalSale', 'routeWise', 'startDate', 'endDate');
    }

    public function getMonthlySales(int $month, int $year): array
    {
        // Customer retail sales (DailyBill)
        $dailyBills = DailyBill::with('customer')
            ->whereMonth('date', sprintf('%02d', $month))
            ->whereYear('date', (string)$year)
            ->get();

        // Dealer wholesale sales (WeeklyBill)
        $weeklyBills = WeeklyBill::with('dealer')
            ->whereMonth('period_end', sprintf('%02d', $month))
            ->whereYear('period_end', (string)$year)
            ->get();

        $totalSale = $dailyBills->sum('net_amount') + $weeklyBills->sum('net_amount');

        // Keep $bills as dailyBills for backward-compatible view rendering
        $bills = $dailyBills;

        return compact('bills', 'weeklyBills', 'totalSale', 'month', 'year');
    }

    public function getDailyPurchases(string $date): array
    {
        $purchases = Purchase::with('vendor')
            ->whereDate('date', $date)
            ->get();

        $totalAmount  = $purchases->sum('total_amount');
        $totalGST     = $purchases->sum('gst_amount');
        $categoryWise = $purchases->groupBy('item')
            ->map(fn($g) => $g->sum('total_amount'));

        return compact('purchases', 'totalAmount', 'totalGST', 'categoryWise', 'date');
    }

    public function getWeeklyPurchases(string $startDate, string $endDate): array
    {
        $purchases = Purchase::with('vendor')
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $totalAmount  = $purchases->sum('total_amount');
        $vendorWise   = $purchases->groupBy('vendor_id')
            ->map(fn($g) => [
                'vendor' => $g->first()->vendor ? $g->first()->vendor->firm_name : 'N/A',
                'total'  => $g->sum('total_amount')
            ]);

        return compact('purchases', 'totalAmount', 'vendorWise', 'startDate', 'endDate');
    }

    public function getMonthlyPurchases(int $month, int $year): array
    {
        $purchases = Purchase::with('vendor')
            ->whereMonth('date', sprintf('%02d', $month))
            ->whereYear('date', (string)$year)
            ->get();

        $totalAmount  = $purchases->sum('total_amount');
        $itemWise     = $purchases->groupBy('item')
            ->map(fn($g) => $g->sum('total_amount'));
        $vendorWise   = $purchases->groupBy('vendor_id')
            ->map(fn($g) => [
                'vendor' => $g->first()->vendor ? $g->first()->vendor->firm_name : 'N/A',
                'total'  => $g->sum('total_amount')
            ]);

        return compact('purchases', 'totalAmount', 'itemWise', 'vendorWise', 'month', 'year');
    }

    public function getVendorAnalytics()
    {
        return Purchase::with('vendor')
            ->select('vendor_id', DB::raw('SUM(total_amount) as total'), DB::raw('COUNT(*) as orders'))
            ->groupBy('vendor_id')->orderByDesc('total')->get();
    }

    public function getPurchaseAnalytics()
    {
        return PurchaseItem::select('item_name as item', DB::raw('SUM(total_amount) as total'), DB::raw('SUM(quantity) as qty'))
            ->groupBy('item_name')
            ->get();
    }

    public function generateSalesPDF(?string $date, ?string $start, ?string $end, ?string $month, ?string $year)
    {
        $data = collect();
        $title = "Sales Report";

        if ($date) {
            $data = DailyBill::whereNotNull('dealer_id')->with('customer')->whereDate('date', $date)->get();
            $title = "Daily Sales Report - " . $date;
        } elseif ($start && $end) {
            $data = WeeklyBill::with('dealer')->whereBetween('period_start', [$start, $end])->get();
            $title = "Weekly Sales Report (" . $start . " to " . $end . ")";
        } elseif ($month && $year) {
            $data = DailyBill::whereNotNull('dealer_id')->with('customer')->whereMonth('date', sprintf('%02d', $month))->whereYear('date', (string)$year)->get();
            $title = "Monthly Sales Report - " . date('F', mktime(0, 0, 0, (int)$month, 1)) . " " . $year;
        }

        return Pdf::loadView('reports.pdf.sales', compact('data', 'title'))->download(strtolower(str_replace(' ', '_', $title)) . '.pdf');
    }

    public function generatePurchasesPDF(?string $date, ?string $start, ?string $end, ?string $month, ?string $year)
    {
        $data = collect();
        $title = "Purchase Report";

        if ($date) {
            $data = Purchase::with('vendor')->whereDate('date', $date)->get();
            $title = "Daily Purchase Report - " . $date;
        } elseif ($start && $end) {
            $data = Purchase::with('vendor')->whereBetween('date', [$start, $end])->get();
            $title = "Weekly Purchase Report (" . $start . " to " . $end . ")";
        } elseif ($month && $year) {
            $data = Purchase::with('vendor')->whereMonth('date', sprintf('%02d', $month))->whereYear('date', (string)$year)->get();
            $title = "Monthly Purchase Report - " . date('F', mktime(0, 0, 0, (int)$month, 1)) . " " . $year;
        }

        return Pdf::loadView('reports.pdf.purchases', compact('data', 'title'))->download(strtolower(str_replace(' ', '_', $title)) . '.pdf');
    }
}
