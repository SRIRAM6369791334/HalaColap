<?php

namespace App\Http\Controllers\Api;

use App\Services\ProfitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfitController extends BaseApiController
{
    public function __construct(private ProfitService $service) {}

    /**
     * Get consolidated Profit & Loss summary.
     */
    public function index(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $weeklyData  = $this->service->getWeeklyBreakdown();
        $monthlyData = $this->service->getMonthlyTrend(6);
        $summary     = $this->service->getSummary();
        $breakdown   = $this->service->getProfitBreakdown($startDate, $endDate);

        return $this->sendResponse([
            'start_date'   => $startDate,
            'end_date'     => $endDate,
            'summary'      => $summary,
            'breakdown'    => $breakdown,
            'weekly_data'  => $weeklyData,
            'monthly_data' => $monthlyData,
        ], 'Profit and loss summary retrieved successfully');
    }

    /**
     * Get monthly profit trend.
     */
    public function monthly(Request $request): JsonResponse
    {
        $months = (int) $request->input('months', 12);
        $monthlyTrend = $this->service->getMonthlyTrend($months);

        return $this->sendResponse([
            'months'        => $months,
            'monthly_trend' => $monthlyTrend,
        ], 'Monthly profit trends retrieved successfully');
    }

    /**
     * Get income vs expenses.
     */
    public function expenseVsIncome(): JsonResponse
    {
        $summary = $this->service->getSummary();
        $weeklyData = $this->service->getWeeklyBreakdown();

        return $this->sendResponse([
            'summary'     => $summary,
            'weekly_data' => $weeklyData,
        ], 'Income vs Expense metrics retrieved successfully');
    }
}
