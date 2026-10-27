@extends('layouts.app')
@section('title', 'Record Usage')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <x-page-header title="Record Daily Consumption" subtitle="Deduct feed/medicine from stock and link to a batch">
        <x-button variant="ghost" href="{{ route('inventory.consumptions.index') }}" icon="arrow_back" size="sm">
            Back to Consumptions
        </x-button>
    </x-page-header>

    <x-card>
        <form action="{{ route('inventory.consumptions.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                {{-- Date --}}
                <div>
                    <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-2">1. Date of Usage</label>
                    <x-form.input type="date" name="date" required value="{{ old('date', date('Y-m-d')) }}" class="font-bold text-zinc-950 dark:text-white" />
                </div>

                {{-- Batch --}}
                <div>
                    <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-2">2. Target Batch (Flock)</label>
                    <x-form.select name="batch_id" required>
                        <option value="">Select Batch...</option>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" {{ old('batch_id') == $batch->id ? 'selected' : '' }}>
                                {{ $batch->batch_code }} ({{ $batch->breed }})
                            </option>
                        @endforeach
                    </x-form.select>
                </div>

                {{-- Item --}}
                <div>
                    <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-2">3. Item (Feed/Medicine)</label>
                    <x-form.select name="item_id" required onchange="updateStockDisplay(this)">
                        <option value="">Select Item...</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" {{ old('item_id') == $item->id ? 'selected' : '' }}>
                                {{ $item->name }} ({{ $item->code }})
                            </option>
                        @endforeach
                    </x-form.select>
                    <div id="stock-hint" class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-tight mt-2 hidden">
                        Available Stock: <span id="available-qty">0.00</span>
                    </div>
                </div>

                {{-- Warehouse --}}
                <div>
                    <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-2">4. Source Warehouse</label>
                    <x-form.select name="warehouse_id" required>
                        <option value="">Select Godown/Shed...</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ old('warehouse_id', $loop->first ? $wh->id : '') == $wh->id ? 'selected' : '' }}>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </x-form.select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 pt-8 border-t border-zinc-100 dark:border-zinc-800">
                {{-- Quantity --}}
                <div>
                    <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-2">5. Quantity Consumed</label>
                    <div class="relative">
                        <x-form.input type="number" name="quantity" step="0.01" required placeholder="0.00" class="font-black text-2xl text-emerald-700 dark:text-emerald-400" />
                        <span class="absolute right-5 top-1/2 -translate-y-1/2 text-[10px] font-black text-zinc-400 uppercase tracking-widest bg-zinc-50 dark:bg-zinc-800 px-2 py-1 rounded-lg border border-zinc-200 dark:border-zinc-700">Unit</span>
                    </div>
                </div>

                {{-- Remarks --}}
                <div>
                    <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-2">6. Remarks / Notes</label>
                    <textarea name="remarks" rows="2" placeholder="e.g. Extra feed due to cold weather..."
                              class="w-full px-4 py-3 border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-shadow text-sm text-zinc-800 dark:text-zinc-200 font-medium placeholder:text-zinc-400"></textarea>
                </div>
            </div>

            <x-button type="submit" class="w-full justify-center py-4 text-base" icon="task_alt">
                Confirm & Deduct Stock 
            </x-button>
        </form>
    </x-card>

    {{-- Info Card --}}
    <div class="p-6 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl text-white shadow-lg shadow-blue-600/20 flex gap-6 items-center">
        <div class="w-16 h-16 shrink-0 bg-white/20 rounded-2xl flex items-center justify-center text-3xl">
            <span class="material-symbols-rounded text-white">inventory</span>
        </div>
        <div>
            <h4 class="font-bold text-lg mb-1">Stock Availability Check</h4>
            <p class="text-blue-100 text-sm leading-relaxed">The system will automatically prevent saving if the quantity exceeds current stock. Recording usage daily ensures accurate <strong class="text-white">FCR tracking</strong> and batch performance analysis.</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function updateStockDisplay(select) {
    // Note: To make this truly reactive, we could add an AJAX call here 
    // to fetch the real-time stock of the selected item.
    // For now, it serves as a visual placeholder.
    const hint = document.getElementById('stock-hint');
    if (select.value) {
        hint.classList.remove('hidden');
    } else {
        hint.classList.add('hidden');
    }
}
</script>
@endpush
