<?php

namespace App\Http\Controllers;

use App\Models\BirdBatch;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class BirdBatchController extends Controller
{
    public function index(): View
    {
        $batches = BirdBatch::orderBy('date_received', 'desc')->get();
        return view('stock.batches.index', compact('batches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'batch_name' => 'required|string|max:255',
            'date_received' => 'required|date',
            'initial_count' => 'required|integer|min:1',
            'avg_weight' => 'required|numeric|min:0',
        ]);

        $data = $request->all();
        $data['current_count'] = $request->initial_count;

        BirdBatch::create($data);

        return redirect()->route('stock.batches.index')->with('success', 'Batch created successfully.');
    }

    public function recordMortality(Request $request, BirdBatch $batch): RedirectResponse
    {
        $request->validate([
            'count' => 'required|integer|min:1|max:' . $batch->current_count,
            'reason' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        app(StockService::class)->recordMortality($batch, $request->count, $request->reason, $request->date);

        return back()->with('success', 'Mortality recorded successfully.');
    }
}
