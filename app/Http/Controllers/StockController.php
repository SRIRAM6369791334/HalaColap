<?php

namespace App\Http\Controllers;

use App\Models\StockItem;
use App\Models\StockTransaction;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $summaries = StockItem::orderBy('item_name')->get();
        
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());
        
        $movements = StockTransaction::whereBetween('date', [$from, $to])
                        ->orderBy('date', 'desc')
                        ->orderBy('id', 'desc')
                        ->paginate(20);

        return view('stock.index', compact('summaries', 'movements', 'from', 'to'));
    }

    public function adjust(Request $request): RedirectResponse
    {
        $request->validate([
            'item_name' => 'required|string|exists:stock_items,item_name',
            'quantity' => 'required|numeric',
            'reason' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        app(StockService::class)->adjustStock([
            'item_name' => $request->item_name,
            'quantity' => $request->quantity,
            'notes' => 'Adjustment: ' . $request->reason,
            'date' => $request->date,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Stock adjusted successfully.');
    }
}
