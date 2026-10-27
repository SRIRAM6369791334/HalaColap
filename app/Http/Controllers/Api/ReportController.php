<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\Dealer;
use App\Models\Purchase;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\WeeklyBill;
use App\Models\DailyBill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends BaseApiController
{
    /**
     * Get reports dashboard summary.
     */
    public function index(): JsonResponse
    {
        $month = sprintf('%02d', now()->month);
        $year = (string) now()->year;

        $summary = [
            'total_customers'       => Customer::count(),
            'total_dealers'         => Dealer::count(),
            'total_revenue_month'   => (float) CustomerPayment::whereMonth('date', $month)->whereYear('date', $year)->sum('amount'),
            'total_purchases_month' => (float) Purchase::whereMonth('date', $month)->whereYear('date', $year)->sum('total_amount'),
            'total_expenses_month'  => (float) Expense::whereMonth('date', $month)->whereYear('date', $year)->sum('amount'),
            'pending_receivables'   => (float) Customer::where('balance', '>', 0)->sum('balance'),
            'pending_payables'      => (float) Dealer::where('pending_amount', '>', 0)->sum('pending_amount'),
        ];

        $topCustomers = Customer::orderByDesc('balance')->limit(5)->get();
        $topDealers   = Dealer::orderByDesc('pending_amount')->limit(5)->get();

        return $this->sendResponse([
            'summary'       => $summary,
            'top_customers' => $topCustomers,
            'top_dealers'   => $topDealers,
        ], 'Reports dashboard summary retrieved successfully');
    }

    /**
     * Daily sales report.
     */
    public function salesDaily(Request $request): JsonResponse
    {
        $date = $request->get('date', today()->toDateString());
        $dailyBills = DailyBill::with('customer')
            ->whereDate('date', $date)
            ->get();

        $totalSale    = $dailyBills->sum('net_amount');
        $totalGST     = $dailyBills->sum('gst_amount');
        $cashSales    = $dailyBills->where('payment_mode', 'cash')->sum('net_amount');
        $creditSales  = $dailyBills->where('payment_mode', 'credit')->sum('net_amount');

        return $this->sendResponse([
            'date'         => $date,
            'daily_bills'  => $dailyBills,
            'total_sale'   => (float) $totalSale,
            'total_gst'    => (float) $totalGST,
            'cash_sales'   => (float) $cashSales,
            'credit_sales' => (float) $creditSales,
        ], 'Daily sales report retrieved successfully');
    }

    /**
     * Weekly sales report.
     */
    public function salesWeekly(Request $request): JsonResponse
    {
        $startDate = $request->get('start', now()->startOfWeek()->toDateString());
        $endDate   = $request->get('end',   now()->endOfWeek()->toDateString());

        $bills = WeeklyBill::with('dealer')
            ->whereBetween('period_start', [$startDate, $endDate])
            ->get();

        $totalSale = $bills->sum('net_amount');
        $routeWise = $bills->groupBy('customer.route')
            ->map(fn($group) => $group->sum('net_amount'));

        return $this->sendResponse([
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'bills'      => $bills,
            'total_sale' => (float) $totalSale,
            'route_wise' => $routeWise,
        ], 'Weekly sales report retrieved successfully');
    }

    /**
     * Monthly sales report.
     */
    public function salesMonthly(Request $request): JsonResponse
    {
        $month = $request->get('month', now()->month);
        $year  = $request->get('year',  now()->year);

        $bills = DailyBill::with('customer')
            ->whereMonth('date', sprintf('%02d', $month))
            ->whereYear('date', (string)$year)
            ->get();

        $totalSale = $bills->sum('net_amount');

        return $this->sendResponse([
            'month'      => (int) $month,
            'year'       => (int) $year,
            'bills'      => $bills,
            'total_sale' => (float) $totalSale,
        ], 'Monthly sales report retrieved successfully');
    }

    /**
     * Daily purchases report.
     */
    public function purchasesDaily(Request $request): JsonResponse
    {
        $date = $request->get('date', today()->toDateString());
        $purchases = Purchase::with('vendor')
            ->whereDate('date', $date)
            ->get();

        $totalAmount  = $purchases->sum('total_amount');
        $totalGST     = $purchases->sum('gst_amount');
        $categoryWise = $purchases->groupBy('item')
            ->map(fn($g) => $g->sum('total_amount'));

        return $this->sendResponse([
            'date'          => $date,
            'purchases'     => $purchases,
            'total_amount'  => (float) $totalAmount,
            'total_gst'     => (float) $totalGST,
            'category_wise' => $categoryWise,
        ], 'Daily purchases report retrieved successfully');
    }

    /**
     * Weekly purchases report.
     */
    public function purchasesWeekly(Request $request): JsonResponse
    {
        $startDate = $request->get('start', now()->startOfWeek()->toDateString());
        $endDate   = $request->get('end',   now()->endOfWeek()->toDateString());

        $purchases = Purchase::with('vendor')
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $totalAmount  = $purchases->sum('total_amount');
        $vendorWise   = $purchases->groupBy('vendor_id')
            ->map(fn($g) => [
                'vendor' => $g->first()->vendor ? $g->first()->vendor->firm_name : 'N/A',
                'total'  => (float) $g->sum('total_amount')
            ]);

        return $this->sendResponse([
            'start_date'   => $startDate,
            'end_date'     => $endDate,
            'purchases'    => $purchases,
            'total_amount' => (float) $totalAmount,
            'vendor_wise'  => $vendorWise,
        ], 'Weekly purchases report retrieved successfully');
    }

    /**
     * Monthly purchases report.
     */
    public function purchasesMonthly(Request $request): JsonResponse
    {
        $month = $request->get('month', now()->month);
        $year  = $request->get('year',  now()->year);

        $purchases = Purchase::with('vendor')
            ->whereMonth('date', sprintf('%02d', $month))
            ->whereYear('date', (string)$year)
            ->get();

        $totalAmount  = $purchases->sum('total_amount');
        $itemWise     = $purchases->groupBy('item')
            ->map(fn($g) => (float) $g->sum('total_amount'));
        $vendorWise   = $purchases->groupBy('vendor_id')
            ->map(fn($g) => [
                'vendor' => $g->first()->vendor ? $g->first()->vendor->firm_name : 'N/A',
                'total'  => (float) $g->sum('total_amount')
            ]);

        return $this->sendResponse([
            'month'        => (int) $month,
            'year'         => (int) $year,
            'purchases'    => $purchases,
            'total_amount' => (float) $totalAmount,
            'item_wise'    => $itemWise,
            'vendor_wise'  => $vendorWise,
        ], 'Monthly purchases report retrieved successfully');
    }

    /**
     * Vendor analytics report.
     */
    public function vendorAnalytics(): JsonResponse
    {
        $vendorWise = Purchase::with('vendor')
            ->select('vendor_id', DB::raw('SUM(total_amount) as total'), DB::raw('COUNT(*) as orders'))
            ->groupBy('vendor_id')
            ->orderByDesc('total')
            ->get();

        return $this->sendResponse($vendorWise, 'Vendor analytics retrieved successfully');
    }

    /**
     * Customer ranking by balance report.
     */
    public function customerRanking(): JsonResponse
    {
        $customers = Customer::orderByDesc('balance')->paginate(20);

        return $this->sendResponse([
            'customers'  => $customers->items(),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page'    => $customers->lastPage(),
                'per_page'     => $customers->perPage(),
                'total'        => $customers->total(),
            ]
        ], 'Customer rankings retrieved successfully');
    }

    /**
     * Purchases category analytics report.
     */
    public function purchaseAnalytics(): JsonResponse
    {
        $analytics = Purchase::select('item', DB::raw('SUM(total_amount) as total'), DB::raw('SUM(quantity) as qty'))
            ->groupBy('item')
            ->get();

        return $this->sendResponse($analytics, 'Purchase items analytics retrieved successfully');
    }
}
