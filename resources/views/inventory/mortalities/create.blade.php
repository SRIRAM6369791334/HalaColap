@extends('layouts.app')
@section('title', 'Record Mortality')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <x-page-header title="Record Daily Mortality" subtitle="Deduct dead birds from active flock counts">
        <x-button variant="ghost" href="{{ route('inventory.mortalities.index') }}" icon="arrow_back" size="sm">
            Back to Mortalities
        </x-button>
    </x-page-header>

    <x-card>
        <form action="{{ route('inventory.mortalities.store') }}" method="POST">
            @csrf
            
            <div class="space-y-8">
                {{-- Date --}}
                <div>
                    <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-2">1. Date of Occurrence</label>
                    <x-form.input type="date" name="date" required value="{{ old('date', date('Y-m-d')) }}" class="font-bold text-zinc-950 dark:text-white" />
                </div>

                {{-- Batch --}}
                <div>
                    <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-2">2. Target Batch (Flock)</label>
                    <x-form.select name="batch_id" required>
                        <option value="">Select Batch...</option>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" {{ old('batch_id') == $batch->id ? 'selected' : '' }}>
                                {{ $batch->batch_code }} - {{ $batch->breed }} (Current: {{ $batch->current_count }} birds)
                            </option>
                        @endforeach
                    </x-form.select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    {{-- Count --}}
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-2">3. Mortality Count (Birds)</label>
                        <x-form.input type="number" name="count" required min="1" placeholder="Enter number..." class="font-black text-3xl text-rose-600 dark:text-rose-400 bg-rose-50/50 dark:bg-rose-900/10 border-rose-200 dark:border-rose-800 focus:border-rose-500 focus:ring-rose-500" />
                    </div>

                    {{-- Reason --}}
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-2">4. Primary Reason</label>
                        <x-form.select name="reason" :options="['Disease' => 'Disease / Outbreak', 'Heat Stress' => 'Heat Stress', 'Cold Stress' => 'Cold Stress', 'Cannibalism' => 'Pecking / Cannibalism', 'Smothering' => 'Smothering (Piling)', 'Natural' => 'Natural / Weakness', 'Other' => 'Other']" placeholder="Select Reason..." />
                    </div>
                </div>

                {{-- Remarks --}}
                <div>
                    <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-2">5. Detailed Remarks</label>
                    <textarea name="remarks" rows="3" placeholder="Additional details about the event..."
                              class="w-full px-4 py-3 border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-shadow text-sm text-zinc-800 dark:text-zinc-200 font-medium placeholder:text-zinc-400"></textarea>
                </div>
            </div>

            <x-button type="submit" class="w-full justify-center py-4 mt-10 text-base !bg-rose-600 hover:!bg-rose-700 !text-white shadow-lg shadow-rose-600/20" icon="warning">
                Confirm & Record Deaths 
            </x-button>
        </form>
    </x-card>

    {{-- Danger Zone Note --}}
    <div class="p-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl flex gap-6 items-center">
        <div class="w-16 h-16 shrink-0 bg-amber-100 dark:bg-amber-900/50 rounded-2xl flex items-center justify-center text-amber-600 dark:text-amber-400">
            <span class="material-symbols-rounded text-3xl">warning</span>
        </div>
        <div>
            <h4 class="font-bold text-lg text-amber-800 dark:text-amber-300 mb-1">Batch Count Impact</h4>
            <p class="text-amber-700 dark:text-amber-400 text-sm leading-relaxed">This action will <strong class="text-amber-900 dark:text-amber-200">permanently subtract</strong> the count from the active flock. Accuracy is critical for calculating survival rates and feeding requirements.</p>
        </div>
    </div>
</div>
@endsection
