<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Consumption;
use App\Models\Batch;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ConsumptionController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(): View
    {
        $consumptions = Consumption::with(['batch', 'item', 'warehouse'])
            ->orderBy('date', 'desc')
            ->paginate(20);
            
        return view('inventory.consumptions.index', compact('consumptions'));
    }

    public function create(): View
    {
        $batches = Batch::where('status', 'Active')->get();
        $items = Item::active()->get();
        $warehouses = Warehouse::active()->get();
        
        return view('inventory.consumptions.create', compact('batches', 'items', 'warehouses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'batch_id' => 'required|exists:batches,id',
            'item_id' => 'required|exists:items,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $item = Item::findOrFail($validated['item_id']);
            
            // 1. Check current stock before allowing consumption
            $currentStock = $this->stockService->getCurrentStock($validated['item_id']);
            if ($currentStock < $validated['quantity']) {
                DB::rollBack();
                return back()->with('error', "Insufficient stock! Current stock for {$item->name} is {$currentStock} {$item->base_unit}.")->withInput();
            }

            // 2. Record Consumption
            $consumption = Consumption::create([
                'date' => $validated['date'],
                'batch_id' => $validated['batch_id'],
                'item_id' => $validated['item_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'quantity' => $validated['quantity'],
                'unit' => $item->base_unit,
                'remarks' => $validated['remarks'],
                'created_by' => auth()->id()
            ]);

            // 3. Record Stock OUT movement
            $this->stockService->recordOut([
                'item_id' => $validated['item_id'],
                'batch_id' => $validated['batch_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'quantity' => $validated['quantity'],
                'unit' => $item->base_unit,
                'source_type' => 'Consumption',
                'source_id' => $consumption->id,
                'transaction_date' => $validated['date'],
                'remarks' => "Consumption for Batch: " . $consumption->batch->batch_code
            ]);

            DB::commit();
            return redirect()->route('inventory.consumptions.index')->with('success', 'Daily consumption recorded successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error recording consumption: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Consumption $consumption): RedirectResponse
    {
        try {
            DB::beginTransaction();
            
            // Revert stock transaction and ledger entry via StockService
            $this->stockService->revertMovement(Consumption::class, $consumption->id);
                
            $consumption->delete();
            
            DB::commit();
            return redirect()->route('inventory.consumptions.index')->with('success', 'Consumption record deleted and stock reverted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error deleting record: ' . $e->getMessage());
        }
    }
}
