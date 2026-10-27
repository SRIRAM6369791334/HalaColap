<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Consumption;
use App\Models\Mortality;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $activeBatches = Batch::where('status', 'Active')->get();
        $selectedBatchId = $request->get('batch_id', $activeBatches->first()?->id);
        
        $stats = null;
        $chartData = [];

        if ($selectedBatchId) {
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
                'batch' => $batch,
                'total_mortality' => $totalMortality,
                'survival_rate' => round($survivalRate, 2),
                'total_feed' => $totalFeed,
                'feed_per_bird' => round($feedPerBird, 3),
                'age_days' => $ageDays,
                'current_birds' => $batch->current_count,
            ];

            // 4. Daily Consumption Chart Data (Last 14 days)
            $chartData = Consumption::where('batch_id', $selectedBatchId)
                ->whereHas('item', function($q) { $q->where('category', 'like', '%feed%'); })
                ->select(DB::raw('DATE(date) as day'), DB::raw('SUM(quantity) as total'))
                ->groupBy('day')
                ->orderBy('day', 'asc')
                ->take(14)
                ->get();
        }

        return view('inventory.analytics.index', compact('activeBatches', 'stats', 'chartData', 'selectedBatchId'));
    }
}
