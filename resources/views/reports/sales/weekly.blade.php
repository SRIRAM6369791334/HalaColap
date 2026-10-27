@extends('layouts.app')

@section('title', 'Weekly Performance Audit')

@section('content')
<x-page-header 
    title="Weekly Performance Audit" 
    subtitle="Consolidated report of weekly sales & receivables">
    <div class="flex items-center gap-3">
        <x-button variant="outline" onclick="window.print()" icon="ph-printer">Print View</x-button>
        <x-button variant="primary" href="{{ route('reports.sales.export-pdf', ['start' => $startDate, 'end' => $endDate]) }}" icon="ph-download">Export PDF</x-button>
    </div>
</x-page-header>

<x-card class="mb-6">
    <form action="{{ route('reports.sales.weekly') }}" method="GET" class="flex flex-col md:flex-row items-end gap-6">
        <div class="flex-1">
            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Period Start</label>
            <x-form.input type="date" name="start" :value="$startDate" />
        </div>
        <div class="flex-1">
            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Period End</label>
            <x-form.input type="date" name="end" :value="$endDate" />
        </div>
        <div class="pb-[2px]">
            <x-button type="submit" variant="primary">Refresh Audit</x-button>
        </div>
    </form>
</x-card>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <x-stat-card title="Total Weekly Revenue" value="Rs {{ number_format($totalSale, 2) }}" icon="ph-currency-inr" color="sky" />
    <x-stat-card title="Weekly Tax (GST)" value="Rs {{ number_format($bills->sum('gst_amount'), 2) }}" icon="ph-percent" color="emerald" />
    <x-stat-card title="Active Accounts" value="{{ $bills->unique('customer_id')->count() }}" icon="ph-users" color="indigo" />
    <x-stat-card title="Avg. Ticket Size" value="Rs {{ number_format($bills->avg('net_amount') ?: 0, 0) }}" icon="ph-receipt" color="amber" />
</div>

<x-card>
    <x-data-table>
        <x-slot name="head">
            <tr>
                <th>Customer & Identification</th>
                <th class="text-center">Audit Period</th>
                <th class="text-right">Taxable</th>
                <th class="text-right">GST</th>
                <th class="text-right">Total Net</th>
                <th class="text-center">Status</th>
            </tr>
        </x-slot>
        @forelse($bills as $bill)
            <tr>
                <td>
                    <div class="flex items-center gap-3">
                        <div class="flex flex-col">
                            <span class="font-black text-zinc-950 tracking-tight">{{ $bill->customer->name ?? 'WALK-IN' }}</span>
                            <span class="text-[10px] text-zinc-400 font-bold uppercase tracking-widest">REF #W-{{ $bill->id }}</span>
                        </div>
                    </div>
                </td>
                <td class="text-center font-bold text-zinc-500 italic">
                    {{ $bill->period_start->format('d M') }} - {{ $bill->period_end->format('d M Y') }}
                </td>
                <td class="text-right font-bold text-zinc-600 italic"><x-currency :amount="$bill->amount ?? ($bill->net_amount - $bill->gst_amount)" /></td>
                <td class="text-right font-bold text-emerald-600"><x-currency :amount="$bill->gst_amount" /></td>
                <td class="text-right font-black text-zinc-950"><x-currency :amount="$bill->net_amount" /></td>
                <td class="text-center">
                    @php
                        $isPaid = strtolower($bill->status) === 'paid';
                    @endphp
                    <x-badge :color="$isPaid ? 'emerald' : 'rose'">{{ $isPaid ? '✓ PAID' : '⚠ PENDING' }}</x-badge>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-8 py-12 text-center text-zinc-500 font-medium">
                    No Weekly Records. Select a broader range to view data
                </td>
            </tr>
        @endforelse
    </x-data-table>
</x-card>
@endsection
