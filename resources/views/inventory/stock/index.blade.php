@extends('layouts.app')
@section('title', 'Stock Dashboard')

@section('content')

<div class="animate-fade-in">

    <x-page-header title="Stock Dashboard" subtitle="Real-time inventory overview and stock status alerts">
        <x-slot:actions>
            <x-button variant="primary" href="{{ route('inventory.stock.movements') }}" icon="receipt_long">
                View Stock Ledgers
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <x-stat-card 
            label="Tracked Items" 
            value="{{ $stats['total_items'] }}" 
            icon="inventory" 
            color="teal" />
        <x-stat-card 
            label="Low Stock" 
            value="{{ $stats['low_stock_count'] }}" 
            icon="warning" 
            color="amber" />
        <x-stat-card 
            label="Out of Stock" 
            value="{{ $stats['out_of_stock_count'] }}" 
            icon="error" 
            color="rose" />
        <x-stat-card 
            label="Active Items" 
            value="{{ $items->where('is_active', true)->count() }}" 
            icon="verified" 
            color="blue" />
    </div>

    {{-- Table Card --}}
    <x-card>
        <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <form action="{{ route('inventory.stock.index') }}" method="GET" class="flex flex-col sm:flex-row items-center gap-4 w-full">
                <div class="relative w-full max-w-xs">
                    <span class="material-symbols-rounded absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-[20px]">search</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search item..." class="w-full pl-10 pr-4 py-2 border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 rounded-lg text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors dark:text-zinc-100">
                </div>
                
                <select name="type" onchange="this.form.submit()" class="w-full sm:w-auto px-4 py-2 border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 rounded-lg text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors dark:text-zinc-100">
                    <option value="">All Types</option>
                    <option value="Feed" {{ request('type') == 'Feed' ? 'selected' : '' }}>Feed</option>
                    <option value="Medicine" {{ request('type') == 'Medicine' ? 'selected' : '' }}>Medicine</option>
                    <option value="Chicks" {{ request('type') == 'Chicks' ? 'selected' : '' }}>Chicks</option>
                    <option value="Vaccine" {{ request('type') == 'Vaccine' ? 'selected' : '' }}>Vaccine</option>
                </select>
            </form>
        </div>

        <x-data-table :headers="['Item Details', 'Type / Category', 'Available Stock', 'Stock Health', 'Actions']">
            @forelse($items as $item)
            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <x-avatar name="{{ $item->name }}" size="sm" />
                        <div>
                            <div class="font-bold text-zinc-900 dark:text-zinc-100">{{ $item->name }}</div>
                            <div class="text-xs font-mono text-zinc-500">{{ $item->code }}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item->type }}</div>
                    <div class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">{{ $item->category ?: 'General' }}</div>
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="font-jetbrains font-bold text-lg {{ $item->current_stock <= 0 ? 'text-rose-600 dark:text-rose-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                        {{ number_format($item->current_stock, 2) }}
                    </div>
                    <div class="text-xs text-zinc-500">{{ $item->base_unit }}</div>
                </td>
                <td class="px-6 py-4">
                    @php
                        $statusClass = match($item->stock_status) {
                            'Healthy' => 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800/50',
                            'Low Stock' => 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800/50',
                            'Out of Stock' => 'bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800/50',
                            default => 'bg-zinc-100 text-zinc-700 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700'
                        };
                    @endphp
                    <div class="flex flex-col gap-1 items-start">
                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-widest border {{ $statusClass }}">
                            {{ $item->stock_status }}
                        </span>
                        @if($item->stock_status === 'Low Stock')
                            <span class="text-[10px] text-amber-600 dark:text-amber-500 font-medium mt-0.5">Threshold: {{ number_format($item->low_stock_threshold) }}</span>
                        @endif
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('inventory.items.edit', $item->id) }}" class="text-zinc-400 hover:text-blue-600 transition-colors p-1.5" title="Edit Item">
                            <span class="material-symbols-rounded text-lg">edit</span>
                        </a>
                        <x-button variant="primary" href="{{ route('purchases.entry') }}?item_id={{ $item->id }}" size="sm" class="!py-1.5 !px-3">
                            Refill
                        </x-button>
                    </div>
                </td>
            </tr>
            @empty
            <x-slot:empty>
                <x-empty-state 
                    icon="inventory_2" 
                    title="No items found in inventory" 
                    description="Your query didn't match any tracked stock.">
                    <x-button href="{{ route('inventory.items.create') }}" variant="secondary" icon="add" class="mt-4">
                        Add your first product to track stock
                    </x-button>
                </x-empty-state>
            </x-slot:empty>
            @endforelse
        </x-data-table>
    </x-card>
</div>

@endsection

