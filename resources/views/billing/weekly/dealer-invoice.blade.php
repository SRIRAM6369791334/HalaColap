@extends('layouts.app')
@section('title', 'Dealer Invoice')

@section('content')
<div class="animate-fade-in">
    <x-page-header title="Dealer Invoice" subtitle="Generate weekly invoices from day-load entries">
        <x-slot:actions>
            <x-button variant="outline" href="{{ route('billing.weekly.index') }}" icon="arrow_back">
                Back to Billing
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filter Form --}}
    <x-card class="mb-8">
        <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50">
            <h2 class="font-cabinet text-lg font-bold text-zinc-900 dark:text-zinc-50">Select Dealer & Period</h2>
        </div>
        <form method="GET" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Dealer</label>
                    <select name="dealer_id" required class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                        <option value="">Select dealer...</option>
                        @foreach($dealers as $d)
                            <option value="{{ $d->id }}" {{ ($dealer?->id ?? '') == $d->id ? 'selected' : '' }}>
                                {{ $d->firm_name }}{{ $d->pending_amount > 0 ? ' (Bal: Rs ' . number_format($d->pending_amount, 0) . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Period Start</label>
                    <input type="date" name="period_start" value="{{ $periodStart }}" required
                        class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Period End</label>
                    <input type="date" name="period_end" value="{{ $periodEnd }}" required
                        class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                </div>
                <div class="flex items-end gap-2">
                    <x-button type="submit" variant="primary" icon="search" class="flex-1">Generate</x-button>
                </div>
            </div>

            {{-- Quick Presets --}}
            <div class="flex flex-wrap gap-2">
                @php
                    $presets = [
                        'this-week'    => ['label' => 'This Week (Mon-Sat)', 'start' => now()->startOfWeek()->format('Y-m-d'), 'end' => now()->endOfWeek()->subDay()->format('Y-m-d')],
                        'last-week'    => ['label' => 'Last Week', 'start' => now()->subWeek()->startOfWeek()->format('Y-m-d'), 'end' => now()->subWeek()->endOfWeek()->subDay()->format('Y-m-d')],
                        'this-month'   => ['label' => 'This Month', 'start' => now()->startOfMonth()->format('Y-m-d'), 'end' => now()->endOfMonth()->format('Y-m-d')],
                        'last-month'   => ['label' => 'Last Month', 'start' => now()->subMonth()->startOfMonth()->format('Y-m-d'), 'end' => now()->subMonth()->endOfMonth()->format('Y-m-d')],
                        'last-7-days'  => ['label' => 'Last 7 Days', 'start' => now()->subDays(6)->format('Y-m-d'), 'end' => now()->format('Y-m-d')],
                        'last-14-days' => ['label' => 'Last 14 Days', 'start' => now()->subDays(13)->format('Y-m-d'), 'end' => now()->format('Y-m-d')],
                    ];
                @endphp
                @foreach($presets as $key => $p)
                    <a href="{{ route('billing.weekly.dealer-invoice', ['dealer_id' => $dealer?->id, 'period_start' => $p['start'], 'period_end' => $p['end'], 'preset' => $key]) }}"
                       class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium border {{ ($periodStart === $p['start'] && $periodEnd === $p['end']) ? 'bg-emerald-50 border-emerald-300 text-emerald-700 dark:bg-emerald-900/30 dark:border-emerald-600 dark:text-emerald-400' : 'bg-zinc-50 border-zinc-200 text-zinc-600 hover:bg-zinc-100 dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-700' }} transition-colors">
                        {{ $p['label'] }}
                    </a>
                @endforeach
            </div>
        </form>
    </x-card>

    @if($dealer)
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <x-stat-card
                label="Current Bill Total"
                value="Rs {{ number_format($currentBillTotal, 2) }}"
                icon="receipt_long"
                color="emerald" />
            <x-stat-card
                label="Previous Balance (B/F)"
                value="Rs {{ number_format($previousBalance, 2) }}"
                icon="history"
                color="amber" />
            <div class="rounded-2xl bg-gradient-to-br {{ $grandTotal > 0 ? 'from-rose-500 to-rose-600 dark:from-rose-600 dark:to-rose-800' : 'from-emerald-500 to-emerald-600 dark:from-emerald-600 dark:to-emerald-800' }} p-6 shadow-sm text-white flex items-center justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                <div>
                    <p class="font-outfit text-sm font-medium {{ $grandTotal > 0 ? 'text-rose-100' : 'text-emerald-100' }}">Grand Total</p>
                    <p class="font-jetbrains mt-2 text-3xl font-black tracking-tight">Rs {{ number_format($grandTotal, 2) }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                    <span class="material-symbols-rounded text-2xl">payments</span>
                </div>
            </div>
        </div>

        {{-- Invoice Table --}}
        <x-card>
            <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50">
                <div class="flex items-center gap-3">
                    <x-avatar name="{{ $dealer->firm_name }}" size="md" />
                    <div>
                        <h2 class="font-cabinet text-lg font-bold text-zinc-900 dark:text-zinc-50">{{ $dealer->firm_name }}</h2>
                        <p class="text-xs text-zinc-500">{{ \Carbon\Carbon::parse($periodStart)->format('d M Y') }} — {{ \Carbon\Carbon::parse($periodEnd)->format('d M Y') }}</p>
                    </div>
                    @if($entries->count() > 0)
                        <span class="ml-auto inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                            {{ $entries->count() }} entries
                        </span>
                    @endif
                </div>
            </div>

            @if($entries->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 border-b border-zinc-200/50 dark:border-zinc-800/50">
                                <th class="px-6 py-3 text-left">S.No</th>
                                <th class="px-6 py-3 text-left">Date</th>
                                <th class="px-6 py-3 text-left">Vendor</th>
                                <th class="px-6 py-3 text-right">Kg</th>
                                <th class="px-6 py-3 text-right">Rate (Rs.)</th>
                                <th class="px-6 py-3 text-right">Total (Rs.)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($entries as $i => $entry)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                                    <td class="px-6 py-3 font-jetbrains text-xs">
                                        @if(isset($entry['sort_order']) && $entry['sort_order'])
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-black bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300 dark:border-amber-700">#{{ $entry['sort_order'] }}</span>
                                        @else
                                            <span class="text-zinc-400">{{ $i + 1 }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 font-jetbrains text-xs">{{ $entry['date'] }}</td>
                                    <td class="px-6 py-3 text-xs text-zinc-600 dark:text-zinc-400">{{ $entry['vendor'] }}</td>
                                    <td class="px-6 py-3 text-right font-jetbrains text-xs">{{ number_format($entry['kg'], 3) }}</td>
                                    <td class="px-6 py-3 text-right font-jetbrains text-xs">{{ number_format($entry['rate'], 0) }}</td>
                                    <td class="px-6 py-3 text-right font-jetbrains font-bold text-xs text-zinc-900 dark:text-zinc-100">{{ number_format($entry['total'], 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t-2 border-zinc-200 dark:border-zinc-700">
                            <tr class="font-bold">
                                <td class="px-6 py-3 text-zinc-500 text-xs" colspan="3">Current Bill Total</td>
                                <td class="px-6 py-3 text-right font-jetbrains text-xs">{{ number_format($entries->sum('kg'), 3) }}</td>
                                <td></td>
                                <td class="px-6 py-3 text-right font-jetbrains text-sm text-emerald-600 dark:text-emerald-400">Rs {{ number_format($currentBillTotal, 0) }}</td>
                            </tr>
                            <tr class="font-bold">
                                <td class="px-6 py-3 text-zinc-500 text-xs" colspan="5">Previous Balance (Balance B/F)</td>
                                <td class="px-6 py-3 text-right font-jetbrains text-sm text-amber-600 dark:text-amber-400">Rs {{ number_format($previousBalance, 0) }}</td>
                            </tr>
                            <tr class="font-black text-lg border-t-2 border-zinc-300 dark:border-zinc-600">
                                <td class="px-6 py-4 text-zinc-900 dark:text-zinc-50 text-xs" colspan="5">Grand Total</td>
                                <td class="px-6 py-4 text-right font-jetbrains text-emerald-600 dark:text-emerald-400">Rs {{ number_format($grandTotal, 0) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Generate Invoice Button --}}
                <div class="p-4 border-t border-zinc-200/50 dark:border-zinc-800/50">
                    <form action="{{ route('billing.weekly.generate-invoice') }}" method="POST" onsubmit="return confirm('Generate invoice for {{ $dealer->firm_name }}?')">
                        @csrf
                        <input type="hidden" name="dealer_id" value="{{ $dealer->id }}">
                        <input type="hidden" name="period_start" value="{{ $periodStart }}">
                        <input type="hidden" name="period_end" value="{{ $periodEnd }}">
                        <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg shadow-indigo-500/20 transition-all active:scale-95 flex items-center justify-center gap-2">
                            <span class="material-symbols-rounded">receipt_long</span>
                            Generate Invoice (Rs {{ number_format($grandTotal, 0) }})
                        </button>
                    </form>
                </div>
            @else
                <div class="p-8 text-center">
                    <x-empty-state
                        icon="receipt_long"
                        title="No day-load entries found"
                        description="No active day-load entries found for this dealer in the selected period." />
                </div>
            @endif
        </x-card>
    @else
        <x-card>
            <div class="p-8 text-center">
                <x-empty-state
                    icon="person_search"
                    title="Select a dealer"
                    description="Choose a dealer and date range above to generate their invoice from day-load entries." />
            </div>
        </x-card>
    @endif
</div>
@endsection
