<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $query = Warehouse::query();

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('location', 'like', "%{$request->search}%");
        }

        $warehouses = $query->latest()->paginate(10);

        return view('inventory.warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        return view('inventory.warehouses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        Warehouse::create($validated);

        return redirect()->route('inventory.warehouses.index')
            ->with('success', 'Warehouse location added successfully.');
    }

    public function edit(Warehouse $warehouse)
    {
        return view('inventory.warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $warehouse->update($validated);

        return redirect()->route('inventory.warehouses.index')
            ->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Warehouse $warehouse)
    {
        // Future: Check if stock exists in this warehouse before deleting
        $warehouse->delete();

        return redirect()->route('inventory.warehouses.index')
            ->with('success', 'Warehouse record removed.');
    }
}
