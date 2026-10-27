<?php

namespace App\Http\Controllers\Api;

use App\Models\Batch;
use App\Models\Consumption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class InventoryAnalyticsController extends BaseApiController
{
    /**
     * Compute survival rates, age, feed consumption, and charts (Cached for 5 minutes).
     */
    public function index(Request $request): JsonResponse
    {
        $activeBatches = Batch::where('status', 'Active')->get();
        $selectedBatchId = $request->get('batch_id', $activeBatches->first()?->id);

        if (!$selectedBatchId) {
            return $this->sendResponse([
                'active_batches' => $activeBatches,
                'stats'          => null,
                'chart_data'     => [],
                'selected_batch_id' => null
            ], 'No active batches available for analytics');
        }

        $cacheKey = "api_inventory_analytics_batch_" . $selectedBatchId;
        
        $analytics = Cache::remember($cacheKey, 300, function () use ($selectedBatchId) {
            $batch = Batch::with(['consumptions.item', 'mortalities'])->findOrFail($selectedBatchId);
            
            // 1. Calculate Mortality Stats
            $totalMortality = $batch->mortalities->sum('count');
            $survivalRate = $batch->initial_count > 0 
                ? (($batch->initial_count - $totalMortality) / $batch->initial_count) * 100 
                : 0;

            // 2. Calculate Feed Consumption
            $feedConsumptions = $batch->consumptions->filter(function($c) {
                return str_contains(strtolower($c->item->category ?? ''), 'feed');
            });
            
            $totalFeed = $feedConsumptions->sum('quantity');
            $feedPerBird = $batch->current_count > 0 ? $totalFeed / $batch->current_count : 0;

            // 3. Age in Days
            $placementDate = \Carbon\Carbon::parse($batch->placement_date);
            $ageDays = $placementDate->diffInDays(now());

            $stats = [
                'batch_code'      => $batch->batch_code,
                'initial_count'   => $batch->initial_count,
                'current_birds'   => $batch->current_count,
                'total_mortality' => $totalMortality,
                'survival_rate'   => round($survivalRate, 2),
                'total_feed'      => $totalFeed,
                'feed_per_bird'   => round($feedPerBird, 3),
                'age_days'        => $ageDays,
            ];

            // 4. Daily Consumption Chart Data (Last 14 days)
            $chartData = Consumption::where('batch_id', $selectedBatchId)
                ->whereHas('item', function($q) { $q->where('category', 'like', '%feed%'); })
                ->select(DB::raw('DATE(date) as day'), DB::raw('SUM(quantity) as total'))
                ->groupBy('day')
                ->orderBy('day', 'asc')
                ->take(14)
                ->get();

            return [
                'stats'      => $stats,
                'chart_data' => $chartData
            ];
        });

        return $this->sendResponse([
            'active_batches'    => $activeBatches,
            'selected_batch_id' => (int)$selectedBatchId,
            'stats'             => $analytics['stats'],
            'chart_data'        => $analytics['chart_data'],
        ], 'Inventory analytics retrieved successfully');
    }
}
