<?php

namespace App\Http\Controllers\Api;

use App\Models\BirdBatch;
use App\Services\StockService;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BirdBatchController extends BaseApiController
{
    /**
     * Display a listing of bird batches.
     */
    public function index(): JsonResponse
    {
        $batches = BirdBatch::orderBy('date_received', 'desc')->get();
        return $this->sendResponse($batches, 'Bird batches retrieved successfully');
    }

    /**
     * Store a newly created bird batch in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'batch_name'    => 'required|string|max:255',
            'date_received' => 'required|date',
            'initial_count' => 'required|integer|min:1',
            'avg_weight'    => 'required|numeric|min:0',
        ]);

        $data = $validated;
        $data['current_count'] = $validated['initial_count'];

        $batch = BirdBatch::create($data);

        ActivityLogger::log("Created Bird Batch: {$batch->batch_name}, Count: {$batch->initial_count}", 'Stock', $batch->id);

        return $this->sendResponse($batch, 'Bird batch created successfully', 201);
    }

    /**
     * Record bird mortality under a batch.
     */
    public function recordMortality(Request $request, BirdBatch $batch): JsonResponse
    {
        $validated = $request->validate([
            'count'  => 'required|integer|min:1|max:' . $batch->current_count,
            'reason' => 'required|string|max:255',
            'date'   => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            app(StockService::class)->recordMortality($batch, $validated['count'], $validated['reason'], $validated['date']);

            // Re-fetch batch to get updated current_count
            $batch->refresh();

            // Log action
            ActivityLogger::log("Recorded bird mortality of {$validated['count']} birds for Batch: {$batch->batch_name}. Reason: {$validated['reason']}", 'Stock', $batch->id);

            DB::commit();

            return $this->sendResponse($batch, 'Mortality recorded and batch updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Error recording bird mortality: ' . $e->getMessage(), [], 500);
        }
    }
}
