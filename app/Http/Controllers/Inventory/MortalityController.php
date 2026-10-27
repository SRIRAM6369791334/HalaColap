<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Mortality;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class MortalityController extends Controller
{
    public function index(): View
    {
        $mortalities = Mortality::with('batch')
            ->orderBy('date', 'desc')
            ->paginate(20);
            
        return view('inventory.mortalities.index', compact('mortalities'));
    }

    public function create(): View
    {
        $batches = Batch::where('status', 'Active')->get();
        return view('inventory.mortalities.create', compact('batches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'batch_id' => 'required|exists:batches,id',
            'count' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:100',
            'remarks' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $batch = Batch::findOrFail($validated['batch_id']);
            
            // Validation: Cannot have more mortality than current count
            if ($batch->current_count < $validated['count']) {
                DB::rollBack();
                return back()->with('error', "Mortality count ({$validated['count']}) cannot exceed current bird count ({$batch->current_count}) for this batch.")->withInput();
            }

            // 1. Record Mortality
            Mortality::create([
                'date' => $validated['date'],
                'batch_id' => $validated['batch_id'],
                'count' => $validated['count'],
                'reason' => $validated['reason'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'created_by' => auth()->id()
            ]);

            // 2. Decrement Batch Current Count
            $batch->decrement('current_count', $validated['count']);

            DB::commit();
            return redirect()->route('inventory.mortalities.index')->with('success', 'Mortality recorded and batch count updated.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error recording mortality: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Mortality $mortality): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $batch = $mortality->batch;
            
            // 1. Restore Batch Current Count
            $batch->increment('current_count', $mortality->count);

            // 2. Delete Record
            $mortality->delete();

            DB::commit();
            return redirect()->route('inventory.mortalities.index')->with('success', 'Mortality record deleted and batch count reverted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error deleting record: ' . $e->getMessage());
        }
    }
}
