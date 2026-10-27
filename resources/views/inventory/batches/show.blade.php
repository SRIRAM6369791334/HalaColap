@extends('layouts.app')

@section('title', 'Batch Details — ' . $batch->batch_code)

@section('content')
<div class="space-y-6">

    <div class="mb-2">
        <a href="{{ route('inventory.batches.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 hover:text-emerald-700 uppercase tracking-wider">
            <span class="material-symbols-rounded text-sm">arrow_back</span>
            Back to Batches
        </a>
    </div>

    <x-page-header title="{{ $batch->batch_code }}" subtitle="{{ $batch->breed ?: 'Generic Breed' }} — Placed {{ $batch->placement_date->format('d M Y') }}">
        <div class="flex items-center gap-3">
            <x-button href="{{ route('inventory.batches.edit', $batch) }}" variant="secondary" icon="edit">
                Edit Batch
            </x-button>
        </div>
    </x-page-header>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-card class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-sm">
                <span class="material-symbols-rounded">egg_alt</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-0.5">Current Count</p>
                <div class="flex items-end gap-1">
                    <h3 class="text-2xl font-black text-zinc-950 dark:text-white">{{ number_format($batch->current_count) }}</h3>
                    <span class="text-xs text-zinc-400 font-bold pb-1">/ {{ number_format($batch->initial_count) }}</span>
                </div>
                @php
                    $percent = ($batch->initial_count > 0) ? ($batch->current_count / $batch->initial_count) * 100 : 0;
                @endphp
                <div class="w-24 h-1.5 bg-zinc-100 dark:bg-zinc-800 rounded-full mt-1 overflow-hidden">
                    <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $percent }}%"></div>
                </div>
            </div>
        </x-card>

        <x-card class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shadow-sm">
                <span class="material-symbols-rounded">trending_down</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-0.5">Mortality Loss</p>
                <h3 class="text-2xl font-black text-rose-600">{{ number_format($batch->initial_count - $batch->current_count) }}</h3>
                @if($batch->initial_count > 0)
                    <p class="text-[10px] text-zinc-400 font-bold mt-0.5">
                        {{ number_format(($batch->initial_count - $batch->current_count) / $batch->initial_count * 100, 1) }}% loss rate
                    </p>
                @endif
            </div>
        </x-card>

        <x-card class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shadow-sm">
                <span class="material-symbols-rounded">schedule</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-0.5">Flock Age</p>
                @php
                    $ageDays = $batch->status === 'Active'
                        ? now()->diffInDays($batch->placement_date)
                        : optional($batch->closed_at)->diffInDays($batch->placement_date);
                @endphp
                <h3 class="text-2xl font-black text-blue-600">{{ $ageDays ?? '—' }} <span class="text-sm font-bold">Days</span></h3>
                @if($ageDays)
                    <p class="text-[10px] text-zinc-400 font-bold mt-0.5">{{ floor($ageDays / 7) }} weeks</p>
                @endif
            </div>
        </x-card>

        <x-card class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-sm">
                <span class="material-symbols-rounded">scale</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-0.5">Avg Placement Weight</p>
                <h3 class="text-2xl font-black text-amber-600">
                    {{ $batch->avg_placement_weight ? number_format($batch->avg_placement_weight, 2) . 'g' : '—' }}
                </h3>
            </div>
        </x-card>
    </div>

    {{-- Batch Details Card --}}
    <x-card>
        <div class="p-6">
            <h2 class="font-cabinet text-lg font-bold text-zinc-900 dark:text-zinc-50 mb-6">Batch Information</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-1">Batch Code</p>
                    <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ $batch->batch_code }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-1">Breed</p>
                    <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ $batch->breed ?: 'Not specified' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-1">Placement Date</p>
                    <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ $batch->placement_date->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-1">Status</p>
                    @if($batch->status === 'Active')
                        <x-badge variant="success">Active</x-badge>
                    @else
                        <x-badge variant="neutral">Closed</x-badge>
                    @endif
                </div>
                @if($batch->status === 'Closed' && $batch->closed_at)
                <div>
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-1">Closed On</p>
                    <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ $batch->closed_at->format('d M Y') }}</p>
                </div>
                @endif
                <div>
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-1">Initial Count</p>
                    <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ number_format($batch->initial_count) }} birds</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-1">Current Count</p>
                    <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ number_format($batch->current_count) }} birds</p>
                </div>
                @if($batch->avg_placement_weight)
                <div>
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-1">Avg Placement Weight</p>
                    <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ number_format($batch->avg_placement_weight, 3) }}g</p>
                </div>
                @endif
            </div>
        </div>
    </x-card>

    {{-- Actions --}}
    <div class="flex items-center gap-3">
        <x-button href="{{ route('inventory.batches.edit', $batch) }}" variant="secondary" icon="edit">
            Edit Batch
        </x-button>
        <form action="{{ route('inventory.batches.destroy', $batch) }}" method="POST"
              onsubmit="return confirm('Delete this batch record? All associated data will be affected.')">
            @csrf @method('DELETE')
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors">
                <span class="material-symbols-rounded text-lg">delete</span>
                Delete Batch
            </button>
        </form>
    </div>

</div>
@endsection
