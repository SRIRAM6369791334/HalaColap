<?php

namespace App\Http\Controllers\Api;

use App\Models\Warehouse;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends BaseApiController
{
    /**
     * Display a listing of warehouses (Paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Warehouse::query();

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('location', 'like', "%{$request->search}%");
        }

        $warehouses = $query->latest()->paginate(10);

        return $this->sendPaginatedResponse($warehouses, 'Warehouses retrieved successfully');
    }

    /**
     * Store a newly created warehouse in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'location'  => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $warehouse = Warehouse::create($validated);

        ActivityLogger::log("Created warehouse location: {$warehouse->name}", 'Inventory', $warehouse->id);

        return $this->sendResponse($warehouse, 'Warehouse location added successfully', 201);
    }

    /**
     * Display the specified warehouse.
     */
    public function show(Warehouse $warehouse): JsonResponse
    {
        return $this->sendResponse($warehouse, 'Warehouse details retrieved successfully');
    }

    /**
     * Update the specified warehouse in storage.
     */
    public function update(Request $request, Warehouse $warehouse): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'location'  => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $warehouse->update($validated);

        ActivityLogger::log("Updated warehouse location: {$warehouse->name}", 'Inventory', $warehouse->id);

        return $this->sendResponse($warehouse, 'Warehouse updated successfully');
    }

    /**
     * Remove the specified warehouse from storage.
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $id = $warehouse->id;
        $name = $warehouse->name;

        // Future check: Ensure no stock remains in this location before deleting
        $warehouse->delete();

        ActivityLogger::log("Deleted warehouse location: {$name}", 'Inventory', $id);

        return $this->sendResponse([], 'Warehouse record removed successfully');
    }
}
