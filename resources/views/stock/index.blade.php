@extends('layouts.app')

@section('title', 'Stock Inventory')

@section('content')
<div class="space-y-6">

    <x-page-header title="Stock Inventory" subtitle="Track stock levels and inventory transactions.">
        @can('edit stock')
        <x-button onclick="openModal('adjustModal')" icon="edit_square">
            Quick Adjust
        </x-button>
        @endcan
        <x-button href="{{ route('stock.batches.index') }}" variant="secondary" icon="inventory_2">
            Batch Management
        </x-button>
    </x-page-header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @forelse($summaries as $item)
            <x-stat-card 
                title="{{ $item->category }}" 
                value="{{ number_format($item->current_stock, 1) }} {{ $item->unit }}" 
                icon="inventory_2"
                :color="$item->current_stock < $item->reorder_level ? 'rose' : 'emerald'"
            >
                <div class="mt-2 font-bold text-zinc-900 dark:text-white">{{ $item->item_name }}</div>
                <p class="text-xs text-zinc-500 mt-1">Reorder level: {{ number_format($item->reorder_level, 1) }}</p>
                @if($item->current_stock < $item->reorder_level)
                    <div class="mt-2">
                        <x-badge color="rose">Low Stock</x-badge>
                    </div>
                @endif
            </x-stat-card>
        @empty
            <div class="col-span-full">
                <x-empty-state icon="inventory_2" title="No stock items found" description="There are currently no items in stock." />
            </div>
        @endforelse
    </div>

    <x-card>
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
            <div>
                <h2 class="font-black text-zinc-950 dark:text-white">Transaction History</h2>
                <p class="text-sm text-zinc-500">Movements from {{ $from }} to {{ $to }}.</p>
            </div>
            <form method="GET" class="flex flex-wrap gap-3">
                <div class="w-40">
                    <x-form.input type="date" name="from" value="{{ $from }}" />
                </div>
                <div class="w-40">
                    <x-form.input type="date" name="to" value="{{ $to }}" />
                </div>
                <x-button type="submit" icon="filter_list">Filter</x-button>
            </form>
        </div>

        <x-data-table>
            <x-slot name="header">
                <tr>
                    <th>Date</th>
                    <th>Item</th>
                    <th>Type</th>
                    <th class="text-right">Quantity</th>
                    <th>Notes</th>
                </tr>
            </x-slot>
            
            @forelse($movements as $txn)
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                    <td class="px-4 py-3 whitespace-nowrap">{{ optional($txn->date)->format('Y-m-d') }}</td>
                    <td class="px-4 py-3 font-semibold text-zinc-900 dark:text-white">{{ $txn->item_name }}</td>
                    <td class="px-4 py-3">
                        @php
                            $badgeColor = match($txn->txn_type) {
                                'OUT' => 'rose',
                                'IN' => 'emerald',
                                default => 'amber',
                            };
                        @endphp
                        <x-badge :color="$badgeColor">{{ $txn->txn_type ?? $txn->type }}</x-badge>
                    </td>
                    <td class="px-4 py-3 text-right font-bold {{ $txn->txn_type === 'OUT' ? 'text-rose-600' : 'text-emerald-600' }}">
                        {{ $txn->txn_type === 'OUT' ? '-' : '+' }}{{ number_format($txn->quantity, 1) }} {{ $txn->unit }}
                    </td>
                    <td class="px-4 py-3 text-zinc-500">{{ $txn->notes }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-zinc-500">No transactions found for this period.</td>
                </tr>
            @endforelse
        </x-data-table>

        <div class="mt-4">
            {{ $movements->appends(['from' => $from, 'to' => $to])->links() }}
        </div>
    </x-card>

</div>

<x-modal id="adjustModal" title="Adjust Stock">
    <form method="POST" action="{{ route('stock.adjust') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Item</label>
            <x-form.select name="item_name" required>
                @foreach($summaries as $item)
                    <option value="{{ $item->item_name }}">{{ $item->item_name }}</option>
                @endforeach
            </x-form.select>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Quantity Change</label>
                <x-form.input type="number" step="0.001" name="quantity" required />
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Date</label>
                <x-form.input type="date" name="date" value="{{ now()->toDateString() }}" required />
            </div>
        </div>
        <div>
            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Reason</label>
            <x-form.input type="text" name="reason" required />
        </div>
        <div class="mt-6 flex justify-end">
            <x-button type="submit" icon="save">Save Adjustment</x-button>
        </div>
    </form>
</x-modal>

@endsection

@push('scripts')
<script>
    function openModal(id) {
        window.dispatchEvent(new CustomEvent('open-modal', { detail: id }));
    }

    function closeModal(id) {
        window.dispatchEvent(new CustomEvent('close-modal', { detail: id }));
    }
</script>
@endpush
