<?php

namespace App\Http\Controllers\Api;

use App\Models\Batch;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BatchController extends BaseApiController
{
    /**
     * Display a listing of batches (Paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Batch::query();

        if ($request->search) {
            $query->where('batch_code', 'like', "%{$request->search}%")
                  ->orWhere('breed', 'like', "%{$request->search}%");
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $batches = $query->latest('placement_date')->paginate(10);

        return $this->sendPaginatedResponse($batches, 'Batches retrieved successfully');
    }

    /**
     * Store a newly created batch in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Auto-generate batch code if not provided
        if (!$request->has('batch_code') || empty($request->batch_code)) {
            $latestBatch = Batch::latest()->first();
            $nextId = $latestBatch ? $latestBatch->id + 1 : 1;
            $request->merge([
                'batch_code' => 'BATCH-' . date('Y') . '-' . str_pad($nextId, 3, '0', STR_PAD_LEFT)
            ]);
        }

        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id;

        $validated = $request->validate([
            'batch_code'           => [
                'required',
                \Illuminate\Validation\Rule::unique('batches', 'batch_code')->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
            'placement_date'       => 'required|date',
            'initial_count'        => 'required|integer|min:1',
            'breed'                => 'nullable|string',
            'avg_placement_weight' => 'nullable|numeric|min:0',
        ]);

        $validated['current_count'] = $request->initial_count;
        $validated['status'] = 'Active';

        $batch = Batch::create($validated);

        ActivityLogger::log("Registered new flock batch: {$batch->batch_code}, Initial Count: {$batch->initial_count}", 'Inventory', $batch->id);

        return $this->sendResponse($batch, 'New batch registered successfully', 201);
    }

    /**
     * Display the specified batch.
     */
    public function show(Batch $batch): JsonResponse
    {
        return $this->sendResponse($batch, 'Batch details retrieved successfully');
    }

    /**
     * Update the specified batch in storage.
     */
    public function update(Request $request, Batch $batch): JsonResponse
    {
        $tenantId = $batch->tenant_id ?? app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id;

        $validated = $request->validate([
            'batch_code'           => [
                'required',
                \Illuminate\Validation\Rule::unique('batches', 'batch_code')->ignore($batch->id)->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
            'placement_date'       => 'required|date',
            'initial_count'        => 'required|integer|min:1',
            'current_count'        => 'required|integer|min:0',
            'breed'                => 'nullable|string',
            'avg_placement_weight' => 'nullable|numeric|min:0',
            'status'               => 'required|in:Active,Closed',
            'closed_at'            => 'nullable|required_if:status,Closed|date',
        ]);

        $batch->update($validated);

        ActivityLogger::log("Updated flock batch: {$batch->batch_code}", 'Inventory', $batch->id);

        return $this->sendResponse($batch, 'Batch updated successfully');
    }

    /**
     * Remove the specified batch from storage.
     */
    public function destroy(Batch $batch): JsonResponse
    {
        $id = $batch->id;
        $code = $batch->batch_code;

        $batch->delete();

        ActivityLogger::log("Deleted flock batch: {$code}", 'Inventory', $id);

        return $this->sendResponse([], 'Batch record deleted successfully');
    }
}
