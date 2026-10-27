@extends('layouts.app')
@section('title', 'Weekly Purchase Report')

@section('content')
<x-page-header 
    title="Weekly Purchase Report" 
    subtitle="Summary for {{ \Carbon\Carbon::parse($startDate)->format('d M') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}">
    <div class="flex items-center gap-3">
        <x-button variant="outline" onclick="window.print()" icon="ph-printer">Print</x-button>
        <x-button variant="primary" href="{{ route('reports.purchases.export-pdf', ['start' => $startDate, 'end' => $endDate]) }}" icon="ph-download">Export PDF</x-button>
    </div>
</x-page-header>

<x-card class="mb-6">
    <form action="{{ route('reports.purchases.weekly') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div>
            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Start Date</label>
            <x-form.input type="date" name="start" :value="$startDate" />
        </div>
        <div>
            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">End Date</label>
            <x-form.input type="date" name="end" :value="$endDate" />
        </div>
        <x-button type="submit" variant="primary">Filter Period</x-button>
    </form>
</x-card>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <x-stat-card title="Total Purchase" value="Rs {{ number_format($purchases->sum('total_amount'), 0, '.', ',') }}" icon="ph-shopping-cart" color="sky" />
    <x-stat-card title="Total GST" value="Rs {{ number_format($purchases->sum('gst_amount'), 0, '.', ',') }}" icon="ph-percent" color="emerald" />
    <x-stat-card title="Total Qty" value="{{ $purchases->sum('quantity') }}" icon="ph-package" color="blue" />
    <x-stat-card title="Vendors Paid" value="{{ $purchases->unique('vendor_id')->count() }}" icon="ph-users" color="amber" />
</div>

<x-card>
    <x-data-table>
        <x-slot name="head">
            <tr>
                <th>Vendor</th>
                <th>Item Details</th>
                <th class="text-right">Total Amount</th>
                <th class="text-right">GST</th>
                <th class="text-center">Date</th>
            </tr>
        </x-slot>
        @forelse($purchases as $purchase)
        <tr>
            <td class="font-medium text-zinc-950">{{ $purchase->vendor->name ?? $purchase->vendor_name }}</td>
            <td class="text-zinc-500 font-semibold uppercase tracking-tight text-[11px]">{{ $purchase->item }}</td>
            <td class="text-right font-mono font-bold text-zinc-950"><x-currency :amount="$purchase->total_amount" /></td>
            <td class="text-right font-mono text-zinc-400 text-xs"><x-currency :amount="$purchase->gst_amount" /></td>
            <td class="text-center text-zinc-500 text-xs italic">{{ $purchase->date->format('d M Y') }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="px-6 py-12 text-center text-zinc-400">
                No purchase records found for this period.
            </td>
        </tr>
        @endforelse
    </x-data-table>
</x-card>
@endsection

