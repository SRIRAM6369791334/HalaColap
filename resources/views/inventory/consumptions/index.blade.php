@extends('layouts.app')
@section('title', 'Daily Usage (FCR)')

@section('content')
<div class="space-y-6">

    <x-page-header title="Daily Usage & Consumption" subtitle="Track feed and medicine usage to calculate Batch performance">
        <x-button href="{{ route('inventory.consumptions.create') }}" icon="add">
            Record Daily Usage
        </x-button>
    </x-page-header>

    {{-- Stats Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-card>
            <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-1">Total Feed Used (MTD)</p>
            <h3 class="text-2xl font-black text-zinc-950 dark:text-white">{{ number_format($consumptions->sum('quantity'), 2) }} <span class="text-sm font-normal text-zinc-400">kg</span></h3>
        </x-card>
        <x-card>
            <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-1">Active Batches Feeding</p>
            <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ $consumptions->unique('batch_id')->count() }}</h3>
        </x-card>
        <x-card>
            <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-1">Stock Health</p>
            <div class="flex items-center gap-2 mt-1">
                <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-sm font-bold text-zinc-700 dark:text-zinc-300">All Optimal</span>
            </div>
        </x-card>
    </div>

    {{-- Consumption Table --}}
    <x-card padding="none">
        <x-data-table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Batch (Flock)</th>
                    <th>Item / Feed</th>
                    <th class="text-right">Quantity</th>
                    <th>Source Warehouse</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($consumptions as $c)
                <tr class="group">
                    <td>
                        <span class="font-bold text-zinc-950 dark:text-white">{{ $c->date->format('d M, Y') }}</span>
                    </td>
                    <td>
                        <div class="flex flex-col">
                            <span class="font-black text-emerald-700 dark:text-emerald-400">{{ $c->batch->batch_code }}</span>
                            <span class="text-[10px] text-zinc-400 font-bold uppercase">{{ $c->batch->breed }}</span>
                        </div>
                    </td>
                    <td>
                        <x-badge variant="info">{{ $c->item->name }}</x-badge>
                    </td>
                    <td class="text-right">
                        <span class="font-black text-zinc-950 dark:text-white text-base">{{ number_format($c->quantity, 2) }}</span>
                        <span class="text-[10px] font-bold text-zinc-400 uppercase">{{ $c->unit }}</span>
                    </td>
                    <td>
                        <span class="text-zinc-500 font-medium">{{ $c->warehouse->name }}</span>
                    </td>
                    <td class="text-right">
                        <form action="{{ route('inventory.consumptions.destroy', $c->id) }}" method="POST" onsubmit="return confirm('Revert this usage and restore stock?')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 rounded-lg hover:bg-rose-50 text-rose-500 dark:hover:bg-rose-900/30 dark:text-rose-400 transition-colors" title="Revert Usage">
                                <span class="material-symbols-rounded text-lg">undo</span>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-20 text-center">
                        <div class="flex flex-col items-center opacity-40">
                            <span class="material-symbols-rounded text-5xl mb-4 text-emerald-500">receipt_long</span>
                            <p class="text-sm font-bold uppercase tracking-widest text-zinc-500">No consumption recorded for today</p>
                            <a href="{{ route('inventory.consumptions.create') }}" class="text-emerald-600 text-xs mt-2 underline font-bold">Start Recording Now</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </x-data-table>
        
        @if($consumptions->hasPages())
        <div class="px-6 py-4 border-t border-zinc-100 dark:border-zinc-800">
            {{ $consumptions->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
