@extends('layouts.app')
@section('title', 'Item Master')

@section('content')
<div class="space-y-6">

    <x-page-header title="Item Master" subtitle="Manage MSR BROILERS resources and inventory definitions">
        <x-button href="{{ route('inventory.items.create') }}" icon="add">
            Register New Item
        </x-button>
    </x-page-header>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            title="Total Items" 
            value="{{ $items->total() }}" 
            icon="inventory_2"
            color="emerald"
        />
        <x-stat-card 
            title="Chick Breeds" 
            value="{{ $items->where('type', 'Chick')->count() }}" 
            icon="cruelty_free"
            color="amber"
        />
        <x-stat-card 
            title="Medications" 
            value="{{ $items->where('type', 'Medicine')->count() + $items->where('type', 'Vaccine')->count() }}" 
            icon="vaccines"
            color="sky"
        />
        <x-stat-card 
            title="Equipments" 
            value="{{ $items->where('type', 'Equipment')->count() }}" 
            icon="construction"
            color="rose"
        />
    </div>

    {{-- Table Card --}}
    <x-card>
        <div class="flex flex-col sm:flex-row gap-4 mb-4 justify-between items-center">
            <form action="{{ route('inventory.items.index') }}" method="GET" class="flex flex-1 gap-2 w-full max-w-lg">
                <div class="flex-1">
                    <x-search name="search" value="{{ request('search') }}" placeholder="Search by name or brand…" />
                </div>
                <div class="w-48">
                    <x-form.select name="type" onchange="this.form.submit()" :options="['' => 'All Categories', 'Feed' => 'Feed', 'Chick' => 'Chick', 'Medicine' => 'Medicine', 'Vaccine' => 'Vaccine', 'Equipment' => 'Equipment', 'Other' => 'Other']" value="{{ request('type') }}" />
                </div>
                @if(request()->anyFilled(['search', 'type']))
                    <x-button variant="ghost" href="{{ route('inventory.items.index') }}" color="rose">Clear</x-button>
                @endif
            </form>
        </div>

        <x-data-table>
            <x-slot name="header">
                <tr>
                    <th>Item Description</th>
                    <th>Category & Type</th>
                    <th>Unit Logics</th>
                    <th class="text-right">Current Stock</th>
                    <th class="text-center">Actions</th>
                </tr>
            </x-slot>
            @forelse($items as $item)
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                    <td class="px-4 py-3">
                        <div class="font-bold text-zinc-900 dark:text-white">{{ $item->name }}</div>
                        <div class="text-xs text-zinc-500 uppercase">{{ $item->brand ?: 'Master Record' }}</div>
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $badgeColor = match($item->type) {
                                'Feed' => 'emerald',
                                'Chick' => 'amber',
                                'Medicine' => 'purple',
                                default => 'sky',
                            };
                        @endphp
                        <x-badge :color="$badgeColor">{{ $item->type }}</x-badge>
                        <div class="text-xs text-zinc-500 mt-1">{{ $item->category ?: 'General' }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div>
                                <div class="text-[10px] text-zinc-400 uppercase font-bold">Base</div>
                                <div class="font-medium text-zinc-700 dark:text-zinc-300">{{ $item->base_unit }}</div>
                            </div>
                            <div class="w-px h-5 bg-zinc-200 dark:bg-zinc-700"></div>
                            <div>
                                <div class="text-[10px] text-zinc-400 uppercase font-bold">Conversion</div>
                                <div class="text-xs text-zinc-500">1 Bag = {{ number_format($item->conversion_rate, 1) }} {{ $item->base_unit }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="text-lg font-black {{ $item->current_stock <= 50 ? 'text-rose-600' : 'text-zinc-900 dark:text-white' }}">
                            {{ number_format($item->current_stock, 0) }}
                        </div>
                        <div class="text-xs text-zinc-500 uppercase">{{ $item->base_unit }}</div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <x-button variant="ghost" size="sm" href="{{ route('inventory.items.edit', $item) }}" icon="edit" class="text-sky-600" />
                            <form action="{{ route('inventory.items.destroy', $item) }}" method="POST" onsubmit="return confirm('Delete {{ $item->name }}?')">
                                @csrf @method('DELETE')
                                <x-button type="submit" variant="ghost" size="sm" icon="delete" class="text-rose-600" />
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center">
                        <div class="flex justify-center mb-4 text-zinc-400">
                            <span class="material-symbols-rounded text-4xl">inventory_2</span>
                        </div>
                        <p class="font-bold text-zinc-900 dark:text-white">No items found</p>
                        <p class="text-sm text-zinc-500">Start by registering your first item.</p>
                    </td>
                </tr>
            @endforelse
        </x-data-table>

        @if($items->hasPages())
            <div class="mt-4">
                {{ $items->withQueryString()->links() }}
            </div>
        @endif
    </x-card>

</div>
@endsection

