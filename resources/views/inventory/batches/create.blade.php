@extends('layouts.app')

@section('title', 'Start New Batch')

@section('content')
<div class="mb-6">
    <a href="{{ route('inventory.batches.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 uppercase tracking-wider mb-2 inline-block">← Back to Batches</a>
    <h1 class="text-2xl font-bold text-zinc-950">Start New Flock Batch</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Initialize a new production cycle and chick placement</p>
</div>

<div class="max-w-4xl">
    <div class="bg-gradient-to-br from-white via-emerald-50/40 to-sky-50/40 rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <form action="{{ route('inventory.batches.store') }}" method="POST" class="p-6 space-y-8">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                {{-- Batch Identification --}}
                <div class="space-y-5">
                    <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-widest border-b border-zinc-200 pb-2">1. Identification</h3>
                    
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-zinc-700 uppercase tracking-tight">Batch Code <span class="text-red-500">*</span></label>
                        <input type="text" name="batch_code" required value="{{ old('batch_code', $defaultCode) }}" 
                               class="w-full px-4 py-2.5 bg-emerald-50 border border-zinc-200 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all font-mono font-bold">
                        @error('batch_code') <p class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-zinc-700 uppercase tracking-tight">Placement Date <span class="text-red-500">*</span></label>
                        <input type="date" name="placement_date" required value="{{ old('placement_date', date('Y-m-d')) }}"
                               class="w-full px-4 py-2.5 bg-emerald-50 border border-zinc-200 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-zinc-700 uppercase tracking-tight">Breed Name / Type</label>
                        <input type="text" name="breed" value="{{ old('breed') }}" placeholder="Ex: Cobb 500 / Ross 308"
                               class="w-full px-4 py-2.5 bg-emerald-50 border border-zinc-200 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all">
                    </div>
                </div>

                {{-- Placement Details --}}
                <div class="space-y-5">
                    <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-widest border-b border-zinc-200 pb-2">2. Placement Details</h3>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-zinc-700 uppercase tracking-tight">Chick Count <span class="text-red-500">*</span></label>
                        <input type="number" name="initial_count" required value="{{ old('initial_count') }}" placeholder="0"
                               class="w-full px-4 py-2.5 bg-emerald-50 border border-zinc-200 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all text-lg font-black">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-zinc-700 uppercase tracking-tight">Avg. Placement Weight (grams)</label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="avg_placement_weight" step="0.01" value="{{ old('avg_placement_weight') }}" placeholder="0.00"
                                   class="flex-1 px-4 py-2.5 bg-emerald-50 border border-zinc-200 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all">
                            <span class="text-[10px] font-bold text-zinc-400">grams</span>
                        </div>
                        <p class="text-[10px] text-zinc-400 font-medium italic mt-1">Typical range: 35g - 45g</p>
                    </div>

                    <div class="pt-4">
                        <div class="p-4 bg-blue-50 rounded-xl border border-blue-100 border-dashed">
                            <div class="flex items-start gap-3">
                                <span class="text-xl">ℹ
                                <p class="text-[11px] text-blue-700 leading-relaxed font-medium">
                                    Starting a batch will allow you to link future purchases (Feed/Chicks) and record daily consumption for performance tracking.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-zinc-100">
                <a href="{{ route('inventory.batches.index') }}" class="px-6 py-2.5 text-sm font-semibold text-zinc-600 hover:text-zinc-950 transition-colors">Cancel</a>
                <button type="submit" class="px-10 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-md transition-all active:scale-95">
                    Register Batch 
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
