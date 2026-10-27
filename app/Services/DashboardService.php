<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\WeeklyBill;
use App\Models\DailyBill;
use App\Models\DayLoadInvoice;
use App\Models\Purchase;
use App\Models\Dealer;
use App\Models\Emi;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function __construct(
        private ProfitService $profitService
    ) {}

    public function getStats(): array
    {
        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id ?? 'global';
        return Cache::remember("tenant_{$tenantId}.dashboard.stats", 60, function () {
            $todayRevenue   = DailyBill::whereNotNull('dealer_id')->whereDate('date', today())->sum('amount');
            $totalCustomers = Customer::count();
            $pendingPayments = Customer::where('balance', '>', 0)->sum('balance');
            $pendingCount    = Customer::where('balance', '>', 0)->count();
            
            $purchasesToday  = Purchase::whereDate('date', today())->sum('total_amount');
            $purchaseCount   = Purchase::whereDate('date', today())->count();
            
            $startOfMonth = now()->startOfMonth()->toDateString();
            $endOfMonth   = now()->endOfMonth()->toDateString();

            $profitBreakdown = $this->profitService->getProfitBreakdown($startOfMonth, $endOfMonth);

            $monthlyRevenue  = $profitBreakdown['total_billed'];
            $monthlyPurchase = $profitBreakdown['vendor_cost'];
            $activeDealers   = Dealer::where('pending_amount', '>', 0)->count();

            // MSR BROILERS operational stats
            $totalBirds     = \App\Models\Batch::where('status', 'Active')->sum('current_count');
            $activeBatches  = \App\Models\Batch::where('status', 'Active')->count();
            $mortalityMTD   = \App\Models\Mortality::whereBetween('date', [$startOfMonth, $endOfMonth])->sum('count');

            return compact(
                'todayRevenue', 'totalCustomers', 'pendingPayments', 'pendingCount',
                'purchasesToday', 'purchaseCount', 'monthlyRevenue', 'monthlyPurchase', 
                'activeDealers', 'totalBirds', 'activeBatches', 'mortalityMTD', 'profitBreakdown'
            );
        });
    }


    public function getRecentSales(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        // Combined recent bills
        return DailyBill::whereNotNull('dealer_id')
            ->with('customer')
            ->latest('date')
            ->limit($limit)
            ->get();
    }

    public function getUpcomingEmis(int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return Emi::where('status', 'Upcoming')
            ->whereDate('due_date', '<=', now()->addDays($days))
            ->orderBy('due_date')
            ->get();
    }
}
