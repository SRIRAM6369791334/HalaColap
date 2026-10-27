@extends('layouts.app')

@section('title', 'Batch Management')

@section('content')
<div class="space-y-6">

    <x-page-header title="Batch Management" subtitle="Track MSR BROILERS flock lifecycle, placements, and performance">
        <x-button href="{{ route('inventory.batches.create') }}" icon="add">
            Start New Batch
        </x-button>
    </x-page-header>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <x-card class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-sm">
                <span class="material-symbols-rounded">inventory_2</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-0.5">Active Batches</p>
                <h3 class="text-2xl font-black text-zinc-950">{{ $batches->where('status', 'Active')->count() }}</h3>
            </div>
        </x-card>
        <x-card class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shadow-sm">
                <span class="material-symbols-rounded">egg_alt</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-0.5">Total Chicks</p>
                <h3 class="text-2xl font-black text-blue-600">{{ number_format($batches->where('status', 'Active')->sum('current_count')) }}</h3>
            </div>
        </x-card>
        <x-card class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-sm">
                <span class="material-symbols-rounded">scale</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-0.5">Avg Placement</p>
                <h3 class="text-2xl font-black text-amber-600">{{ number_format($batches->avg('avg_placement_weight'), 2) }}g</h3>
            </div>
        </x-card>
        <x-card class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-zinc-50 text-zinc-400 flex items-center justify-center text-xl shadow-sm">
                <span class="material-symbols-rounded">verified</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-0.5">Closed Batches</p>
                <h3 class="text-2xl font-black text-zinc-400">{{ $batches->where('status', 'Closed')->count() }}</h3>
            </div>
        </x-card>
    </div>

    {{-- Filters --}}
    <x-card class="mb-6">
        <form action="{{ route('inventory.batches.index') }}" method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[240px]">
                <x-form.input type="text" name="search" value="{{ request('search') }}" placeholder="Search by batch code or breed..." />
            </div>
            
            <div class="flex items-center gap-2">
                <label class="text-[10px] font-bold text-zinc-400 uppercase">Status:</label>
                <x-form.select name="status" onchange="this.form.submit()" :options="['Active' => 'Active', 'Closed' => 'Closed']" value="{{ request('status') }}" placeholder="All Status" />
            </div>

            @if(request()->anyFilled(['search', 'status']))
                <a href="{{ route('inventory.batches.index') }}" class="text-xs font-bold text-rose-500 hover:bg-rose-50 px-3 py-2 rounded-lg transition-colors">Clear Filters</a>
            @endif
        </form>
    </x-card>

    {{-- Batches Table --}}
    <x-card padding="none">
        <x-data-table>
            <thead>
                <tr>
                    <th>Batch Details</th>
                    <th>Flock Age</th>
                    <th>Population</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                    <tr class="group">
                        <td>
                            <div class="flex flex-col">
                                <span class="font-bold text-zinc-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ $batch->batch_code }}</span>
                                <span class="text-[10px] text-zinc-500 font-bold uppercase tracking-tight">{{ $batch->breed ?: 'Generic Breed' }}</span>
                                <span class="text-[10px] text-zinc-400 mt-1">Placed: {{ $batch->placement_date->format('d M Y') }}</span>
                            </div>
                        </td>
                        <td>
                            @php
                                $days = $batch->status === 'Active' 
                                    ? now()->diffInDays($batch->placement_date) 
                                    : $batch->closed_at->diffInDays($batch->placement_date);
                            @endphp
                            <div class="flex flex-col">
                                <span class="text-lg font-black text-zinc-700 dark:text-zinc-300">{{ $days }} Days</span>
                                <span class="text-[10px] text-zinc-400 font-bold uppercase">Old</span>
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-col">
                                <div class="flex items-end gap-1">
                                    <span class="text-lg font-black text-zinc-900 dark:text-white">{{ number_format($batch->current_count) }}</span>
                                    <span class="text-xs text-zinc-400 font-bold pb-1">/ {{ number_format($batch->initial_count) }}</span>
                                </div>
                                <div class="w-24 h-1.5 bg-zinc-100 dark:bg-zinc-800 rounded-full mt-1 overflow-hidden">
                                    @php
                                        $percent = ($batch->initial_count > 0) ? ($batch->current_count / $batch->initial_count) * 100 : 0;
                                    @endphp
                                    <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $percent }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($batch->status === 'Active')
                                <x-badge variant="success">Active</x-badge>
                            @else
                                <x-badge variant="neutral">Closed</x-badge>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('inventory.batches.edit', $batch) }}" 
                                   class="p-2 rounded-lg hover:bg-blue-50 text-blue-500 dark:hover:bg-blue-900/30 dark:text-blue-400 transition-colors border border-transparent hover:border-blue-100 dark:hover:border-blue-900/50" title="Edit Batch">
                                    <span class="material-symbols-rounded text-lg">edit</span>
                                </a>
                                <form action="{{ route('inventory.batches.destroy', $batch) }}" method="POST"
                                      onsubmit="return confirm('Delete this batch record? All associated data will be affected.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 rounded-lg hover:bg-rose-50 text-rose-500 dark:hover:bg-rose-900/30 dark:text-rose-400 transition-colors border border-transparent hover:border-rose-100 dark:hover:border-rose-900/50" title="Delete">
                                        <span class="material-symbols-rounded text-lg">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center opacity-40">
                                <span class="material-symbols-rounded text-5xl mb-4 text-emerald-500">inventory_2</span>
                                <p class="text-sm font-bold uppercase tracking-widest text-zinc-500">No batches found in record</p>
                                <a href="{{ route('inventory.batches.create') }}" class="text-emerald-600 text-xs mt-2 underline font-bold">Start your first batch now</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-data-table>
        
        @if($batches->hasPages())
        <div class="px-6 py-4 border-t border-zinc-100 dark:border-zinc-800">
            {{ $batches->withQueryString()->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
