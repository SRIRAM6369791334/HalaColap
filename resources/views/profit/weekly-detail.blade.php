@extends('layouts.app')
@section('title', 'Weekly Financial Detail Report')

@section('content')
<x-page-header 
    title="Weekly Financial Detail Report" 
    subtitle="Itemized breakdown for period: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}">
    <div class="flex gap-3">
        <x-button variant="secondary" href="{{ route('profit.index') }}">
            <span class="material-symbols-rounded text-sm">arrow_back</span> Back to Profit Dashboard
        </x-button>
    </div>
</x-page-header>

{{-- Weekly Profit Summary --}}
<h3 class="font-cabinet font-bold text-zinc-800 dark:text-zinc-100 mb-3 text-lg">Profit & Loss Summary (Accrual Basis)</h3>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <x-stat-card label="Total Billed (Inflow)" value="Rs {{ number_format($summary['total_billed'], 2) }}" icon="receipt_long" color="emerald" />
    <x-stat-card label="Total Vendor Cost" value="Rs {{ number_format($summary['vendor_cost'], 2) }}" icon="inventory_2" color="purple" />
    <x-stat-card label="Total Expenses" value="Rs {{ number_format($summary['total_expenses'], 2) }}" icon="trending_up" color="rose" />
    <x-stat-card label="Weekly Net Profit" value="Rs {{ number_format($summary['net_profit'], 2) }}" icon="account_balance_wallet" color="{{ $summary['net_profit'] >= 0 ? 'emerald' : 'rose' }}" />
</div>

{{-- Weekly Cash Flow Summary --}}
<h3 class="font-cabinet font-bold text-zinc-800 dark:text-zinc-100 mb-3 text-lg mt-4">Cash Flow Summary (Actual Cash)</h3>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
    <x-stat-card label="Total Dealer Collections" value="Rs {{ number_format($summary['dealer_paid'], 2) }}" icon="payments" color="emerald" />
    <x-stat-card label="Total Vendor Payouts" value="Rs {{ number_format($summary['vendor_paid'], 2) }}" icon="account_balance" color="rose" />
    <x-stat-card label="Weekly Net Cash Flow" value="Rs {{ number_format($summary['cash_profit'], 2) }}" icon="savings" color="{{ $summary['cash_profit'] >= 0 ? 'emerald' : 'rose' }}" />
</div>

{{-- 1. Day-Load Batches & Entries --}}
<x-card title="1. Day-Load Sales & Purchases ({{ $dayLoadBatches->count() }} Batches)" class="mb-8">
    @if($dayLoadBatches->isEmpty())
        <x-empty-state icon="local_shipping" title="No Day-Load Batches" description="No day-load batches recorded during this week period." />
    @else
        <div class="space-y-6 p-4">
            @foreach($dayLoadBatches as $batch)
                <div class="p-5 rounded-2xl bg-zinc-50 dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 shadow-xs">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-zinc-200 dark:border-zinc-800">
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 rounded-lg bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-extrabold text-sm">
                                Batch #{{ $batch->id }}
                            </span>
                            <span class="font-bold text-zinc-900 dark:text-white text-base">
                                {{ $batch->billing_date->format('d M Y (l)') }}
                            </span>
                        </div>
                        <div class="text-sm font-semibold text-zinc-500">
                            Total Bird Wt: <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($batch->total_bird_weight, 2) }} kg</span>
                            @if($batch->total_loss_weight > 0)
                                · Loss Wt: <span class="font-bold text-rose-600 dark:text-rose-400">{{ number_format($batch->total_loss_weight, 2) }} kg (Rs {{ number_format($batch->weight_loss_amount, 2) }})</span>
                            @endif
                        </div>
                    </div>

                    <x-data-table>
                        <x-slot name="head">
                            <tr>
                                <th class="px-4 py-2 text-xs font-bold uppercase tracking-wider">Dealer</th>
                                <th class="px-4 py-2 text-xs font-bold uppercase tracking-wider">Vendor</th>
                                <th class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-right">Farm Wt</th>
                                <th class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-right">Bird Wt</th>
                                <th class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-right">Dealer Rate</th>
                                <th class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-right">Dealer Billed</th>
                                <th class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-right">Vendor Rate</th>
                                <th class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-right">Vendor Cost</th>
                            </tr>
                        </x-slot>
                        @foreach($batch->entries as $entry)
                            @php
                                $dRate = (float)($entry->customer_rate ?: $entry->rate);
                                $vRate = (float)($entry->billing_rate ?: ($entry->vendor_rate ?: $entry->paper_rate));
                                $dBilled = (float)$entry->bird_weight * $dRate;
                                $vCost = (float)$entry->farm_weight * $vRate;
                            @endphp
                            <tr class="hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40">
                                <td class="px-4 py-2.5 font-bold text-zinc-900 dark:text-zinc-100">{{ $entry->dealer?->firm_name ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">{{ $entry->vendor?->firm_name ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-right font-mono text-zinc-700 dark:text-zinc-300">{{ number_format((float)$entry->farm_weight, 2) }} kg</td>
                                <td class="px-4 py-2.5 text-right font-mono text-zinc-900 dark:text-zinc-100 font-bold">{{ number_format((float)$entry->bird_weight, 2) }} kg</td>
                                <td class="px-4 py-2.5 text-right font-mono text-emerald-600">Rs {{ number_format($dRate, 2) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono text-emerald-700 dark:text-emerald-300 font-extrabold">Rs {{ number_format($dBilled, 2) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono text-purple-600">Rs {{ number_format($vRate, 2) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono text-purple-700 dark:text-purple-300 font-extrabold">Rs {{ number_format($vCost, 2) }}</td>
                            </tr>
                        @endforeach
                    </x-data-table>
                </div>
            @endforeach
        </div>
    @endif
</x-card>

{{-- 2. Dealer Payments Received & Vendor Payouts --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <x-card title="2. Dealer Collections (Rs {{ number_format($dealerPayments->sum('amount'), 2) }})">
        @if($dealerPayments->isEmpty())
            <x-empty-state icon="payments" title="No Dealer Payments" description="No dealer payments received during this week." />
        @else
            <x-data-table>
                <x-slot name="head">
                    <tr>
                        <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider">Dealer</th>
                        <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider">Mode</th>
                        <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-right">Amount</th>
                    </tr>
                </x-slot>
                @foreach($dealerPayments as $p)
                    <tr>
                        <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $p->date->format('d M Y') }}</td>
                        <td class="px-4 py-3 font-bold text-zinc-900 dark:text-zinc-100">{{ $p->dealer?->firm_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs font-bold text-emerald-600">{{ $p->payment_mode }}</td>
                        <td class="px-4 py-3 text-right font-mono font-extrabold text-emerald-600">Rs {{ number_format((float)$p->amount, 2) }}</td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    <x-card title="3. Vendor Payouts (Rs {{ number_format($vendorPayments->sum('amount'), 2) }})">
        @if($vendorPayments->isEmpty())
            <x-empty-state icon="outbound" title="No Vendor Payouts" description="No vendor payments made during this week." />
        @else
            <x-data-table>
                <x-slot name="head">
                    <tr>
                        <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider">Vendor</th>
                        <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider">Mode</th>
                        <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-right">Amount</th>
                    </tr>
                </x-slot>
                @foreach($vendorPayments as $vp)
                    <tr>
                        <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $vp->date->format('d M Y') }}</td>
                        <td class="px-4 py-3 font-bold text-zinc-900 dark:text-zinc-100">{{ $vp->vendor?->firm_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs font-bold text-purple-600">{{ $vp->payment_mode }}</td>
                        <td class="px-4 py-3 text-right font-mono font-extrabold text-purple-600">Rs {{ number_format((float)$vp->amount, 2) }}</td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>
</div>

{{-- 3. Operational Expenses & Weight Loss Expenses --}}
<x-card title="4. Operational & Weight Loss Expenses (Rs {{ number_format($expenses->sum('amount'), 2) }})" class="mb-8">
    @if($expenses->isEmpty())
        <x-empty-state icon="trending_up" title="No Expenses" description="No expenses recorded during this week period." />
    @else
        <x-data-table>
            <x-slot name="head">
                <tr>
                    <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider">Method</th>
                    <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider text-right">Amount</th>
                </tr>
            </x-slot>
            @foreach($expenses as $exp)
                <tr>
                    <td class="px-6 py-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ \Carbon\Carbon::parse($exp->date)->format('d M Y') }}</td>
                    <td class="px-6 py-3">
                        <x-badge variant="{{ $exp->category === 'Weight Loss' ? 'rose' : 'zinc' }}">
                            {{ $exp->category }}
                        </x-badge>
                    </td>
                    <td class="px-6 py-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $exp->description }}</td>
                    <td class="px-6 py-3 text-xs font-bold text-zinc-500">{{ $exp->payment_method }}</td>
                    <td class="px-6 py-3 text-right font-mono font-extrabold text-rose-600">Rs {{ number_format((float)$exp->amount, 2) }}</td>
                </tr>
            @endforeach
        </x-data-table>
    @endif
</x-card>

{{-- 5. Paid EMIs --}}
<x-card title="5. Paid EMIs (Rs {{ number_format($emis->sum('amount'), 2) }})" class="mb-8">
    @if($emis->isEmpty())
        <x-empty-state icon="account_balance" title="No EMIs Paid" description="No EMIs were recorded as paid during this week period." />
    @else
        <x-data-table>
            <x-slot name="head">
                <tr>
                    <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider">Due Date</th>
                    <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider">Loan Name</th>
                    <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider text-right">Amount</th>
                </tr>
            </x-slot>
            @foreach($emis as $emi)
                <tr>
                    <td class="px-6 py-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ \Carbon\Carbon::parse($emi->due_date)->format('d M Y') }}</td>
                    <td class="px-6 py-3">
                        <x-badge variant="emerald">
                            {{ $emi->emi_type }}
                        </x-badge>
                    </td>
                    <td class="px-6 py-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $emi->loan_name }}</td>
                    <td class="px-6 py-3 text-right font-mono font-extrabold text-rose-600">Rs {{ number_format((float)$emi->amount, 2) }}</td>
                </tr>
            @endforeach
        </x-data-table>
    @endif
</x-card>
@endsection
