<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request)
    {
        // Get all items with their aggregated stock
        $itemsQuery = Item::query();

        if ($request->search) {
            $itemsQuery->where('name', 'like', "%{$request->search}%")
                      ->orWhere('code', 'like', "%{$request->search}%");
        }

        if ($request->type) {
            $itemsQuery->where('type', $request->type);
        }

        $items = $itemsQuery->get()->map(function ($item) {
            // Calculate current stock from ledger
            $in = DB::table('stock_ledgers')->where('item_id', $item->id)->where('type', 'IN')->sum('quantity');
            $out = DB::table('stock_ledgers')->where('item_id', $item->id)->where('type', 'OUT')->sum('quantity');
            $item->current_stock = $in - $out;
            
            // Status check
            if ($item->current_stock <= 0) {
                $item->stock_status = 'Out of Stock';
            } elseif ($item->current_stock <= $item->low_stock_threshold) {
                $item->stock_status = 'Low Stock';
            } else {
                $item->stock_status = 'Healthy';
            }
            
            return $item;
        });

        // Summary Stats
        $stats = [
            'total_items' => $items->count(),
            'low_stock_count' => $items->where('stock_status', 'Low Stock')->count(),
            'out_of_stock_count' => $items->where('stock_status', 'Out of Stock')->count(),
        ];

        return view('inventory.stock.index', compact('items', 'stats'));
    }

    public function movements(Request $request)
    {
        $movements = StockLedger::with(['item', 'batch', 'warehouse'])
            ->latest('transaction_date')
            ->paginate(20);

        return view('inventory.stock.movements', compact('movements'));
    }
}
