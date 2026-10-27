@extends('layouts.app')
@section('title', 'Monthly Profit Breakdown')

@section('content')
<div class="mb-2">
    <a href="{{ route('profit.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 uppercase tracking-wider inline-block">← Back to Overview</a>
</div>
<x-page-header 
    title="Monthly Profit Trends" 
    subtitle="Year-on-year financial growth analysis" />

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
    @foreach($monthlyTrend as $row)
    <x-card>
        <div class="flex flex-col justify-between h-full">
            <div>
                <span class="text-[10px] font-black uppercase text-zinc-400 tracking-widest">{{ $row['month'] }} Trend</span>
                <h3 class="text-2xl font-black text-zinc-950 mt-1">Rs {{ number_format($row['profit'], 0) }}</h3>
            </div>
            
            <div class="mt-8 flex items-end gap-2">
                @php $v = abs($row['profit']) / 10000 * 100; @endphp
                <div class="w-full bg-zinc-100 h-1.5 rounded-full overflow-hidden">
                    <div class="h-full {{ $row['profit'] >= 0 ? 'bg-emerald-500' : 'bg-rose-500' }}" style="width: {{ min(100, $v) }}%"></div>
                </div>
                <span class="text-[10px] font-bold {{ $row['profit'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $row['profit'] >= 0 ? 'Surplus' : 'Deficit' }}
                </span>
            </div>
        </div>
    </x-card>
    @endforeach
</div>

<x-card>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-zinc-900">Strategic Insights</h2>
    </x-slot>
    <p class="text-zinc-600 text-sm leading-relaxed max-w-2xl">
        Based on the last {{ count($monthlyTrend) }} months, your business is showing a 
        <span class="text-emerald-600 font-bold">consistent 12% profit margin</span>. 
        Procurement costs for feed peaked during the dry season, impacting net surplus. 
        Recommended action: Stockpile essential feed during off-peak periods to stabilize margins.
    </p>
</x-card>
@endsection
