@extends('layouts.app')
@section('title', 'Daily Purchase Report')

@section('content')
<x-page-header 
    title="Daily Purchase Report" 
    subtitle="Summary for {{ \Carbon\Carbon::parse($date)->format('d F, Y') }}">
    <div class="flex items-center gap-3">
        <x-button variant="outline" onclick="window.print()" icon="ph-printer">Print</x-button>
        <x-button variant="primary" href="{{ route('reports.purchases.export-pdf', ['date' => $date]) }}" icon="ph-download">Export PDF</x-button>
    </div>
</x-page-header>

<x-card class="mb-6">
    <form action="{{ route('reports.purchases.daily') }}" method="GET" class="flex flex-col md:flex-row items-end gap-4">
        <div class="flex-1 max-w-xs">
            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Select Date</label>
            <x-form.input type="date" name="date" :value="$date" />
        </div>
        <x-button type="submit" variant="primary">Fetch Report</x-button>
    </form>
</x-card>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <x-stat-card title="Total Purchase Amount" value="Rs {{ number_format($purchases->sum('total_amount'), 0, '.', ',') }}" icon="ph-shopping-cart" color="sky" />
    <x-stat-card title="Total GST" value="Rs {{ number_format($purchases->sum('gst_amount'), 0, '.', ',') }}" icon="ph-percent" color="emerald" />
    <x-stat-card title="Total Items" value="{{ $purchases->count() }}" icon="ph-package" color="blue" />
</div>

<x-card>
    <x-data-table>
        <x-slot name="head">
            <tr>
                <th>Vendor</th>
                <th>Item Details</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Rate</th>
                <th class="text-right">GST</th>
                <th class="text-right">Total</th>
            </tr>
        </x-slot>
        @forelse($purchases as $purchase)
        <tr>
            <td class="font-medium text-zinc-950">{{ $purchase->vendor->name ?? $purchase->vendor_name }}</td>
            <td class="text-zinc-500 font-semibold uppercase tracking-tight text-[11px]">{{ $purchase->item }}</td>
            <td class="text-center font-mono text-zinc-600">{{ $purchase->quantity }}</td>
            <td class="text-right font-mono text-zinc-500"><x-currency :amount="$purchase->rate" /></td>
            <td class="text-right font-mono text-zinc-400 text-xs"><x-currency :amount="$purchase->gst_amount" /></td>
            <td class="text-right font-mono font-bold text-zinc-950"><x-currency :amount="$purchase->total_amount" /></td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="px-6 py-12 text-center text-zinc-400">
                No purchase records found for this date.
            </td>
        </tr>
        @endforelse
    </x-data-table>
</x-card>
@endsection

