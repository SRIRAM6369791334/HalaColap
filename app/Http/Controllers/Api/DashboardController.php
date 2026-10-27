<?php

namespace App\Http\Controllers\Api;

use App\Services\DashboardService;
use App\Services\DashboardCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends BaseApiController
{
    public function __construct(
        private DashboardService $service,
        private DashboardCacheService $cache
    ) {}

    /**
     * Get live dashboard summary statistics (Cached for 5 minutes).
     */
    public function index(): JsonResponse
    {
        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id ?? 'global';
        // Leverage Redis/File Cache for 5 minutes (300 seconds)
        $stats = Cache::remember("tenant_{$tenantId}.api_dashboard_stats", 300, function () {
            return $this->service->getStats();
        });

        $recentSales = $this->service->getRecentSales(5);
        $upcomingEmis = $this->service->getUpcomingEmis(7);

        return $this->sendResponse([
            'stats'         => $stats,
            'recent_sales'  => $recentSales,
            'upcoming_emis' => $upcomingEmis,
        ], 'Dashboard statistics retrieved successfully');
    }

    /**
     * Get upcoming EMIs/loans alerts.
     */
    public function alerts(): JsonResponse
    {
        $upcomingEmis = $this->service->getUpcomingEmis(30);

        return $this->sendResponse([
            'upcoming_emis' => $upcomingEmis,
        ], 'Dashboard upcoming loan alerts retrieved successfully');
    }
}
