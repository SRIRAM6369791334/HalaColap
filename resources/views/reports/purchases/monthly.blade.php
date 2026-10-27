@extends('layouts.app')
@section('title', 'Monthly Purchase Report')

@section('content')
<x-page-header 
    title="Monthly Purchase Report" 
    subtitle="Summary for {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}">
    <div class="flex items-center gap-3">
        <x-button variant="outline" onclick="window.print()" icon="ph-printer">Print</x-button>
        <x-button variant="primary" href="{{ route('reports.purchases.export-pdf', ['month' => $month, 'year' => $year]) }}" icon="ph-download">Export PDF</x-button>
    </div>
</x-page-header>

<x-card class="mb-6">
    <form action="{{ route('reports.purchases.monthly') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div>
            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Select Month</label>
            <x-form.select name="month">
                @foreach(range(1, 12) as $m)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                @endforeach
            </x-form.select>
        </div>
        <div>
            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Select Year</label>
            <x-form.select name="year">
                @foreach(range(now()->year - 5, now()->year) as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </x-form.select>
        </div>
        <x-button type="submit" variant="primary">Filter Results</x-button>
    </form>
</x-card>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <x-stat-card title="Total Monthly Purchase" value="Rs {{ number_format($purchases->sum('total_amount'), 0, '.', ',') }}" icon="ph-shopping-cart" color="sky" />
    <x-stat-card title="Total GST Paid" value="Rs {{ number_format($purchases->sum('gst_amount'), 0, '.', ',') }}" icon="ph-percent" color="emerald" />
    <x-stat-card title="Active Vendors" value="{{ $purchases->unique('vendor_id')->count() }}" icon="ph-users" color="blue" />
</div>

<x-card>
    <x-data-table>
        <x-slot name="head">
            <tr>
                <th>Vendor</th>
                <th class="text-center">Order Count</th>
                <th class="text-right">Total Purchase</th>
                <th class="text-right">Total GST</th>
            </tr>
        </x-slot>
        @php
            $grouped = $purchases->groupBy('vendor_id');
        @endphp
        @forelse($grouped as $vendorId => $vendorPurchases)
        <tr>
            <td class="font-medium text-zinc-950">{{ $vendorPurchases->first()->vendor->name ?? $vendorPurchases->first()->vendor_name }}</td>
            <td class="text-center text-zinc-500 font-mono">{{ $vendorPurchases->count() }}</td>
            <td class="text-right font-mono font-bold text-zinc-950"><x-currency :amount="$vendorPurchases->sum('total_amount')" /></td>
            <td class="text-right font-mono text-zinc-400 text-xs"><x-currency :amount="$vendorPurchases->sum('gst_amount')" /></td>
        </tr>
        @empty
        <tr>
            <td colspan="4" class="px-6 py-12 text-center text-zinc-400">
                No purchase records found for this month.
            </td>
        </tr>
        @endforelse
    </x-data-table>
</x-card>
@endsection

