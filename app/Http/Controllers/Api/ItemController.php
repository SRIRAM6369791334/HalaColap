<?php

namespace App\Http\Controllers\Api;

use App\Models\Item;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends BaseApiController
{
    /**
     * Display a listing of items (Paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int)$request->input('per_page', 15), 100);
        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id ?? 'global';

        if ($request->boolean('all') || $request->input('all') === '1') {
            $items = \Illuminate\Support\Facades\Cache::remember("tenant_{$tenantId}.masters.items.all", now()->addMinutes(30), function () {
                return Item::orderBy('name')->get();
            });

            return $this->sendResponse($items, 'Items retrieved successfully');
        }

        $search = $request->input('search');
        $type = $request->input('type');

        if (empty($search) && empty($type) && $perPage === 15) {
            $items = \Illuminate\Support\Facades\Cache::remember("tenant_{$tenantId}.masters.items.paginated.default", now()->addMinutes(30), function () {
                return Item::latest()->paginate(15);
            });
        } else {
            $query = Item::query();
            
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('brand', 'like', '%' . $search . '%');
                });
            }

            if (!empty($type)) {
                $query->where('type', $type);
            }

            $items = $query->latest()->paginate($perPage);
        }
        
        return $this->sendPaginatedResponse($items, 'Items retrieved successfully');
    }

    /**
     * Store a newly created item in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id;

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'code'            => [
                'nullable',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('items', 'code')->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
            'type'            => 'required|in:Feed,Chick,Medicine,Vaccine,Equipment,Other',
            'category'        => 'nullable|string',
            'brand'           => 'nullable|string',
            'base_unit'       => 'required|string',
            'conversion_rate' => 'required|numeric|min:0.01',
            'is_active'       => 'boolean'
        ]);

        $item = Item::create($validated);

        ActivityLogger::log("Created inventory item: {$item->name}", 'Inventory', $item->id);

        return $this->sendResponse($item, 'Item created successfully', 201);
    }

    /**
     * Display the specified item.
     */
    public function show(Item $item): JsonResponse
    {
        return $this->sendResponse($item, 'Item details retrieved successfully');
    }

    /**
     * Update the specified item in storage.
     */
    public function update(Request $request, Item $item): JsonResponse
    {
        $tenantId = $item->tenant_id ?? app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id;

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'code'            => [
                'nullable',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('items', 'code')->ignore($item->id)->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
            'type'            => 'required|in:Feed,Chick,Medicine,Vaccine,Equipment,Other',
            'category'        => 'nullable|string',
            'brand'           => 'nullable|string',
            'base_unit'       => 'required|string',
            'conversion_rate' => 'required|numeric|min:0.01',
            'is_active'       => 'boolean'
        ]);

        $item->update($validated);

        ActivityLogger::log("Updated inventory item: {$item->name}", 'Inventory', $item->id);

        return $this->sendResponse($item, 'Item updated successfully');
    }

    /**
     * Remove the specified item from storage.
     */
    public function destroy(Item $item): JsonResponse
    {
        // Check if item has stock ledger entries before deleting
        if ($item->stockLedgers()->exists()) {
            return $this->sendError('Cannot delete item with existing stock records. Deactivate it instead.', [], 422);
        }

        $id = $item->id;
        $name = $item->name;

        $item->delete();

        ActivityLogger::log("Deleted inventory item: {$name}", 'Inventory', $id);

        return $this->sendResponse([], 'Item deleted successfully');
    }
}
