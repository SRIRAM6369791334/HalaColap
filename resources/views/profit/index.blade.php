@extends('layouts.app')
@section('title', 'Profit & Loss Overview')

@section('content')
<x-page-header 
    title="Profit & Loss Dashboard" 
    subtitle="Real-time financial performance overview">
    <div class="flex gap-3">
        <x-button variant="secondary" href="{{ route('profit.monthly') }}">Monthly Breakdown</x-button>
        <x-button variant="primary" href="{{ route('profit.expense-vs-income') }}">Expense vs Income</x-button>
    </div>
</x-page-header>

{{-- Formula Infobox Banner --}}
<!-- <div class="mb-6 p-5 rounded-2xl bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-950/40 dark:to-teal-950/40 border border-emerald-200/80 dark:border-emerald-800/40 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        <div class="h-10 w-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-lg shadow-md">
            <span class="material-symbols-rounded">calculate</span>
        </div>
        <div>
            <h3 class="font-cabinet text-base font-extrabold text-emerald-950 dark:text-emerald-100">Profit & Loss Calculation Formula</h3>
            <p class="text-xs font-mono text-emerald-800 dark:text-emerald-300 font-semibold mt-0.5">
                Net Profit = Total Bills (Dealer & Customer Payments) − Total Vendor Pay − Total Expenses
            </p>
        </div>
    </div>
    <div class="text-xs font-bold text-emerald-700 dark:text-emerald-400 bg-white/80 dark:bg-zinc-900/80 px-4 py-2 rounded-xl border border-emerald-200 dark:border-emerald-800 shadow-xs">
        Includes Weight Loss Expenses & Operational Burn
    </div>
</div> -->

<x-card class="mb-6 !p-4 border border-zinc-200/80 dark:border-zinc-800/80 shadow-xs">
    <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
        <form method="GET" class="flex flex-wrap lg:flex-nowrap items-center gap-3">
            <div class="flex items-center gap-2 bg-zinc-100/80 dark:bg-zinc-800/80 px-3 py-1.5 rounded-xl border border-zinc-200/50 dark:border-zinc-700/50">
                <span class="material-symbols-rounded text-emerald-500 text-sm">calendar_today</span>
                <span class="text-xs font-black text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Year:</span>
                <select name="year" onchange="this.form.submit()" class="text-xs font-extrabold rounded-lg border-0 bg-transparent text-zinc-900 dark:text-zinc-100 focus:ring-0 py-0 pl-1 pr-6 cursor-pointer">
                    <option value="" class="dark:bg-zinc-900">-- Custom Range --</option>
                    @foreach($availableYears as $yr)
                        <option value="{{ $yr }}" {{ (string)$selectedYear === (string)$yr ? 'selected' : '' }} class="dark:bg-zinc-900">Year {{ $yr }}</option>
                    @endforeach
                </select>
            </div>

            <div class="h-6 w-px bg-zinc-200 dark:bg-zinc-800 hidden sm:block"></div>

            <div class="flex items-center gap-2">
                <input type="date" name="start_date" value="{{ $startDate }}" class="text-xs font-bold rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 py-2 px-3">
                <span class="text-zinc-400 font-bold">→</span>
                <input type="date" name="end_date" value="{{ $endDate }}" class="text-xs font-bold rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 py-2 px-3">
            </div>

            <x-button type="submit" variant="primary" class="!py-2 !px-4 !text-xs font-bold shadow-xs">
                <span class="material-symbols-rounded text-sm mr-1">filter_alt</span> Filter
            </x-button>

            @if(request()->filled('start_date') || request()->filled('end_date') || request()->filled('year'))
                <a href="{{ route('profit.index') }}" class="inline-flex items-center gap-1 px-3 py-2 text-xs font-bold rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 dark:border-rose-900/50 dark:text-rose-400 dark:hover:bg-rose-950/50 transition-all shadow-2xs">
                    <span class="material-symbols-rounded text-sm">restart_alt</span> Reset
                </a>
            @endif
        </form>

        <div class="flex items-center gap-2 pt-3 lg:pt-0 border-t lg:border-t-0 border-zinc-200/50 dark:border-zinc-800/50">
            <x-button variant="outline" href="{{ route('profit.export', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="!py-2 !px-3.5 !text-xs font-bold">
                <span class="material-symbols-rounded text-sm mr-1 text-emerald-600">csv</span> CSV Export
            </x-button>
            <x-button variant="primary" href="{{ route('profit.export-pdf', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="!py-2 !px-3.5 !text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white">
                <span class="material-symbols-rounded text-sm mr-1">picture_as_pdf</span> PDF Report
            </x-button>
        </div>
    </div>
</x-card>

{{-- Overall 8 Financial Metrics Summary Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <x-stat-card label="1. Total Billed Amount" value="Rs {{ number_format($breakdown['total_billed'], 2) }}" icon="receipt_long" color="emerald" />
    <x-stat-card label="2. Dealer Paid Amount" value="Rs {{ number_format($breakdown['dealer_paid'], 2) }}" icon="payments" color="teal" />
    <x-stat-card label="3. Dealer Pending Balance" value="Rs {{ number_format($breakdown['dealer_pending'], 2) }}" icon="pending_actions" color="amber" />
    <x-stat-card label="4. Total Vendor Cost" value="Rs {{ number_format($breakdown['vendor_cost'], 2) }}" icon="inventory_2" color="purple" />
    <x-stat-card label="5. Vendor Paid Amount" value="Rs {{ number_format($breakdown['vendor_paid'], 2) }}" icon="outbound" color="blue" />
    <x-stat-card label="6. Vendor Pending Payable" value="Rs {{ number_format($breakdown['vendor_pending'], 2) }}" icon="account_balance" color="indigo" />
    <x-stat-card label="7. Total Expenses" value="Rs {{ number_format($breakdown['total_expenses'], 2) }}" icon="trending_up" color="rose" />
    <x-stat-card label="8. Net Profit / Loss" value="Rs {{ number_format($breakdown['net_profit'], 2) }}" icon="account_balance_wallet" color="{{ $breakdown['net_profit'] >= 0 ? 'emerald' : 'rose' }}" />
</div>

{{-- Capital & Owner Drawings Summary --}}
<div class="p-5 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/60 dark:border-zinc-700/60 mb-8">
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <span class="material-symbols-rounded text-indigo-600 dark:text-indigo-400 text-lg">savings</span>
            <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">Capital Movements & Owner Drawings</h3>
        </div>
        <a href="{{ route('billing.investments.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 flex items-center gap-1">
            Manage Capital Account <span class="material-symbols-rounded text-sm">arrow_forward</span>
        </a>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-500">Capital Invested (Period)</p>
                <p class="text-lg font-black font-jetbrains text-emerald-600 dark:text-emerald-400 mt-0.5">
                    Rs {{ number_format($breakdown['capital_invested'] ?? 0, 2) }}
                </p>
            </div>
            <span class="material-symbols-rounded text-emerald-500 text-2xl">add_circle</span>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-500">Owner Withdrawals (Period)</p>
                <p class="text-lg font-black font-jetbrains text-rose-600 dark:text-rose-400 mt-0.5">
                    Rs {{ number_format($breakdown['capital_withdrawn'] ?? 0, 2) }}
                </p>
            </div>
            <span class="material-symbols-rounded text-rose-500 text-2xl">money_off</span>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-500">Net Capital Movement</p>
                <p class="text-lg font-black font-jetbrains text-indigo-600 dark:text-indigo-400 mt-0.5">
                    Rs {{ number_format(($breakdown['capital_invested'] ?? 0) - ($breakdown['capital_withdrawn'] ?? 0), 2) }}
                </p>
            </div>
            <span class="material-symbols-rounded text-indigo-500 text-2xl">account_balance_wallet</span>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <x-card class="lg:col-span-2">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-zinc-200/50 dark:border-zinc-800/50">
            <div class="flex flex-wrap items-center gap-2.5">
                <h2 class="font-cabinet text-lg font-extrabold text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                    <span class="material-symbols-rounded text-emerald-500 text-[20px]">analytics</span>
                    Revenue vs Expenses & Vendor Pay Trend
                </h2>
                @if($breakdown['net_profit'] >= 0)
                    <span class="hidden sm:inline-flex items-center gap-1 text-xs font-black text-emerald-700 bg-emerald-100 dark:bg-emerald-950 dark:text-emerald-300 px-2.5 py-1 rounded-lg shadow-2xs">
                        <span class="material-symbols-rounded text-sm">trending_up</span> Profit: <x-currency :amount="$breakdown['net_profit']" />
                    </span>
                @endif
            </div>
            <div class="flex items-center gap-1.5 bg-zinc-100 dark:bg-zinc-800/80 p-1 rounded-xl shadow-inner">
                <button type="button" onclick="setChartHorizon(1)" id="btn-horizon-1" class="px-2.5 py-1 text-xs font-extrabold rounded-lg transition-all bg-emerald-600 text-white shadow-xs">This Week</button>
                <button type="button" onclick="setChartHorizon(4)" id="btn-horizon-4" class="px-2.5 py-1 text-xs font-bold rounded-lg text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 transition-all">4 Wks</button>
                <button type="button" onclick="setChartHorizon(12)" id="btn-horizon-12" class="px-2.5 py-1 text-xs font-bold rounded-lg text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 transition-all">12 Wks</button>
                <button type="button" onclick="setChartHorizon(26)" id="btn-horizon-26" class="px-2.5 py-1 text-xs font-bold rounded-lg text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 transition-all">26 Wks</button>
                <button type="button" onclick="setChartHorizon(52)" id="btn-horizon-52" class="px-2.5 py-1 text-xs font-bold rounded-lg text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 transition-all">1 Year</button>
            </div>
        </div>
        <div class="relative h-72">
            <canvas id="weeklyChart" class="w-full h-full"></canvas>
        </div>
    </x-card>

    <x-card class="lg:col-span-1" title="Financial Allocation Breakdown">
        <div class="relative h-72 flex items-center justify-center">
            <canvas id="collectionChart" class="w-full h-full"></canvas>
            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none pb-8">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Total Billed</span>
                <span class="font-cabinet font-black text-base sm:text-lg text-zinc-950 dark:text-zinc-50 mt-0.5"><x-currency :amount="$breakdown['total_billed']" /></span>
                @if($breakdown['total_billed'] > 0)
                    <span class="inline-flex items-center gap-1 text-[11px] font-extrabold text-emerald-600 dark:text-emerald-400 mt-1 px-2.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200/50 dark:border-emerald-800/50 shadow-2xs">
                        <span class="material-symbols-rounded text-xs">percent</span>
                        {{ number_format(($breakdown['net_profit'] / $breakdown['total_billed']) * 100, 1) }}% Margin
                    </span>
                @endif
            </div>
        </div>
    </x-card>
</div>

{{-- Weekly Performance Report Table --}}
<x-card title="Weekly Performance Report (Select Any Week to View Itemized Details)" class="mb-8">
    <x-data-table>
        <x-slot name="head">
            <tr>
                <th class="px-6 py-4 font-semibold tracking-wider">Week Period</th>
                <th class="px-6 py-4 font-semibold tracking-wider text-right">Total Bills / Inflow</th>
                <th class="px-6 py-4 font-semibold tracking-wider text-right">Total Vendor Pay</th>
                <th class="px-6 py-4 font-semibold tracking-wider text-right">Total Expenses</th>
                <th class="px-6 py-4 font-semibold tracking-wider text-right">Net Profit / Loss</th>
                <th class="px-6 py-4 font-semibold tracking-wider text-center">Action</th>
            </tr>
        </x-slot>
        @foreach($weeklyData as $row)
        <tr class="hover:bg-emerald-50/50 dark:hover:bg-zinc-800/40 transition-colors group cursor-pointer" onclick="window.location='{{ route('profit.weekly-detail', ['start_date' => $row['start_date'], 'end_date' => $row['end_date']]) }}'">
            <td class="px-6 py-4">
                <div class="font-black text-zinc-950 dark:text-zinc-50 text-base">{{ $row['week'] }}</div>
                <div class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $row['week_label'] ?? '' }}</div>
            </td>
            <td class="px-6 py-4 text-right font-mono text-emerald-600 dark:text-emerald-400 font-extrabold text-base"><x-currency :amount="$row['revenue']" /></td>
            <td class="px-6 py-4 text-right font-mono text-purple-600 dark:text-purple-400 font-extrabold text-base"><x-currency :amount="$row['purchase']" /></td>
            <td class="px-6 py-4 text-right font-mono text-rose-600 dark:text-rose-400 font-extrabold text-base"><x-currency :amount="$row['expenses']" /></td>
            <td class="px-6 py-4 text-right font-black text-base {{ $row['profit'] >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
                <x-currency :amount="$row['profit']" />
            </td>
            <td class="px-6 py-4 text-center" onclick="event.stopPropagation()">
                <a href="{{ route('profit.weekly-detail', ['start_date' => $row['start_date'], 'end_date' => $row['end_date']]) }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-extrabold rounded-xl bg-emerald-100 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 transition-all shadow-2xs">
                    View Details <span class="material-symbols-rounded text-sm">arrow_forward</span>
                </a>
            </td>
        </tr>
        @endforeach
    </x-data-table>
    @if($weeklyData->hasPages())
        <div class="p-4 border-t border-zinc-200/50 dark:border-zinc-800/50">
            {{ $weeklyData->links() }}
        </div>
    @endif
</x-card>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const allWeeksData = @json($allWeeks ?? []);
    let currentHorizon = 1;
    let weeklyChartInstance = null;

    function buildChartData(weeksCount) {
        const sliced = allWeeksData.slice(-weeksCount);
        return {
            labels: sliced.map(d => d.week_label || d.week),
            revenue: sliced.map(d => d.revenue),
            expenses: sliced.map(d => d.expenses + d.purchase),
            profit: sliced.map(d => d.profit)
        };
    }

    const ctxWeekly = document.getElementById('weeklyChart').getContext('2d');
    
    const revGrad = ctxWeekly.createLinearGradient(0, 0, 0, 300);
    revGrad.addColorStop(0, 'rgba(16, 185, 129, 0.9)');
    revGrad.addColorStop(1, 'rgba(16, 185, 129, 0.08)');

    const expGrad = ctxWeekly.createLinearGradient(0, 0, 0, 300);
    expGrad.addColorStop(0, 'rgba(244, 63, 94, 0.9)');
    expGrad.addColorStop(1, 'rgba(244, 63, 94, 0.08)');

    const initialData = buildChartData(currentHorizon);

    weeklyChartInstance = new Chart(ctxWeekly, {
        type: 'bar',
        data: {
            labels: initialData.labels,
            datasets: [
                {
                    label: 'Revenue / Bills',
                    data: initialData.revenue,
                    backgroundColor: revGrad,
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 1.5,
                    borderRadius: { topLeft: 8, topRight: 8 },
                    borderSkipped: 'bottom',
                    barPercentage: 0.55,
                    maxBarThickness: 44,
                },
                {
                    label: 'Vendor Pay & Expenses',
                    data: initialData.expenses,
                    backgroundColor: expGrad,
                    borderColor: 'rgba(244, 63, 94, 1)',
                    borderWidth: 1.5,
                    borderRadius: { topLeft: 8, topRight: 8 },
                    borderSkipped: 'bottom',
                    barPercentage: 0.55,
                    maxBarThickness: 44,
                },
                {
                    label: 'Net Profit',
                    data: initialData.profit,
                    type: 'line',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    backgroundColor: 'rgba(59, 130, 246, 0.06)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: 'rgba(59, 130, 246, 1)',
                    pointBorderWidth: 2.5,
                    pointRadius: currentHorizon > 12 ? 0 : 5,
                    pointHoverRadius: 7
                }
            ]
        },
        plugins: [{
            id: 'topValuesPlugin',
            afterDatasetsDraw(chart) {
                if (currentHorizon > 12) return;
                const { ctx } = chart;
                chart.data.datasets.forEach((dataset, datasetIndex) => {
                    const meta = chart.getDatasetMeta(datasetIndex);
                    meta.data.forEach((element, index) => {
                        const val = dataset.data[index];
                        if (val === undefined || val === null || val === 0) return;
                        
                        let text = '';
                        if (val >= 10000000) text = 'Rs ' + (val / 10000000).toFixed(1) + 'Cr';
                        else if (val >= 100000) text = 'Rs ' + (val / 100000).toFixed(2) + 'L';
                        else if (val >= 1000) text = 'Rs ' + (val / 1000).toFixed(1) + 'k';
                        else text = 'Rs ' + val;

                        ctx.save();
                        ctx.font = 'bold 11px Outfit, sans-serif';
                        ctx.fillStyle = datasetIndex === 0 ? '#047857' : (datasetIndex === 1 ? '#be123c' : '#1d4ed8');
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'bottom';
                        ctx.fillText(text, element.x, element.y - 5);
                        ctx.restore();
                    });
                });
            }
        }],
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { 
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 18, font: { family: "'Outfit', sans-serif", size: 12, weight: '600' } }
                },
                tooltip: {
                    backgroundColor: 'rgba(24, 24, 27, 0.95)',
                    titleFont: { family: "'Cabinet Grotesk', sans-serif", size: 14 },
                    bodyFont: { family: "'Outfit', sans-serif", size: 13 },
                    padding: 12,
                    cornerRadius: 12,
                    displayColors: true,
                    usePointStyle: true,
                    callbacks: {
                        label: function(ctx) {
                            const val = ctx.raw || 0;
                            return ' ' + ctx.dataset.label + ': Rs ' + val.toLocaleString('en-IN', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                x: { 
                    grid: { display: false }, 
                    ticks: { 
                        font: { family: "'Outfit', sans-serif", size: 11, weight: '600' },
                        maxRotation: 0,
                        minRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 7
                    } 
                },
                y: { 
                    beginAtZero: true, 
                    grace: '10%',
                    grid: { color: 'rgba(161, 161, 170, 0.12)', borderDash: [4, 4] }, 
                    ticks: { 
                        font: { family: "'Outfit', sans-serif", size: 11 },
                        callback: function(val) {
                            if (val >= 10000000) return 'Rs ' + (val / 10000000).toFixed(1) + 'Cr';
                            if (val >= 100000) return 'Rs ' + (val / 100000).toFixed(1) + 'L';
                            if (val >= 1000) return 'Rs ' + (val / 1000).toFixed(0) + 'k';
                            return 'Rs ' + val;
                        }
                    } 
                }
            }
        }
    });

    window.setChartHorizon = function(weeksCount) {
        currentHorizon = weeksCount;
        [1, 4, 12, 26, 52].forEach(w => {
            const btn = document.getElementById('btn-horizon-' + w);
            if (btn) {
                if (w === weeksCount) {
                    btn.className = "px-2.5 py-1 text-xs font-extrabold rounded-lg transition-all bg-emerald-600 text-white shadow-xs";
                } else {
                    btn.className = "px-2.5 py-1 text-xs font-bold rounded-lg text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 transition-all";
                }
            }
        });

        const newData = buildChartData(weeksCount);
        weeklyChartInstance.data.labels = newData.labels;
        weeklyChartInstance.data.datasets[0].data = newData.revenue;
        weeklyChartInstance.data.datasets[1].data = newData.expenses;
        weeklyChartInstance.data.datasets[2].data = newData.profit;
        
        // Hide point dots when showing long horizons (> 12 weeks) to eliminate blue circle clutter
        weeklyChartInstance.data.datasets[2].pointRadius = weeksCount > 12 ? 0 : 5;
        weeklyChartInstance.data.datasets[2].pointHoverRadius = 7;

        weeklyChartInstance.update();
    };

    // Financial Allocation Doughnut Chart
    const breakdown = @json($breakdown);
    const vCost    = parseFloat(breakdown.vendor_cost || 0);
    const exps     = parseFloat(breakdown.total_expenses || 0);
    const netProf  = Math.max(0, parseFloat(breakdown.net_profit || 0));
    const dPending = parseFloat(breakdown.dealer_pending || 0);

    const hasData = (vCost + exps + netProf + dPending) > 0;

    const ctxColl = document.getElementById('collectionChart').getContext('2d');

    new Chart(ctxColl, {
        type: 'doughnut',
        data: {
            labels: hasData ? ['Vendor Cost', 'Expenses', 'Net Profit', 'Dealer Pending'] : ['No Data'],
            datasets: [{
                data: hasData ? [vCost, exps, netProf, dPending] : [1],
                backgroundColor: hasData 
                    ? ['#a855f7', '#f43f5e', '#10b981', '#f59e0b']
                    : ['#e5e7eb'],
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'bottom',
                    display: hasData,
                    labels: { usePointStyle: true, padding: 14, font: { family: "'Outfit', sans-serif", size: 11, weight: '600' } }
                },
                tooltip: {
                    enabled: hasData,
                    backgroundColor: 'rgba(24, 24, 27, 0.95)',
                    titleFont: { family: "'Cabinet Grotesk', sans-serif", size: 14 },
                    bodyFont: { family: "'Outfit', sans-serif", size: 12 },
                    padding: 12,
                    cornerRadius: 12,
                    callbacks: {
                        label: function(ctx) {
                            const val = ctx.raw || 0;
                            return ' ' + ctx.label + ': Rs ' + val.toLocaleString('en-IN', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            cutout: '72%',
            layout: { padding: 10 }
        }
    });
});
</script>
@endpush
