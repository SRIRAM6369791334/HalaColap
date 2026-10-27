@extends('layouts.app')

@section('title', 'Edit Batch')

@section('content')
<div class="mb-6">
    <a href="{{ route('inventory.batches.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 uppercase tracking-wider mb-2 inline-block">← Back to Batches</a>
    <h1 class="text-2xl font-bold text-zinc-950">Edit Batch: {{ $batch->batch_code }}</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Update flock status or placement details</p>
</div>

<div class="max-w-4xl">
    <div class="bg-gradient-to-br from-white via-emerald-50/40 to-sky-50/40 rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <form action="{{ route('inventory.batches.update', $batch->id) }}" method="POST" class="p-6 space-y-8">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                {{-- Identification --}}
                <div class="space-y-5">
                    <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-widest border-b border-zinc-200 pb-2">1. Current Status</h3>
                    
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-zinc-700 uppercase tracking-tight">Batch Status <span class="text-red-500">*</span></label>
                        <select name="status" id="batch-status" required class="w-full px-4 py-2.5 bg-gradient-to-br from-white via-emerald-50/30 to-sky-50/30 border border-zinc-200 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all font-bold">
                            <option value="Active" {{ old('status', $batch->status) == 'Active' ? 'selected' : '' }}>Active (Ongoing)</option>
                            <option value="Closed" {{ old('status', $batch->status) == 'Closed' ? 'selected' : '' }}>Closed (Sold/Empty)</option>
                        </select>
                    </div>

                    <div id="closed-date-container" class="space-y-1.5 {{ $batch->status === 'Closed' ? '' : 'hidden' }}">
                        <label class="text-[10px] font-bold text-zinc-700 uppercase tracking-tight">Closing Date <span class="text-red-500">*</span></label>
                        <input type="date" name="closed_at" value="{{ old('closed_at', $batch->closed_at ? $batch->closed_at->format('Y-m-d') : date('Y-m-d')) }}"
                               class="w-full px-4 py-2.5 bg-emerald-50 border border-zinc-200 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-zinc-700 uppercase tracking-tight">Batch Code</label>
                        <input type="text" name="batch_code" value="{{ old('batch_code', $batch->batch_code) }}" readonly
                               class="w-full px-4 py-2.5 bg-sky-50 border border-zinc-200 rounded-lg text-zinc-500 font-mono">
                    </div>
                </div>

                {{-- Population Control --}}
                <div class="space-y-5">
                    <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-widest border-b border-zinc-200 pb-2">2. Population Management</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-zinc-700 uppercase tracking-tight">Initial Count</label>
                            <input type="number" name="initial_count" value="{{ old('initial_count', $batch->initial_count) }}" 
                                   class="w-full px-4 py-2.5 bg-emerald-50 border border-zinc-200 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-zinc-700 uppercase tracking-tight">Current Count</label>
                            <input type="number" name="current_count" value="{{ old('current_count', $batch->current_count) }}"
                                   class="w-full px-4 py-2.5 bg-gradient-to-br from-white via-emerald-50/30 to-sky-50/30 border border-zinc-200 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all font-black text-emerald-600">
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-zinc-700 uppercase tracking-tight">Breed Name</label>
                        <input type="text" name="breed" value="{{ old('breed', $batch->breed) }}"
                               class="w-full px-4 py-2.5 bg-emerald-50 border border-zinc-200 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-zinc-100">
                <a href="{{ route('inventory.batches.index') }}" class="px-6 py-2.5 text-sm font-semibold text-zinc-600 hover:text-zinc-950 transition-colors">Cancel</a>
                <button type="submit" class="px-10 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-md transition-all active:scale-95">
                    Update Batch Data 
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('batch-status').addEventListener('change', function() {
    const container = document.getElementById('closed-date-container');
    if (this.value === 'Closed') {
        container.classList.remove('hidden');
    } else {
        container.classList.add('hidden');
    }
});
</script>
@endpush
@endsection
