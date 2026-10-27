@extends('layouts.app')
@section('title', 'Dealer Payments')

@section('content')

<div class="animate-fade-in">
    <x-page-header title="Dealer Payments" subtitle="Track payments collected from dealers and customers">
        <x-slot:actions>
            <x-button variant="outline" href="{{ route('payments.dealers.export') }}" icon="download">
                Export
            </x-button>
            @can('create payments')
            <x-button variant="primary" href="{{ route('payments.dealers.create') }}" icon="add">
                Record Payment
            </x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-stat-card 
            label="Total Collected" 
            value="Rs {{ number_format($totalCollected, 0) }}" 
            icon="payments" 
            color="blue" />
        <x-stat-card 
            label="Receivable from Dealers" 
            value="Rs {{ number_format($dealers->sum(fn($d) => $d->displayed_outstanding), 0) }}" 
            icon="error" 
            color="rose" />
        <x-stat-card 
            label="Active Dealers" 
            value="{{ $dealers->filter(fn($d) => $d->displayed_outstanding > 0)->count() }}" 
            icon="group" 
            color="emerald" 
            trend="with Balances" 
            :trendUp="true" />
    </div>

    {{-- Filter Bar --}}
    <div class="mb-6 p-4 bg-zinc-50/50 dark:bg-zinc-800/30 rounded-2xl border border-zinc-200/50 dark:border-zinc-700/50"
         x-data="{
            setDates(period) {
                const now = new Date();
                const to = now.toISOString().split('T')[0];
                let from;
                if (period === 'today') { from = to; }
                else if (period === '7d') { from = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]; }
                else if (period === '30d') { from = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]; }
                else if (period === 'month') { from = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0]; }
                $refs.dateFrom.value = from;
                $refs.dateTo.value = to;
                $refs.filterForm.submit();
            }
         }">
        <form method="GET" x-ref="filterForm" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Dealer</label>
                <select name="dealer_id" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm min-w-[160px]">
                    <option value="">All Dealers</option>
                    @foreach($dealers as $d)
                        <option value="{{ $d->id }}" {{ ($dealerFilter ?? '') == $d->id ? 'selected' : '' }}>{{ $d->firm_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">From</label>
                <input type="date" name="date_from" x-ref="dateFrom" value="{{ $dateFrom ?? '' }}" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">To</label>
                <input type="date" name="date_to" x-ref="dateTo" value="{{ $dateTo ?? '' }}" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Mode</label>
                <select name="payment_mode" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm min-w-[120px]">
                    <option value="">All Modes</option>
                    <option value="Cash" {{ ($modeFilter ?? '') === 'Cash' ? 'selected' : '' }}>Cash</option>
                    <option value="UPI" {{ ($modeFilter ?? '') === 'UPI' ? 'selected' : '' }}>UPI</option>
                    <option value="NEFT" {{ ($modeFilter ?? '') === 'NEFT' ? 'selected' : '' }}>NEFT</option>
                    <option value="Cheque(Bank Transfer)" {{ ($modeFilter ?? '') === 'Cheque(Bank Transfer)' ? 'selected' : '' }}>Cheque</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">&nbsp;</label>
                <x-button type="submit" variant="primary" icon="filter_alt" size="sm">Filter</x-button>
            </div>
            @if($dealerFilter || $dateFrom || $dateTo || $modeFilter)
                <div>
                    <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">&nbsp;</label>
                    <a href="{{ route('payments.dealers.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-rose-50 hover:border-rose-200 hover:text-rose-600 dark:hover:bg-rose-900/20 dark:hover:border-rose-800 dark:hover:text-rose-400 transition-all">
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
                        <button type="button" @click="setDates('7d')" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 dark:hover:bg-emerald-900/20 dark:hover:border-emerald-800 dark:hover:text-emerald-400 transition-all">7 Days</button>
                        <button type="button" @click="setDates('30d')" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 dark:hover:bg-emerald-900/20 dark:hover:border-emerald-800 dark:hover:text-emerald-400 transition-all">30 Days</button>
                        <button type="button" @click="setDates('month')" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 dark:hover:bg-emerald-900/20 dark:hover:border-emerald-800 dark:hover:text-emerald-400 transition-all">This Month</button>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Search</label>
                <div class="relative">
                    <span class="material-symbols-rounded absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-[18px] pointer-events-none">search</span>
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Dealer or reference..." class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 pl-10 text-sm min-w-[180px]">
                </div>
            </div>
        </form>
    </div>

    {{-- Table Card --}}
    <x-card>
        <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50">
            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ $payments->total() }} payment{{ $payments->total() !== 1 ? 's' : '' }} found</p>
        </div>
        
        <x-data-table :headers="['Dealer / Firm', 'Payment Date', 'Amount Received', 'Payment Mode', 'Cash / Bank', 'Remaining Balance', 'Actions']">
            @forelse($payments as $p)
                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors group">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <x-avatar :name="$p->dealer->firm_name ?? '?'" size="sm" />
                            <div>
                                <p class="font-cabinet font-bold text-zinc-900 dark:text-zinc-100">{{ $p->dealer->firm_name ?? '-' }}</p>
                                <p class="font-outfit text-xs text-zinc-500">{{ $p->dealer->contact_person ?? 'NO CONTACT' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $p->date->format('d M, Y') }}</p>
                        <p class="text-xs text-zinc-500">{{ $p->date->format('l') }}</p>
                    </td>
                    <td class="px-6 py-4 font-jetbrains font-medium text-rose-600 dark:text-rose-400 text-right">
                        <x-currency :amount="$p->amount" />
                    </td>
                    <td class="px-6 py-4 text-center">
                        <x-badge variant="zinc">{{ $p->payment_mode }}</x-badge>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col items-start gap-0.5">
                            @php
                                $hasCash = !is_null($p->cash_amount) && $p->cash_amount > 0;
                                $hasBank = !is_null($p->bank_amount) && $p->bank_amount > 0;
                            @endphp
                            @if ($hasCash || $hasBank)
                                @if ($hasCash)
                                    <span class="text-xs font-jetbrains text-zinc-700 dark:text-zinc-300">
                                        Cash: <x-currency :amount="$p->cash_amount" />
                                    </span>
                                @endif
                                @if ($hasBank)
                                    <span class="text-xs font-jetbrains text-zinc-700 dark:text-zinc-300">
                                        Bank: <x-currency :amount="$p->bank_amount" />
                                        @if ($p->bank_transfer_type)
                                            <span class="text-[10px] text-zinc-400 ml-0.5">({{ $p->bank_transfer_type }})</span>
                                        @endif
                                    </span>
                                @endif
                            @else
                                <span class="text-xs text-zinc-400">—</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <span class="font-jetbrains font-medium text-zinc-900 dark:text-zinc-100">
                            <x-currency :amount="$p->pending_balance_after" />
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <x-button variant="outline" href="{{ route('payments.dealers.ledger', $p->dealer_id) }}" size="sm">
                                Ledger
                            </x-button>
                            <form action="{{ route('payments.dealers.destroy', $p->id) }}" method="POST" class="inline" onsubmit="return confirm('Undo/Delete this payment of Rs {{ number_format($p->amount, 2) }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 hover:text-rose-600 transition-all" title="Delete / Undo Payment">
                                    <span class="material-symbols-rounded text-[18px]">delete</span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <x-slot:empty>
                    <x-empty-state 
                        icon="account_balance_wallet" 
                        title="No Payouts Recorded" 
                        description="Ready to clear your dealer balances?" />
                </x-slot:empty>
            @endforelse

            @if($payments->hasPages())
                <x-slot:pagination>
                    {{ $payments->withQueryString()->links() }}
                </x-slot:pagination>
            @endif
        </x-data-table>
    </x-card>

@endsection
