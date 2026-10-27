<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $service,
        private \App\Services\DashboardCacheService $cache
    ) {}

    public function index(): View
    {
        $stats = $this->cache->getStats(fn() => $this->service->getStats());
        
        $recentSales  = $this->service->getRecentSales(5);
        $upcomingEmis = $this->service->getUpcomingEmis(7);

        return view('dashboard.index', compact('stats', 'recentSales', 'upcomingEmis'));
    }

    public function alerts(): View
    {
        $upcomingEmis = $this->service->getUpcomingEmis(30); // Show more for dedicated alerts page
        return view('dashboard.alerts', compact('upcomingEmis'));
    }
}
