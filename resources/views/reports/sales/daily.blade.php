@extends('layouts.app')

@section('title', 'Daily Sales Report')

@section('content')
<x-page-header 
    title="Daily Sales Report" 
    subtitle="Sales summary for {{ $date }}.">
    <x-button variant="outline" href="{{ route('reports.sales.export-pdf', ['date' => $date]) }}" icon="ph-download">
        Export PDF
    </x-button>
</x-page-header>

<x-card class="mb-6">
    <form action="{{ route('reports.sales.daily') }}" method="GET" class="flex flex-col gap-3 md:flex-row md:items-end">
        <div>
            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Date</label>
            <x-form.input type="date" name="date" :value="$date" />
        </div>
        <div class="pb-[2px]">
            <x-button type="submit" variant="primary">Apply</x-button>
        </div>
    </form>
</x-card>

<div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-8">
    <x-stat-card title="Total Sale" value="Rs {{ number_format($totalSale, 2) }}" icon="ph-currency-inr" color="sky" />
    <x-stat-card title="GST" value="Rs {{ number_format($totalGST, 2) }}" icon="ph-percent" color="emerald" />
    <x-stat-card title="Cash" value="Rs {{ number_format($cashSales, 2) }}" icon="ph-money" color="emerald" />
    <x-stat-card title="Credit" value="Rs {{ number_format($creditSales, 2) }}" icon="ph-credit-card" color="rose" />
</div>

<x-card>
    <x-data-table>
        <x-slot name="head">
            <tr>
                <th>Invoice</th>
                <th>Customer</th>
                <th>Payment</th>
                <th class="text-right">Net Amount</th>
            </tr>
        </x-slot>
        @forelse($dailyBills as $bill)
            <tr>
                <td class="font-semibold">{{ $bill->invoice_number }}</td>
                <td>{{ $bill->customer->name ?? 'N/A' }}</td>
                <td><x-badge :color="$bill->payment_mode === 'cash' ? 'emerald' : 'rose'">{{ ucfirst($bill->payment_mode) }}</x-badge></td>
                <td class="text-right font-bold"><x-currency :amount="$bill->net_amount" /></td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-zinc-500 py-12">No sales records found for this date.</td></tr>
        @endforelse
    </x-data-table>
</x-card>
@endsection
