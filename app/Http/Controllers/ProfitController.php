<?php

namespace App\Http\Controllers;

use App\Services\ExportService;
use App\Services\ProfitService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfitController extends Controller
{
    public function __construct(
        private ProfitService $service,
        private ExportService $exporter,
    ) {}

    public function index(\Illuminate\Http\Request $request): View
    {
        $selectedYear = $request->input('year');
        if ($selectedYear && is_numeric($selectedYear)) {
            $startDate = $selectedYear . '-01-01';
            $endDate   = $selectedYear . '-12-31';
        } else {
            $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
            $endDate   = $request->input('end_date', now()->endOfMonth()->toDateString());
        }

        $chartStart = ($selectedYear || $request->filled('start_date')) ? $startDate : now()->startOfYear()->toDateString();
        $chartEnd   = ($selectedYear || $request->filled('end_date')) ? $endDate : now()->endOfWeek()->toDateString();

        $allWeeks = $this->service->getWeeklyBreakdown($chartStart, $chartEnd);
        $allWeeksCollection = collect($allWeeks)->sortByDesc('week_key')->values();
        
        $page = (int) $request->input('page', 1);
        $perPage = 13;
        
        $weeklyData = new \Illuminate\Pagination\LengthAwarePaginator(
            $allWeeksCollection->forPage($page, $perPage)->values(),
            $allWeeksCollection->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $availableYears = $this->service->getAvailableYears();
        $monthlyData  = $this->service->getMonthlyTrend(6);
        $summary      = $this->service->getSummary();
        $breakdown    = $this->service->getProfitBreakdown($startDate, $endDate);
        
        return view('profit.index', compact('weeklyData', 'allWeeks', 'monthlyData', 'summary', 'breakdown', 'startDate', 'endDate', 'availableYears', 'selectedYear'));
    }

    public function weeklyDetail(\Illuminate\Http\Request $request): View
    {
        $startDate = $request->input('start_date', now()->startOfWeek()->toDateString());
        $endDate   = $request->input('end_date', now()->endOfWeek()->toDateString());

        $summary   = $this->service->getProfitBreakdown($startDate, $endDate);

        $dayLoadBatches = \App\Models\DayLoadBatch::with(['entries.dealer', 'entries.vendor'])
            ->whereBetween('billing_date', [$startDate, $endDate])
            ->orderBy('billing_date', 'desc')
            ->get();

        $dealerPayments = \App\Models\DealerPayment::with('dealer')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        $vendorPayments = \App\Models\VendorPayment::with('vendor')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        $expenses = \App\Models\Expense::whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        $emis = \App\Models\Emi::where('status', 'Paid')
            ->whereBetween('due_date', [$startDate, $endDate])
            ->orderBy('due_date', 'desc')
            ->get();

        return view('profit.weekly-detail', compact(
            'startDate', 'endDate', 'summary',
            'dayLoadBatches', 'dealerPayments', 'vendorPayments', 'expenses', 'emis'
        ));
    }

    public function monthly(): View
    {
        $monthlyTrend = $this->service->getMonthlyTrend(12);
        return view('profit.monthly', compact('monthlyTrend'));
    }

    public function expenseVsIncome(): View
    {
        $summary = $this->service->getSummary();
        $weeklyData = $this->service->getWeeklyBreakdown();
        return view('profit.expense-vs-income', compact('summary', 'weeklyData'));
    }

    public function batch(): View
    {
        return view('profit.batch');
    }

    public function orderWise(): View
    {
        return view('profit.order-wise');
    }

    public function comparison(): View
    {
        $summary = $this->service->getSummary();
        return view('profit.comparison', compact('summary'));
    }

    public function export(): StreamedResponse
    {
        $rows = collect($this->service->getWeeklyBreakdown())->map(fn($w) => [
            $w['week'], $w['revenue'], $w['purchase'], $w['expenses'], $w['profit'],
        ]);
        return $this->exporter->streamCsv(
            'profit-report',
            ['Week', 'Revenue', 'Purchases', 'Expenses', 'Profit'],
            $rows
        );
    }

    public function exportPdf(\Illuminate\Http\Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $summary = $this->service->getSummary();
        $breakdown = $this->service->getProfitBreakdown($startDate, $endDate);
        $weeklyData = $this->service->getWeeklyBreakdown();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('profit.pdf', compact(
            'summary', 'breakdown', 'weeklyData', 'startDate', 'endDate'
        ));

        return $pdf->download("profit-loss-statement-{$startDate}-to-{$endDate}.pdf");
    }
}
