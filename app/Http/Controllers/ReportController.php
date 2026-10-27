<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Http\Requests\Reports\ReportDateRequest;
use App\Http\Requests\Reports\ReportPeriodRequest;
use App\Http\Requests\Reports\ReportExportRequest;
use Illuminate\View\View;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request): View
    {
        try {
            $month = sprintf('%02d', now()->month);
            $year = (string)now()->year;

            $summary = $this->reportService->getIndexSummary($month, $year);
            $topCustomers = $this->reportService->getTopCustomers();
            $topDealers = $this->reportService->getTopDealers();

            return view('reports.index', compact('summary', 'topCustomers', 'topDealers'));
        } catch (\Exception $e) {
            report($e);
            return view('reports.index')->withErrors(['error' => 'Failed to load report summary.']);
        }
    }

    public function salesDaily(ReportDateRequest $request)
    {
        try {
            $date = $request->validated('date', today()->toDateString());
            $data = $this->reportService->getDailySales($date);

            return view('reports.sales.daily', $data);
        } catch (\Exception $e) {
            report($e);
            return back()->withErrors(['error' => 'Failed to load daily sales report.']);
        }
    }

    public function salesWeekly(ReportPeriodRequest $request)
    {
        try {
            $startDate = $request->validated('start', now()->startOfWeek()->toDateString());
            $endDate   = $request->validated('end', now()->endOfWeek()->toDateString());

            $data = $this->reportService->getWeeklySales($startDate, $endDate);

            return view('reports.sales.weekly', $data);
        } catch (\Exception $e) {
            report($e);
            return back()->withErrors(['error' => 'Failed to load weekly sales report.']);
        }
    }

    public function salesMonthly(ReportPeriodRequest $request)
    {
        try {
            $month = $request->validated('month', now()->month);
            $year  = $request->validated('year', now()->year);

            $data = $this->reportService->getMonthlySales((int)$month, (int)$year);

            return view('reports.sales.monthly', $data);
        } catch (\Exception $e) {
            report($e);
            return back()->withErrors(['error' => 'Failed to load monthly sales report.']);
        }
    }

    public function purchasesDaily(ReportDateRequest $request)
    {
        try {
            $date = $request->validated('date', today()->toDateString());
            $data = $this->reportService->getDailyPurchases($date);

            return view('reports.purchases.daily', $data);
        } catch (\Exception $e) {
            report($e);
            return back()->withErrors(['error' => 'Failed to load daily purchase report.']);
        }
    }

    public function purchasesWeekly(ReportPeriodRequest $request)
    {
        try {
            $startDate = $request->validated('start', now()->startOfWeek()->toDateString());
            $endDate   = $request->validated('end', now()->endOfWeek()->toDateString());

            $data = $this->reportService->getWeeklyPurchases($startDate, $endDate);

            return view('reports.purchases.weekly', $data);
        } catch (\Exception $e) {
            report($e);
            return back()->withErrors(['error' => 'Failed to load weekly purchase report.']);
        }
    }

    public function purchasesMonthly(ReportPeriodRequest $request)
    {
        try {
            $month = $request->validated('month', now()->month);
            $year  = $request->validated('year', now()->year);

            $data = $this->reportService->getMonthlyPurchases((int)$month, (int)$year);

            return view('reports.purchases.monthly', $data);
        } catch (\Exception $e) {
            report($e);
            return back()->withErrors(['error' => 'Failed to load monthly purchase report.']);
        }
    }

    public function vendorAnalytics()
    {
        try {
            $vendorWise = $this->reportService->getVendorAnalytics();
            return view('reports.purchases.vendor-analytics', compact('vendorWise'));
        } catch (\Exception $e) {
            report($e);
            return back()->withErrors(['error' => 'Failed to load vendor analytics.']);
        }
    }

    public function exportSalesPDF(ReportExportRequest $request)
    {
        try {
            $data = $request->validated();
            return $this->reportService->generateSalesPDF(
                $data['date'] ?? null,
                $data['start'] ?? null,
                $data['end'] ?? null,
                $data['month'] ?? null,
                $data['year'] ?? null
            );
        } catch (\Exception $e) {
            report($e);
            return back()->withErrors(['error' => 'Failed to generate PDF.']);
        }
    }

    public function exportPurchasesPDF(ReportExportRequest $request)
    {
        try {
            $data = $request->validated();
            return $this->reportService->generatePurchasesPDF(
                $data['date'] ?? null,
                $data['start'] ?? null,
                $data['end'] ?? null,
                $data['month'] ?? null,
                $data['year'] ?? null
            );
        } catch (\Exception $e) {
            report($e);
            return back()->withErrors(['error' => 'Failed to generate PDF.']);
        }
    }

    public function customerRanking(): View
    {
        try {
            $customers = $this->reportService->getCustomerRanking(20);
            return view('reports.customers.ranking', compact('customers'));
        } catch (\Exception $e) {
            report($e);
            return view('reports.customers.ranking')->withErrors(['error' => 'Failed to load ranking.']);
        }
    }

    public function purchaseAnalytics(): View
    {
        try {
            $analytics = $this->reportService->getPurchaseAnalytics();
            return view('reports.purchases.analytics', compact('analytics'));
        } catch (\Exception $e) {
            report($e);
            return view('reports.purchases.analytics')->withErrors(['error' => 'Failed to load analytics.']);
        }
    }
}
