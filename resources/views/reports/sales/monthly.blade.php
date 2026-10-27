@extends('layouts.app')
@section('title', 'Monthly Financial Audit')

@section('content')
<x-page-header 
    title="Monthly Financial Audit" 
    subtitle="High-level executive summary of monthly revenue streams">
    <div class="flex items-center gap-3">
        <x-button variant="outline" onclick="window.print()" icon="ph-printer">Print View</x-button>
        <x-button variant="primary" href="{{ route('reports.sales.export-pdf', ['month' => $month, 'year' => $year]) }}" icon="ph-download">Export PDF</x-button>
    </div>
</x-page-header>

<x-card class="mb-6">
    <form action="{{ route('reports.sales.monthly') }}" method="GET" class="flex flex-col md:flex-row items-end gap-6">
        <div class="flex-1">
            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Target Month</label>
            <x-form.select name="month">
                @foreach(range(1, 12) as $m)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                @endforeach
            </x-form.select>
        </div>
        <div class="flex-1">
            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Target Year</label>
            <x-form.select name="year">
                @foreach(range(now()->year - 5, now()->year) as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </x-form.select>
        </div>
        <div class="pb-[2px]">
            <x-button type="submit" variant="primary">Refresh Report</x-button>
        </div>
    </form>
</x-card>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <x-stat-card title="Gross Monthly Sale" value="Rs {{ number_format($totalSale, 2) }}" icon="ph-currency-inr" color="sky" />
    <x-stat-card title="Accrued GST" value="Rs {{ number_format($bills->sum('gst_amount'), 2) }}" icon="ph-percent" color="emerald" />
    <x-stat-card title="Liquid Collections" value="Rs {{ number_format($bills->where('status', 'paid')->sum('net_amount'), 2) }}" icon="ph-money" color="indigo" />
</div>

<x-card>
    <x-data-table>
        <x-slot name="head">
            <tr>
                <th>Customer Portfolio</th>
                <th class="text-right">Aggregate Sales</th>
                <th class="text-right">Tax Contribution</th>
                <th class="text-right">Unrealized Due</th>
            </tr>
        </x-slot>
        @php $grouped = $bills->groupBy('customer_id'); @endphp
        @forelse($grouped as $customerId => $customerBills)
            <tr>
                <td>
                    <div class="flex items-center gap-4">
                        <div class="flex flex-col">
                            <span class="font-black text-zinc-950 tracking-tight text-lg">{{ $customerBills->first()->customer->name ?? 'WALK-IN' }}</span>
                            <span class="text-[10px] text-zinc-400 font-bold uppercase tracking-widest">{{ $customerBills->count() }} Transactions</span>
                        </div>
                    </div>
                </td>
                <td class="text-right font-black text-zinc-950 text-lg italic"><x-currency :amount="$customerBills->sum('net_amount')" /></td>
                <td class="text-right font-bold text-emerald-600"><x-currency :amount="$customerBills->sum('gst_amount')" /></td>
                <td class="text-right font-black text-red-600 text-lg">
                    <x-currency :amount="$customerBills->where('status', 'unpaid')->sum('net_amount')" />
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-10 py-24 text-center text-zinc-500 font-medium">
                    No Data Available. Monthly revenue stream is empty for this period.
                </td>
            </tr>
        @endforelse
    </x-data-table>
</x-card>
@endsection
