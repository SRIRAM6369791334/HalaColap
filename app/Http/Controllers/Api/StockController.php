<?php

namespace App\Http\Controllers\Api;

use App\Models\Item;
use App\Models\StockLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\StockService;
use App\Services\ActivityLogger;

class StockController extends BaseApiController
{
    public function __construct(protected StockService $stockService) {}

    /**
     * Display a listing of items with their aggregated stocks (Paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $itemsQuery = Item::query();

        if ($request->filled('search')) {
            $itemsQuery->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('type')) {
            $itemsQuery->where('type', $request->type);
        }

        $perPage = $request->input('per_page', 15);
        $itemsPaginated = $itemsQuery->paginate($perPage);

        $itemIds = $itemsPaginated->pluck('id')->toArray();

        // Pre-aggregate IN and OUT stock totals for only the items on the current page to eliminate N+1 loop
        $pageTotalsIn = DB::table('stock_ledgers')
            ->whereIn('item_id', $itemIds)
            ->where('type', 'IN')
            ->groupBy('item_id')
            ->select('item_id', DB::raw('SUM(quantity) as total'))
            ->pluck('total', 'item_id');

        $pageTotalsOut = DB::table('stock_ledgers')
            ->whereIn('item_id', $itemIds)
            ->where('type', 'OUT')
            ->groupBy('item_id')
            ->select('item_id', DB::raw('SUM(quantity) as total'))
            ->pluck('total', 'item_id');

        $itemsPaginated->getCollection()->transform(function ($item) use ($pageTotalsIn, $pageTotalsOut) {
            $in = $pageTotalsIn[$item->id] ?? 0;
            $out = $pageTotalsOut[$item->id] ?? 0;
            $item->current_stock = (float)($in - $out);

            if ($item->current_stock <= 0) {
                $item->stock_status = 'Out of Stock';
            } elseif ($item->current_stock <= $item->low_stock_threshold) {
                $item->stock_status = 'Low Stock';
            } else {
                $item->stock_status = 'Healthy';
            }

            return $item;
        });

        // Compute summary metrics
        $allItems = Item::all();
        $totalsIn = DB::table('stock_ledgers')->where('type', 'IN')->groupBy('item_id')->select('item_id', DB::raw('SUM(quantity) as total'))->pluck('total', 'item_id');
        $totalsOut = DB::table('stock_ledgers')->where('type', 'OUT')->groupBy('item_id')->select('item_id', DB::raw('SUM(quantity) as total'))->pluck('total', 'item_id');

        $lowStockCount = 0;
        $outOfStockCount = 0;

        foreach ($allItems as $item) {
            $inQty = $totalsIn[$item->id] ?? 0;
            $outQty = $totalsOut[$item->id] ?? 0;
            $stock = $inQty - $outQty;

            if ($stock <= 0) {
                $outOfStockCount++;
            } elseif ($stock <= $item->low_stock_threshold) {
                $lowStockCount++;
            }
        }

        $stats = [
            'total_items'        => $allItems->count(),
            'low_stock_count'    => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Inventory stocks retrieved successfully',
            'data'    => [
                'items' => $itemsPaginated->items(),
                'stats' => $stats
            ],
            'pagination' => [
                'current_page' => $itemsPaginated->currentPage(),
                'per_page'     => $itemsPaginated->perPage(),
                'total'        => $itemsPaginated->total(),
            ]
        ]);
    }

    /**
     * Display a listing of stock movements/ledgers (Paginated).
     */
    public function movements(Request $request): JsonResponse
    {
        $query = StockLedger::with(['item', 'batch', 'warehouse']);

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $movements = $query->latest('transaction_date')->paginate(15);

        return $this->sendPaginatedResponse($movements, 'Stock ledger movements retrieved successfully');
    }

    /**
     * Record a manual stock adjustment.
     */
    public function adjust(Request $request): JsonResponse
    {
        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id;

        $validated = $request->validate([
            'item_id'      => [
                'required',
                \Illuminate\Validation\Rule::exists('items', 'id')->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
            'warehouse_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('warehouses', 'id')->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
            'batch_id'     => [
                'nullable',
                \Illuminate\Validation\Rule::exists('batches', 'id')->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
            'quantity'     => 'required|numeric',
            'remarks'      => 'required|string|max:255',
            'date'         => 'required|date'
        ]);

        try {
            DB::beginTransaction();

            $item = Item::findOrFail($validated['item_id']);

            $adjustmentData = [
                'item_id'      => $validated['item_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'batch_id'     => $validated['batch_id'] ?? null,
                'quantity'     => abs($validated['quantity']),
                'unit'         => $item->base_unit,
                'source_type'  => 'Adjustment',
                'remarks'      => $validated['remarks'],
                'date'         => $validated['date'],
                'notes'        => $validated['remarks'],
            ];

            if ($validated['quantity'] >= 0) {
                $txn = $this->stockService->recordIn($adjustmentData);
            } else {
                $currentStock = $this->stockService->getCurrentStock($validated['item_id']);
                if ($currentStock < abs($validated['quantity'])) {
                    DB::rollBack();
                    return $this->sendError("Insufficient stock! Available stock is {$currentStock} {$item->base_unit}.", [], 422);
                }
                $txn = $this->stockService->recordOut($adjustmentData);
            }

            ActivityLogger::log("Manual stock adjustment for item '{$item->name}' by {$validated['quantity']} {$item->base_unit}. Reason: {$validated['remarks']}", 'Stock Adjustments', $txn->id);

            DB::commit();

            return $this->sendResponse($txn, 'Manual stock adjustment logged successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Error processing stock adjustment: ' . $e->getMessage(), [], 500);
        }
    }
}
