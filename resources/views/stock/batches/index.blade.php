@extends('layouts.app')
@section('title', 'Biological Asset Management')

@section('content')
<div class="space-y-6">

    <x-page-header title="Batch Evolution" subtitle="Monitoring lifecycle dynamics and biological stability">
        @can('create batches')
        <x-button onclick="openModal('batchModal')" icon="add">
            New Batch
        </x-button>
        @endcan
    </x-page-header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($batches as $batch)
        <x-card>
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $batch->batch_name }}</h3>
                    <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest mt-1">Arrival: {{ $batch->date_received->format('d M Y') }}</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest">Health Score</p>
                    @php $health = $batch->initial_count > 0 ? ($batch->current_count / $batch->initial_count) * 100 : 0; @endphp
                    <p class="text-lg font-black {{ $health > 95 ? 'text-emerald-500' : ($health > 90 ? 'text-amber-500' : 'text-rose-500') }}">
                        {{ number_format($health, 1) }}%
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-100 dark:border-zinc-800">
                    <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest">Initial</p>
                    <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ number_format($batch->initial_count) }}</p>
                </div>
                <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-100 dark:border-zinc-800">
                    <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest">Current</p>
                    <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($batch->current_count) }}</p>
                </div>
                <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-100 dark:border-zinc-800">
                    <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest">Mortality</p>
                    <p class="text-lg font-bold text-rose-500">{{ number_format($batch->initial_count - $batch->current_count) }}</p>
                </div>
            </div>

            <div class="flex gap-2">
                @can('edit batches')
                <x-button onclick="openMortalityModal({{ $batch->id }}, '{{ $batch->batch_name }}')" variant="danger" class="flex-1 justify-center py-2" size="sm">
                    Record Mortality
                </x-button>
                @endcan
                <x-button variant="secondary" class="py-2" size="sm">
                    Details
                </x-button>
            </div>
        </x-card>
        @empty
        <div class="col-span-full">
            <x-card padding="none">
                <div class="flex flex-col items-center justify-center py-20 text-center opacity-60">
                    <span class="material-symbols-rounded text-6xl mb-4 text-emerald-500">egg</span>
                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white">Swarm Empty</h3>
                    <p class="text-zinc-500 max-w-xs mt-2 text-sm">No active biological batches detected in the system. Initialize a new batch to begin tracking.</p>
                </div>
            </x-card>
        </div>
        @endforelse
    </div>
</div>
@endsection
