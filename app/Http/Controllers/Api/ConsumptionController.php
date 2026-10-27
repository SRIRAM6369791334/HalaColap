<?php

namespace App\Http\Controllers\Api;

use App\Models\Consumption;
use App\Models\Item;
use App\Services\StockService;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsumptionController extends BaseApiController
{
    public function __construct(protected StockService $stockService) {}

    /**
     * Display a listing of consumptions (Paginated).
     */
    public function index(): JsonResponse
    {
        $consumptions = Consumption::with(['batch', 'item', 'warehouse'])
            ->orderBy('date', 'desc')
            ->paginate(20);
            
        return $this->sendPaginatedResponse($consumptions, 'Consumptions list retrieved successfully');
    }

    /**
     * Store a newly created consumption in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id;

        $validated = $request->validate([
            'date'         => 'required|date',
            'batch_id'     => [
                'required',
                \Illuminate\Validation\Rule::exists('batches', 'id')->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
            'item_id'      => [
                'required',
                \Illuminate\Validation\Rule::exists('items', 'id')->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
            'warehouse_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('warehouses', 'id')->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
            'quantity'     => 'required|numeric|min:0.01',
            'remarks'      => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $item = Item::findOrFail($validated['item_id']);
            
            // 1. Check current stock before allowing consumption
            $currentStock = $this->stockService->getCurrentStock($validated['item_id']);
            if ($currentStock < $validated['quantity']) {
                DB::rollBack();
                return $this->sendError("Insufficient stock! Current stock for {$item->name} is {$currentStock} {$item->base_unit}.", [
                    'quantity' => ["Required quantity exceeds current active stock."]
                ], 422);
            }

            // 2. Record Consumption
            $consumption = Consumption::create([
                'date'         => $validated['date'],
                'batch_id'     => $validated['batch_id'],
                'item_id'      => $validated['item_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'quantity'     => $validated['quantity'],
                'unit'         => $item->base_unit,
                'remarks'      => $validated['remarks'],
                'created_by'   => auth()->id()
            ]);

            // 3. Record Stock OUT movement
            $this->stockService->recordOut([
                'item_id'          => $validated['item_id'],
                'batch_id'         => $validated['batch_id'],
                'warehouse_id'     => $validated['warehouse_id'],
                'quantity'         => $validated['quantity'],
                'unit'             => $item->base_unit,
                'source_type'      => 'Consumption',
                'source_id'        => $consumption->id,
                'transaction_date' => $validated['date'],
                'remarks'          => "Consumption for Batch: " . $consumption->batch->batch_code
            ]);

            // Audit Logging
            ActivityLogger::log("Logged consumption of {$consumption->quantity} {$consumption->unit} for item: {$item->name} on Batch: {$consumption->batch->batch_code}", 'Inventory', $consumption->id);

            DB::commit();
            return $this->sendResponse($consumption, 'Daily consumption recorded successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Error recording consumption: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Remove the specified consumption from storage.
     */
    public function destroy(Consumption $consumption): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $id = $consumption->id;
            $qty = $consumption->quantity;
            $unit = $consumption->unit;
            $itemName = $consumption->item->name;
            $batchCode = $consumption->batch->batch_code;

            // Revert stock transaction and ledger entry via StockService
            $this->stockService->revertMovement(Consumption::class, $consumption->id);
                
            $consumption->delete();
            
            // Audit Logging
            ActivityLogger::log("Reverted consumption of {$qty} {$unit} for item: {$itemName} on Batch: {$batchCode}", 'Inventory', $id);

            DB::commit();
            return $this->sendResponse([], 'Consumption record deleted and stock reverted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Error deleting record: ' . $e->getMessage(), [], 500);
        }
    }
}
