@extends('layouts.app')
@section('title', 'Batch Performance Dashboard')

@section('content')
<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-black text-zinc-950 tracking-tight">Performance Dashboard</h1>
        <p class="text-zinc-500 font-medium">Real-time flock analytics and FCR tracking</p>
    </div>

    {{-- Batch Selector --}}
    <form action="{{ route('inventory.analytics') }}" method="GET" id="batchFilterForm" class="flex items-center gap-3 bg-gradient-to-br from-white via-emerald-50/30 to-sky-50/30 p-2 rounded-2xl border border-zinc-200 shadow-sm">
        <span class="pl-3 text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Select Batch:</span>
        <select name="batch_id" onchange="document.getElementById('batchFilterForm').submit()" 
                class="bg-emerald-50 border-none rounded-xl px-4 py-2 font-black text-emerald-700 focus:ring-0 outline-none cursor-pointer">
            @foreach($activeBatches as $b)
                <option value="{{ $b->id }}" {{ $selectedBatchId == $b->id ? 'selected' : '' }}>
                    {{ $b->batch_code }} ({{ $b->breed }})
                </option>
            @endforeach
        </select>
    </form>
</div>

@if($stats)
    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Age Card --}}
        <div class="bg-gradient-to-br from-white via-emerald-50/30 to-sky-50/30 p-6 rounded-2xl border border-zinc-200 shadow-md shadow-zinc-200/60">
            <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-2xl mb-4">⏳</div>
            <h3 class="text-[10px] font-black text-zinc-400 uppercase tracking-[0.2em] mb-1">Batch Age</h3>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-black text-zinc-950">{{ $stats['age_days'] }}</span>
                <span class="text-sm font-bold text-zinc-400">Days</span>
            </div>
        </div>

        {{-- Survival Card --}}
        <div class="bg-gradient-to-br from-white via-emerald-50/30 to-sky-50/30 p-6 rounded-2xl border border-zinc-200 shadow-md shadow-zinc-200/60">
            <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-2xl mb-4"></div>
            <h3 class="text-[10px] font-black text-zinc-400 uppercase tracking-[0.2em] mb-1">Survival Rate</h3>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-black text-emerald-600">{{ $stats['survival_rate'] }}%</span>
                <span class="text-xs font-bold text-zinc-400">({{ number_format($stats['current_birds']) }} Live)</span>
            </div>
        </div>

        {{-- Feed Consumed Card --}}
        <div class="bg-gradient-to-br from-white via-emerald-50/30 to-sky-50/30 p-6 rounded-2xl border border-zinc-200 shadow-md shadow-zinc-200/60">
            <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center text-2xl mb-4"></div>
            <h3 class="text-[10px] font-black text-zinc-400 uppercase tracking-[0.2em] mb-1">Total Feed</h3>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-black text-amber-600">{{ number_format($stats['total_feed']) }}</span>
                <span class="text-sm font-bold text-zinc-400">Kg</span>
            </div>
        </div>

        {{-- FCR Projection Card --}}
        <div class="bg-gradient-to-br from-white via-emerald-50/30 to-sky-50/30 border border-zinc-200 p-6 rounded-2xl shadow-md shadow-zinc-200/60 text-white relative overflow-hidden">
            <div class="relative z-10">
                <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center text-2xl mb-4"></div>
                <h3 class="text-[10px] font-black text-emerald-300 uppercase tracking-[0.2em] mb-1">Avg Consumption</h3>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-black">{{ $stats['feed_per_bird'] }}</span>
                    <span class="text-sm font-bold text-emerald-300">kg/bird</span>
                </div>
            </div>
            <div class="absolute -right-4 -bottom-4 opacity-10 text-8xl font-black">FCR</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Consumption Chart --}}
        <div class="lg:col-span-2 bg-gradient-to-br from-white via-emerald-50/30 to-sky-50/30 p-8 rounded-3xl border border-zinc-200 shadow-lg">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-xl font-black text-zinc-950">Feed Usage Trend</h2>
                    <p class="text-sm text-zinc-400 font-medium">Daily consumption patterns (Last 14 days)</p>
                </div>
            </div>
            <div class="h-[350px]">
                <canvas id="consumptionChart"></canvas>
            </div>
        </div>

        {{-- Batch Details Side --}}
        <div class="space-y-6">
            <div class="bg-gradient-to-br from-white via-emerald-50/30 to-sky-50/30 border border-zinc-200 rounded-3xl p-8 text-white shadow-lg">
                <h2 class="text-lg font-black mb-6 flex items-center gap-2">
                    <span class="text-emerald-400"></span> Batch Summary
                </h2>
                <div class="space-y-4">
                    <div class="flex justify-between items-center py-3 border-b border-white/5">
                        <span class="text-zinc-400 text-sm font-bold">Initial Count</span>
                        <span class="font-black">{{ number_format($stats['batch']->initial_count) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-white/5">
                        <span class="text-zinc-400 text-sm font-bold">Total Mortality</span>
                        <span class="font-black text-red-400">{{ number_format($stats['total_mortality']) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-white/5">
                        <span class="text-zinc-400 text-sm font-bold">Breed Type</span>
                        <span class="font-black text-emerald-400">{{ $stats['batch']->breed }}</span>
                    </div>
                    <div class="flex justify-between items-center py-3">
                        <span class="text-zinc-400 text-sm font-bold">Current Status</span>
                        <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 text-[10px] font-black uppercase rounded-full tracking-widest">Active</span>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-white via-emerald-50/40 to-sky-50/40 rounded-3xl p-8 border border-zinc-200 shadow-md">
                <h2 class="text-lg font-black text-zinc-950 mb-4 flex items-center gap-2">
                    <span class="text-blue-500"></span> Optimization Tip
                </h2>
                <p class="text-sm text-zinc-500 leading-relaxed">
                    Based on your <strong>{{ $stats['survival_rate'] }}% survival rate</strong>, your mortality is within industry standards. 
                    Monitor the <strong>{{ $stats['feed_per_bird'] }}kg/bird</strong> intake closely against breed standards to ensure optimal weight gain.
                </p>
            </div>
        </div>
    </div>

@else
    <div class="bg-gradient-to-br from-white via-emerald-50/40 to-sky-50/40 rounded-3xl p-20 text-center border border-dashed border-zinc-200">
        <div class="text-6xl mb-6"></div>
        <h2 class="text-2xl font-black text-zinc-950">No Active Batches Found</h2>
        <p class="text-zinc-400 mt-2 max-w-sm mx-auto font-medium">You need at least one active flock to view performance analytics.</p>
        <a href="{{ route('inventory.batches.create') }}" class="mt-8 inline-flex px-8 py-4 bg-gradient-to-r from-emerald-600 to-sky-500 text-white font-black rounded-2xl hover:bg-emerald-700 transition-all shadow-md shadow-emerald-600/20">
            Create First Batch
        </a>
    </div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    @if($stats && count($chartData) > 0)
    const ctx = document.getElementById('consumptionChart').getContext('2d');
    
    // Create gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
    gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartData->pluck('day')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))->toArray()) !!},
            datasets: [{
                label: 'Feed Usage (Kg)',
                data: {!! json_encode($chartData->pluck('total')->toArray()) !!},
                borderColor: '#10b981',
                borderWidth: 4,
                tension: 0.4,
                fill: true,
                backgroundColor: gradient,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f3f4f6' },
                    border: { display: false }
                },
                x: {
                    grid: { display: false },
                    border: { display: false }
                }
            }
        }
    });
    @endif
</script>
@endpush
