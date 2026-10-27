<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ItemController extends Controller
{
    /**
     * Display a listing of items.
     */
    public function index(Request $request): View
    {
        $query = Item::query();

        // Filters
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('brand', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('type') && $request->type != '') {
            $query->where('type', $request->type);
        }

        $items = $query->latest()->paginate(15);
        
        return view('inventory.items.index', compact('items'));
    }

    /**
     * Show the form for creating a new item.
     */
    public function create(): View
    {
        return view('inventory.items.create');
    }

    /**
     * Store a newly created item in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('items', 'code')->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
            'type' => 'required|in:Feed,Chick,Medicine,Vaccine,Equipment,Other',
            'category' => 'nullable|string',
            'brand' => 'nullable|string',
            'base_unit' => 'required|string',
            'conversion_rate' => 'required|numeric|min:0.01',
            'is_active' => 'boolean'
        ]);

        Item::create($validated);

        return redirect()->route('inventory.items.index')->with('success', 'Item created successfully.');
    }

    /**
     * Show the form for editing the specified item.
     */
    public function edit(Item $item): View
    {
        return view('inventory.items.edit', compact('item'));
    }

    /**
     * Update the specified item in storage.
     */
    public function update(Request $request, Item $item): RedirectResponse
    {
        $tenantId = $item->tenant_id ?? app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('items', 'code')->ignore($item->id)->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
            'type' => 'required|in:Feed,Chick,Medicine,Vaccine,Equipment,Other',
            'category' => 'nullable|string',
            'brand' => 'nullable|string',
            'base_unit' => 'required|string',
            'conversion_rate' => 'required|numeric|min:0.01',
            'is_active' => 'boolean'
        ]);

        $item->update($validated);

        return redirect()->route('inventory.items.index')->with('success', 'Item updated successfully.');
    }

    /**
     * Remove the specified item from storage.
     */
    public function destroy(Item $item): RedirectResponse
    {
        // Check if item has stock ledger entries before deleting
        if ($item->stockLedgers()->exists()) {
            return back()->with('error', 'Cannot delete item with existing stock records. Deactivate it instead.');
        }

        $item->delete();
        return redirect()->route('inventory.items.index')->with('success', 'Item deleted successfully.');
    }
}
