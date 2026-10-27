@extends('layouts.app')
@section('title', 'Cash & Bank Ledger')

@section('content')
<div class="animate-fade-in space-y-6">
    <x-page-header title="Cash & Bank Ledger" subtitle="Daily cash-in-hand and bank transfer running balances">
        <x-slot:actions>
            <x-button variant="outline" href="{{ route('billing.investments.index') }}" icon="savings">
                Capital & Investments
            </x-button>
            <x-button variant="outline" href="{{ route('masters.vendors.index') }}" icon="local_shipping">
                Vendor Advances
            </x-button>
            <x-button variant="outline" href="{{ route('payments.dealers.create') }}" icon="payments" target="_blank">
                Dealer Payment
            </x-button>
            <x-button variant="outline" href="{{ route('expenses.create') }}" icon="money_off" target="_blank">
                Expense
            </x-button>
            <x-button variant="outline" icon="refresh" onclick="location.reload()">
                Refresh
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Consolidated Business Liquidity & Capital Ribbon --}}
    <div class="p-4 rounded-2xl bg-gradient-to-r from-indigo-50 via-emerald-50/50 to-amber-50/50 dark:from-indigo-950/40 dark:via-emerald-950/20 dark:to-amber-950/20 border border-indigo-200/60 dark:border-indigo-800/40 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-600/20">
                <span class="material-symbols-rounded text-xl">account_balance_wallet</span>
            </div>
            <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-indigo-700 dark:text-indigo-300 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Consolidated Business Liquidity
                </span>
                <p class="text-xs text-zinc-500">Cash in Hand + Bank Accounts + Capital Investment Reserve</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4 sm:gap-6">
            {{-- Cash in Hand --}}
            <div class="text-right">
                <span class="block text-[10px] font-bold text-zinc-500 uppercase">💵 Cash in Hand</span>
                <span class="font-jetbrains font-bold text-sm text-emerald-600 dark:text-emerald-400">
                    Rs {{ number_format($latestCashBalance, 2) }}
                </span>
            </div>

            {{-- Bank Balance --}}
            <div class="text-right">
                <span class="block text-[10px] font-bold text-zinc-500 uppercase">🏦 Bank Accounts</span>
                <span class="font-jetbrains font-bold text-sm text-blue-600 dark:text-blue-400">
                    Rs {{ number_format($latestBankBalance, 2) }}
                </span>
            </div>

            {{-- Investment Pool --}}
            <div class="text-right">
                <a href="{{ route('billing.investments.index') }}" class="group block">
                    <span class="block text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase group-hover:underline flex items-center justify-end gap-0.5">
                        🏛️ Investment Pool
                        <span class="material-symbols-rounded text-[12px]">arrow_forward</span>
                    </span>
                    <span class="font-jetbrains font-bold text-sm text-indigo-700 dark:text-indigo-300">
                        Rs {{ number_format($currentInvestmentBalance, 2) }}
                    </span>
                </a>
            </div>

            {{-- Vendor Advances --}}
            <div class="text-right">
                <a href="{{ route('masters.vendors.index') }}" class="group block">
                    <span class="block text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase group-hover:underline flex items-center justify-end gap-0.5">
                        🚚 Active Advances
                        <span class="material-symbols-rounded text-[12px]">arrow_forward</span>
                    </span>
                    <span class="font-jetbrains font-bold text-sm text-amber-700 dark:text-amber-300">
                        Rs {{ number_format($totalActiveVendorAdvances, 2) }}
                    </span>
                </a>
            </div>

            {{-- Total Consolidated Liquidity --}}
            <div class="pl-4 border-l border-zinc-300 dark:border-zinc-700 text-right">
                <span class="block text-[10px] font-black text-indigo-900 dark:text-indigo-100 uppercase">💎 Total Liquidity</span>
                <span class="font-jetbrains font-black text-base text-indigo-600 dark:text-indigo-400">
                    Rs {{ number_format($totalConsolidatedLiquidity, 2) }}
                </span>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 sm:gap-6">
        <x-stat-card label="Total Cash Income" value="Rs {{ number_format($totalCashIncome, 0) }}" icon="payments" color="emerald" />
        <x-stat-card label="Total Bank Income" value="Rs {{ number_format($totalBankIncome, 0) }}" icon="account_balance" color="blue" />
        <x-stat-card label="Total Cash Expense" value="Rs {{ number_format($totalCashExpense, 0) }}" icon="money_off" color="rose" />
        <x-stat-card label="Total Bank Expense" value="Rs {{ number_format($totalBankExpense, 0) }}" icon="account_balance" color="amber" />
        <x-stat-card label="Operating Cash & Bank" value="Rs {{ number_format($currentTotalBalance, 0) }}" icon="account_balance_wallet" color="indigo" subtitle="In Hand + Bank" />
    </div>

    <x-card>
        {{-- Filter Bar --}}
        <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50"
             x-data="{
                setDates(period) {
                    const now = new Date();
                    const to = now.toISOString().split('T')[0];
                    let from;
                    if (period === 'today') {
                        from = to;
                    } else if (period === 'week') {
                        const day = now.getDay();
                        from = new Date(now.getTime() - (day === 0 ? 6 : day - 1) * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                    } else if (period === 'month') {
                        from = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
                    } else if (period === 'lastmonth') {
                        from = new Date(now.getFullYear(), now.getMonth() - 1, 1).toISOString().split('T')[0];
                        const lastDay = new Date(now.getFullYear(), now.getMonth(), 0).toISOString().split('T')[0];
                        $refs.dateTo.value = lastDay;
                    }
                    $refs.dateFrom.value = from;
                    if (period !== 'lastmonth') $refs.dateTo.value = to;
                    $refs.filterForm.submit();
                }
             }">
            <form method="GET" x-ref="filterForm" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">From</label>
                    <input type="date" name="start" x-ref="dateFrom" value="{{ $startDate ?? '' }}" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">To</label>
                    <input type="date" name="end" x-ref="dateTo" value="{{ $endDate ?? '' }}" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Status</label>
                    <select name="status" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm min-w-[130px]">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All</option>
                        <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="not_approved" {{ $status === 'not_approved' ? 'selected' : '' }}>Not Approved</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">&nbsp;</label>
                    <x-button type="submit" variant="primary" icon="filter_alt" size="sm">Filter</x-button>
                </div>
                @if($status !== 'all' || $startDate || $endDate)
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">&nbsp;</label>
                        <a href="{{ route('billing.cash-bank-ledger.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-rose-50 hover:border-rose-200 hover:text-rose-600 dark:hover:bg-rose-900/20 dark:hover:border-rose-800 dark:hover:text-rose-400 transition-all">
                            <span class="material-symbols-rounded text-[16px]">close</span>
                            Clear
                        </a>
                    </div>
                @endif
                <div class="flex gap-1.5 ml-auto items-end">
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">&nbsp;</label>
                        <div class="flex gap-1.5">
                            <button type="button" @click="setDates('today')" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 dark:hover:bg-emerald-900/20 dark:hover:border-emerald-800 dark:hover:text-emerald-400 transition-all">Today</button>
                            <button type="button" @click="setDates('week')" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 dark:hover:bg-emerald-900/20 dark:hover:border-emerald-800 dark:hover:text-emerald-400 transition-all">This Week</button>
                            <button type="button" @click="setDates('month')" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 dark:hover:bg-emerald-900/20 dark:hover:border-emerald-800 dark:hover:text-emerald-400 transition-all">This Month</button>
                            <button type="button" @click="setDates('lastmonth')" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 dark:hover:bg-emerald-900/20 dark:hover:border-emerald-800 dark:hover:text-emerald-400 transition-all">Last Month</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <x-data-table :headers="['Date', 'Cash Income', 'Bank Income', 'Cash Expense', 'Bank Expense', 'Opening Cash', 'Closing Cash', 'Opening Bank', 'Closing Bank', 'Total Balance', 'Actions', 'Status', '']">
            @forelse($ledgers as $ledger)
                @php
                    $totalBalance = (float) $ledger->closing_cash_balance + (float) $ledger->closing_bank_balance;
                @endphp
                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                    <td class="px-6 py-4">
                        @php
                            $dStr = $ledger->ledger_date->format('Y-m-d');
                            $dayCaps = isset($dateCapitalMap) ? ($dateCapitalMap->get($dStr) ?? collect()) : collect();
                            $hasWithdrawal = $dayCaps->where('type', 'Withdrawal')->sum('amount');
                            $hasInvestment = $dayCaps->where('type', 'Investment')->sum('amount');
                        @endphp
                        <a href="{{ route('billing.cash-bank-ledger.show-day', $dStr) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $ledger->ledger_date->format('d M Y') }}</p>
                            <p class="text-xs text-zinc-500">{{ $ledger->ledger_date->format('l') }}</p>
                        </a>
                        @if($hasWithdrawal > 0)
                            <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800" title="Owner Drawings / Capital Withdrawn from Pool">
                                <span class="material-symbols-rounded text-[12px]">money_off</span>
                                Drawings: -Rs {{ number_format($hasWithdrawal, 0) }}
                            </span>
                        @endif
                        @if($hasInvestment > 0)
                            <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800" title="Capital Inflow into Pool">
                                <span class="material-symbols-rounded text-[12px]">savings</span>
                                Capital: +Rs {{ number_format($hasInvestment, 0) }}
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-jetbrains font-bold text-emerald-600">Rs {{ number_format((float) $ledger->cash_income, 0) }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-jetbrains font-bold text-blue-600">Rs {{ number_format((float) $ledger->bank_income, 0) }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-jetbrains font-bold text-rose-600">Rs {{ number_format((float) $ledger->cash_expense, 0) }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-jetbrains font-bold text-amber-600">Rs {{ number_format((float) $ledger->bank_expense, 0) }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-jetbrains text-zinc-600 dark:text-zinc-400">Rs {{ number_format((float) $ledger->opening_cash_balance, 0) }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-jetbrains font-bold {{ (float) $ledger->closing_cash_balance > 0 ? 'text-emerald-600' : 'text-zinc-500' }}">
                            Rs {{ number_format((float) $ledger->closing_cash_balance, 0) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-jetbrains text-zinc-600 dark:text-zinc-400">Rs {{ number_format((float) $ledger->opening_bank_balance, 0) }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-jetbrains font-bold text-blue-600">Rs {{ number_format((float) $ledger->closing_bank_balance, 0) }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-jetbrains font-bold text-indigo-600 text-base">Rs {{ number_format($totalBalance, 0) }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('billing.cash-bank-ledger.show-day', $ledger->ledger_date->format('Y-m-d')) }}"
                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-[11px] font-bold uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 dark:hover:bg-emerald-900/20 dark:hover:border-emerald-800 dark:hover:text-emerald-400 transition-all"
                           title="View details for {{ $ledger->ledger_date->format('d M Y') }}">
                            <span class="material-symbols-rounded text-[16px]">visibility</span>
                            View
                        </a>
                    </td>
                    <td class="px-6 py-4">
                        @if($ledger->is_approved)
                            <x-badge variant="success">Approved</x-badge>
                        @else
                            <x-badge variant="zinc">Not Approved</x-badge>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($ledger->is_approved)
                            <span class="text-xs text-zinc-500">
                                Approved by {{ $ledger->approvedBy?->name ?? 'Unknown' }}
                                @if($ledger->approved_at)
                                    on {{ $ledger->approved_at->format('d M Y, h:i A') }}
                                @endif
                                @if((float) ($ledger->approved_amount ?? 0) > 0)
                                    <br><span class="font-jetbrains text-emerald-600">Rs {{ number_format((float) $ledger->approved_amount, 0) }} swept</span>
                                @endif
                            </span>
                        @else
                            <form method="POST" action="{{ route('billing.cash-bank-ledger.approve', $ledger) }}" class="inline" onsubmit="return confirm('Are you sure you want to approve the ledger for {{ $ledger->ledger_date->format('d M Y') }}? This will sweep the closing cash balance of Rs {{ number_format((float) $ledger->closing_cash_balance, 0) }} into the bank account and is irreversible.')">
                                @csrf
                                <x-button type="submit" variant="primary" size="sm" icon="check_circle">
                                    Approve
                                </x-button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <x-slot:empty>
                    <x-empty-state icon="account_balance" title="No ledger entries found" description="Select a different date range or record some dealer payments first." />
                </x-slot:empty>
            @endforelse

            @if($ledgers->hasPages())
                <x-slot:pagination>
                    {{ $ledgers->withQueryString()->links() }}
                </x-slot:pagination>
            @endif
        </x-data-table>
    </x-card>
</div>
@endsection
