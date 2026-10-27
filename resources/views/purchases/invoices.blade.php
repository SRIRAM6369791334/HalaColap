@extends('layouts.app')
@section('title', 'Invoices')

@section('content')
<div class="animate-fade-in">
    <x-page-header title="Invoices" subtitle="View day-load entries and purchases day by day — click a date to see all records">
        <x-slot:actions>
            <x-button variant="outline" href="{{ route('purchases.export') }}" icon="download">
                Export
            </x-button>
            <x-button variant="primary" href="{{ route('purchases.entry') }}" icon="add">
                New Purchase
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Overall Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-stat-card
            label="Total Day-Loads"
            value="{{ number_format($totalDayLoads) }}"
            icon="local_shipping"
            color="blue" />
        <x-stat-card
            label="Birds Loaded"
            value="{{ number_format($totalBirdsLoaded) }} boxes"
            icon="inventory_2"
            color="indigo" />
        <x-stat-card
            label="Total Purchases"
            value="{{ number_format($totalPurchases) }}"
            icon="receipt_long"
            color="emerald" />
        <x-stat-card
            label="Total Expenditure"
            value="Rs {{ number_format($totalExpenditure, 2) }}"
            icon="payments"
            color="amber" />
    </div>

    {{-- Date List --}}
    <x-card>
        <div class="flex flex-col sm:flex-row gap-4 mb-6 justify-between items-center">
            <h2 class="font-cabinet text-lg font-bold text-zinc-900 dark:text-zinc-50">All Days</h2>
        </div>

        {{-- Filter Form --}}
        <div class="mb-6 p-4 bg-zinc-50/50 dark:bg-zinc-800/30 rounded-2xl border border-zinc-200/50 dark:border-zinc-700/50"
             x-data="{
                setDates(period) {
                    const now = new Date();
                    const to = now.toISOString().split('T')[0];
                    let from;
                    if (period === 'today') {
                        from = to;
                    } else if (period === '7d') {
                        from = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                    } else if (period === '30d') {
                        from = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                    } else if (period === 'month') {
                        from = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
                    }
                    $refs.dateFrom.value = from;
                    $refs.dateTo.value = to;
                    $refs.filterForm.submit();
                }
             }">
            <form method="GET" action="{{ route('purchases.invoices') }}" x-ref="filterForm" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">From</label>
                    <input type="date" name="date_from" x-ref="dateFrom" value="{{ $dateFrom }}" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">To</label>
                    <input type="date" name="date_to" x-ref="dateTo" value="{{ $dateTo }}" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm">
                </div>
                <x-button type="submit" variant="primary" icon="filter_alt" size="sm">Filter</x-button>
                @if($dateFrom || $dateTo || $search)
                    <a href="{{ route('purchases.invoices') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-rose-50 hover:border-rose-200 hover:text-rose-600 dark:hover:bg-rose-900/20 dark:hover:border-rose-800 dark:hover:text-rose-400 transition-all">
                        <span class="material-symbols-rounded text-[16px]">close</span>
                        Clear Filters
                    </a>
                @endif
                <div class="flex gap-1.5 ml-auto">
                    <button type="button" @click="setDates('today')" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 dark:hover:bg-emerald-900/20 dark:hover:border-emerald-800 dark:hover:text-emerald-400 transition-all">Today</button>
                    <button type="button" @click="setDates('7d')" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 dark:hover:bg-emerald-900/20 dark:hover:border-emerald-800 dark:hover:text-emerald-400 transition-all">7 Days</button>
                    <button type="button" @click="setDates('30d')" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 dark:hover:bg-emerald-900/20 dark:hover:border-emerald-800 dark:hover:text-emerald-400 transition-all">30 Days</button>
                    <button type="button" @click="setDates('month')" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 dark:hover:bg-emerald-900/20 dark:hover:border-emerald-800 dark:hover:text-emerald-400 transition-all">This Month</button>
                </div>
            </form>
        </div>

        <x-data-table :headers="['Date', 'Day', 'Day-Load', 'Purchases', 'Total Amount', 'Actions']">
            @forelse($dateGroups as $group)
                @php
                    $dateObj = \Carbon\Carbon::parse($group->date);
                    $isToday = $dateObj->isToday();
                    $isYesterday = $dateObj->isYesterday();
                    $hasDayLoad = $group->dayload_count > 0;
                    $hasPurchases = $group->purchase_count > 0;
                @endphp
                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors group">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-10 h-10 rounded-xl {{ $isToday ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-zinc-100 dark:bg-zinc-800' }} flex items-center justify-center">
                                <span class="material-symbols-rounded text-lg {{ $isToday ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-500' }}">calendar_today</span>
                            </div>
                            <div>
                                <div class="font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ $dateObj->format('d M Y') }}
                                    @if($isToday)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Today</span>
                                    @elseif($isYesterday)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">Yesterday</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400 font-medium text-sm">
                        {{ $dateObj->format('l') }}
                    </td>
                    <td class="px-6 py-4">
                        @if($hasDayLoad)
                            <div class="flex flex-col gap-0.5">
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 dark:text-blue-400">
                                    <span class="material-symbols-rounded text-sm">local_shipping</span>
                                    {{ $group->dayload_count }} batch{{ $group->dayload_count > 1 ? 'es' : '' }}
                                </span>
                                <span class="text-[11px] text-zinc-500 font-jetbrains">{{ number_format($group->total_boxes) }} boxes · {{ number_format($group->total_bird_weight, 1) }} kg</span>
                            </div>
                        @else
                            <span class="text-zinc-400 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($hasPurchases)
                            <div class="flex flex-col gap-0.5">
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                    <span class="material-symbols-rounded text-sm">receipt_long</span>
                                    {{ $group->purchase_count }} invoice{{ $group->purchase_count > 1 ? 's' : '' }}
                                </span>
                                <span class="text-[11px] text-zinc-500 font-jetbrains">Rs {{ number_format((float) $group->total_amount, 2) }}</span>
                            </div>
                        @else
                            <span class="text-zinc-400 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 font-jetbrains font-bold text-zinc-900 dark:text-zinc-100">
                        @php
                            $dayTotal = (float) $group->total_amount;
                        @endphp
                        @if($dayTotal > 0)
                            Rs {{ number_format($dayTotal, 2) }}
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('purchases.invoices', ['date' => $group->date]) }}"
                           class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">
                            <span class="material-symbols-rounded text-sm">visibility</span>
                            View All
                        </a>
                    </td>
                </tr>
            @empty
                <x-slot:empty>
                    <x-empty-state
                        icon="receipt_long"
                        title="No records found"
                        description="Start recording day-load entries or purchases to see them grouped by date here." />
                </x-slot:empty>
            @endforelse

            @if($dateGroups->hasPages())
                <x-slot:pagination>
                    {{ $dateGroups->withQueryString()->links() }}
                </x-slot:pagination>
            @endif
        </x-data-table>
    </x-card>
</div>
@endsection
