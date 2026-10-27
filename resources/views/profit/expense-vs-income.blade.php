@extends('layouts.app')
@section('title', 'Expense vs Income')

@section('content')
<div class="mb-2">
    <a href="{{ route('profit.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 uppercase tracking-wider inline-block">← Back to Overview</a>
</div>
<x-page-header 
    title="Expense vs Income Matrix" 
    subtitle="Comparative study of business efficiency" />

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <x-card>
        <x-slot name="header">
            <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-widest text-center">Income Breakdown (This Month)</h3>
        </x-slot>
        <div class="flex justify-center mb-10">
            <div class="relative w-48 h-48 rounded-full border-[16px] border-emerald-500 flex items-center justify-center">
                <div class="text-center">
                    <p class="text-[10px] font-bold text-zinc-400 uppercase">Revenue</p>
                    <p class="text-xl font-black text-zinc-950">Rs {{ number_format($summary['revenue'], 0) }}</p>
                </div>
            </div>
        </div>
        <div class="space-y-4">
            <div class="flex justify-between items-center text-sm">
                <span class="text-zinc-500">Scheduled Invoicing</span>
                <span class="font-bold text-zinc-950">65%</span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-zinc-500">Counter Sales</span>
                <span class="font-bold text-zinc-950">35%</span>
            </div>
        </div>
    </x-card>

    <x-card>
        <x-slot name="header">
            <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-widest text-center">Expense Matrix (Current)</h3>
        </x-slot>
        <div class="flex justify-center mb-10">
            <div class="relative w-48 h-48 rounded-full border-[16px] border-rose-500 flex items-center justify-center">
                <div class="text-center">
                    <p class="text-[10px] font-bold text-zinc-400 uppercase">Outflow</p>
                    <p class="text-xl font-black text-zinc-950">Rs {{ number_format($summary['purchase'] + $summary['expenses'], 0) }}</p>
                </div>
            </div>
        </div>
        <div class="space-y-4">
            <div class="flex justify-between items-center text-sm">
                <span class="text-zinc-500">Procurement (Stock)</span>
                <span class="font-bold text-zinc-950">Rs {{ number_format($summary['purchase'], 0) }}</span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-zinc-500">Operationals & EMIs</span>
                <span class="font-bold text-zinc-950">Rs {{ number_format($summary['expenses'], 0) }}</span>
            </div>
        </div>
    </x-card>
</div>

<x-card class="mt-8 !bg-emerald-50 !border-emerald-100 mb-8">
    <p class="text-emerald-800 text-sm text-center">
        <strong>Efficiency Ratio:</strong> Your business is currently retaining 
        <span class="font-black text-lg text-emerald-900">{{ number_format($summary['profit'] / ($summary['revenue'] ?: 1) * 100, 1) }}%</span> 
        of every Rupee generated after all procurement and operational expenses.
    </p>
</x-card>

<x-card title="Income vs Expense Trend">
    <canvas id="trendChart" class="w-full h-80"></canvas>
</x-card>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const weeklyData = @json($weeklyData);
    
    const labels = weeklyData.map(d => d.week).reverse();
    const income = weeklyData.map(d => d.revenue).reverse();
    const expense = weeklyData.map(d => d.expenses + d.purchase).reverse();

    const ctxTrend = document.getElementById('trendChart').getContext('2d');

    const incGrad = ctxTrend.createLinearGradient(0, 0, 0, 400);
    incGrad.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
    incGrad.addColorStop(1, 'rgba(16, 185, 129, 0)');

    const expGrad = ctxTrend.createLinearGradient(0, 0, 0, 400);
    expGrad.addColorStop(0, 'rgba(244, 63, 94, 0.4)');
    expGrad.addColorStop(1, 'rgba(244, 63, 94, 0)');

    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Income (Revenue)',
                    data: income,
                    borderColor: 'rgba(16, 185, 129, 1)', // Emerald
                    backgroundColor: incGrad,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: 'rgba(16, 185, 129, 1)',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
                {
                    label: 'Outflow (Purchases + Expenses)',
                    data: expense,
                    borderColor: 'rgba(244, 63, 94, 1)', // Rose
                    backgroundColor: expGrad,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: 'rgba(244, 63, 94, 1)',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: { 
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 20, font: { family: "'Outfit', sans-serif" } }
                },
                tooltip: {
                    backgroundColor: 'rgba(24, 24, 27, 0.9)',
                    titleFont: { family: "'Cabinet Grotesk', sans-serif", size: 14 },
                    bodyFont: { family: "'Outfit', sans-serif", size: 13 },
                    padding: 12,
                    cornerRadius: 12,
                    displayColors: true,
                    usePointStyle: true
                }
            },
            scales: {
                x: { 
                    grid: { display: false }, 
                    border: { display: false },
                    ticks: { font: { family: "'Outfit', sans-serif" } }
                },
                y: { 
                    beginAtZero: true,
                    grid: { color: 'rgba(161, 161, 170, 0.1)', borderDash: [5, 5] },
                    border: { display: false },
                    ticks: { font: { family: "'Outfit', sans-serif" } }
                }
            }
        }
    });
});
</script>
@endpush
