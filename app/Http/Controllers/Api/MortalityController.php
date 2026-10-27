<?php

namespace App\Http\Controllers\Api;

use App\Models\Mortality;
use App\Models\Batch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLogger;

class MortalityController extends BaseApiController
{
    /**
     * Display a listing of mortalities (Paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Mortality::with('batch');

        if ($request->has('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        $mortalities = $query->orderBy('date', 'desc')->paginate(15);

        return $this->sendPaginatedResponse($mortalities, 'Mortality logs retrieved successfully');
    }

    /**
     * Store a newly created mortality in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id;

        $validated = $request->validate([
            'date'     => 'required|date',
            'batch_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('batches', 'id')->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
            'count'    => 'required|integer|min:1',
            'reason'   => 'nullable|string|max:100',
            'remarks'  => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $batch = Batch::findOrFail($validated['batch_id']);

            if ($batch->current_count < $validated['count']) {
                DB::rollBack();
                return $this->sendError("Mortality count exceeds current bird count ({$batch->current_count}) for this batch.", [], 422);
            }

            // 1. Record Mortality
            $mortality = Mortality::create([
                'date'       => $validated['date'],
                'batch_id'   => $validated['batch_id'],
                'count'      => $validated['count'],
                'reason'     => $validated['reason'],
                'remarks'    => $validated['remarks'],
                'created_by' => auth()->id()
            ]);

            // 2. Decrement Batch Current Count
            $batch->decrement('current_count', $validated['count']);

            // Audit Logging
            ActivityLogger::log("Recorded bird mortality of {$validated['count']} birds for Batch: {$batch->batch_code}", 'Stock', $mortality->id);

            DB::commit();

            $mortality->load('batch');
            return $this->sendResponse($mortality, 'Mortality recorded and batch updated successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Error recording bird mortality: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Remove the specified mortality from storage.
     */
    public function destroy(Mortality $mortality): JsonResponse
    {
        try {
            DB::beginTransaction();

            $batch = $mortality->batch;
            $count = $mortality->count;
            $id = $mortality->id;

            // 1. Restore Batch Current Count
            $batch->increment('current_count', $count);

            // 2. Delete Record
            $mortality->delete();

            // Audit Logging
            ActivityLogger::log("Reverted bird mortality of {$count} birds for Batch: {$batch->batch_code}", 'Stock', $id);

            DB::commit();

            return $this->sendResponse([], 'Mortality record deleted and batch count reverted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Error deleting mortality record: ' . $e->getMessage(), [], 500);
        }
    }
}
