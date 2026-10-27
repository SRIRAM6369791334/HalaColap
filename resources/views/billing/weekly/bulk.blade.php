@extends('layouts.app')
@section('title', 'Bulk Weekly Billing')

@section('content')
<div class="mb-6 animate-fade-in">
    <a href="{{ route('billing.weekly.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 hover:text-emerald-700 uppercase tracking-wider mb-2">
        <span class="material-symbols-rounded text-sm">arrow_back</span>
        Back to Weekly Billing
    </a>
    <h1 class="font-cabinet text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">Bulk Billing Generation</h1>
    <p class="mt-1 font-outfit text-sm text-zinc-500 dark:text-zinc-400">Select multiple dealers to generate weekly bills in one click</p>
</div>

<div class="max-w-4xl animate-fade-in">
    <x-card>
        <div class="p-6">
            <form action="{{ route('billing.weekly.bulkStore') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="space-y-4">
                        <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-widest border-b border-zinc-200 dark:border-zinc-800 pb-2 font-outfit">1. Billing Period</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <x-form.input type="date" name="period_start" label="Start Date" required />
                            <x-form.input type="date" name="period_end" label="End Date" required />
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-widest border-b border-zinc-200 dark:border-zinc-800 pb-2 font-outfit">2. Default Values</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <x-form.input type="number" name="amount" label="Flat Amount (Rs)" step="0.01" required placeholder="0.00" class="text-indigo-600 font-bold" />
                            <x-form.select name="status" label="Initial Status" required>
                                <option value="Generated">Generated</option>
                                <option value="Pending">Pending</option>
                            </x-form.select>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 mb-8">
                    <div class="flex justify-between items-center border-b border-zinc-200 dark:border-zinc-800 pb-2">
                        <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-widest font-outfit">3. Select Dealers</h3>
                        <button type="button" onclick="toggleAll(this)" class="text-[10px] font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 uppercase transition-colors">Select All</button>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 max-h-64 overflow-y-auto p-1 font-outfit custom-scrollbar">
                        @foreach($dealers as $dealer)
                        <label class="flex items-center gap-3 p-3 bg-zinc-50 dark:bg-zinc-800/50 hover:bg-zinc-100 dark:hover:bg-zinc-700 border border-zinc-200 dark:border-zinc-700 hover:border-indigo-200 dark:hover:border-indigo-500/50 rounded-lg cursor-pointer transition-all group">
                            <input type="checkbox" name="dealer_ids[]" value="{{ $dealer->id }}" class="dealer-checkbox w-4 h-4 text-indigo-600 rounded border-zinc-300 focus:ring-indigo-500 transition-all bg-white dark:bg-zinc-900">
                            <div>
                                <p class="text-sm font-bold text-zinc-900 dark:text-zinc-100 group-hover:text-indigo-900 dark:group-hover:text-indigo-300 transition-colors">{{ $dealer->firm_name }}</p>
                                <p class="text-[10px] text-zinc-500">{{ $dealer->route ?: 'No Route' }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="pt-6 border-t border-zinc-200 dark:border-zinc-800 flex justify-end gap-3">
                    <x-button type="submit" variant="primary" icon="layers" class="w-full sm:w-auto">
                        Run Bulk Generation ⚡
                    </x-button>
                </div>
            </form>
        </div>
    </x-card>
</div>

@push('scripts')
<script>
    function toggleAll(btn) {
        const checkboxes = document.querySelectorAll('.dealer-checkbox');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !allChecked);
        btn.textContent = allChecked ? 'Select All' : 'Deselect All';
    }
</script>
@endpush
@endsection
