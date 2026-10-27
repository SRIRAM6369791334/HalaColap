@extends('layouts.app')
@section('title', 'Capital & Investments')

@section('content')
<div class="animate-fade-in space-y-6" x-data="{
    selectedType: 'Investment',
    amount: '',
    personName: '',
    paymentMode: 'Cash',
    bankType: '',
    refNumber: '',
    notes: '',
    date: '{{ now()->format('Y-m-d') }}',
    poolBalance: {{ (float) $currentInvestmentBalance }},

    setType(type) {
        this.selectedType = type;
        if (type === 'Investment' || type === 'Transfer to Cash' || type === 'Transfer from Cash' || type === 'Withdrawal') {
            this.paymentMode = 'Cash';
            this.bankType = '';
        } else {
            this.paymentMode = 'Bank Transfer';
            this.bankType = 'UPI';
        }
    },

    openWith(type) {
        $dispatch('open-investment', type);
        $dispatch('open-modal', 'record-capital-modal');
    },

    fillMax() {
        if (['Transfer to Cash', 'Transfer to Bank', 'Withdrawal'].includes(this.selectedType)) {
            this.amount = this.poolBalance > 0 ? this.poolBalance.toFixed(2) : '0.00';
        }
    },

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
    {{-- Page Header --}}
    <x-page-header 
        title="Capital & Investments" 
        subtitle="Manage business capital reserves, operational cash/bank allocations, and owner drawings">
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                <x-button variant="outline" href="{{ route('billing.cash-bank-ledger.index') }}" icon="account_balance_wallet">
                    Ledger
                </x-button>
                <x-button variant="outline" type="button" @click="openWith('Transfer to Cash')" icon="payments">
                    To Cash
                </x-button>
                <x-button variant="outline" type="button" @click="openWith('Transfer to Bank')" icon="account_balance">
                    To Bank
                </x-button>
                <x-button variant="outline" type="button" @click="openWith('Transfer from Cash')" icon="input" class="!text-teal-600 hover:!bg-teal-50 dark:hover:!bg-teal-950/40">
                    From Cash
                </x-button>
                <x-button variant="outline" type="button" @click="openWith('Transfer from Bank')" icon="output" class="!text-cyan-600 hover:!bg-cyan-50 dark:hover:!bg-cyan-950/40">
                    From Bank
                </x-button>
                <x-button variant="outline" type="button" @click="openWith('Withdrawal')" icon="money_off" class="!text-rose-600 hover:!bg-rose-50 dark:hover:!bg-rose-950/40">
                    Owner Drawings
                </x-button>
                <x-button variant="primary" type="button" @click="openWith('Investment')" icon="add_circle" class="!bg-emerald-600 hover:!bg-emerald-700 shadow-md shadow-emerald-600/20">
                    + Add Investment
                </x-button>
            </div>
        </x-slot:actions>
    </x-page-header>

    {{-- Bento Grid Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
        {{-- Primary Treasury Hero Card --}}
        <div class="relative overflow-hidden p-5 rounded-2xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-slate-900 text-white shadow-xl shadow-indigo-600/20 sm:col-span-2 lg:col-span-1 border border-indigo-500/30 flex flex-col justify-between">
            <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
            <div>
                <div class="flex items-center justify-between opacity-90 mb-2">
                    <span class="text-[11px] font-black uppercase tracking-wider text-indigo-100 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Capital Pool
                    </span>
                    <span class="material-symbols-rounded text-xl text-indigo-200">savings</span>
                </div>
                <div class="text-2xl font-black font-jetbrains tracking-tight">
                    Rs {{ number_format($currentInvestmentBalance, 2) }}
                </div>
            </div>
            <div class="text-[11px] font-medium text-indigo-200/90 mt-3 pt-2.5 border-t border-indigo-500/30 flex items-center justify-between">
                <span>Available Reserve</span>
                <span class="font-mono font-bold text-white text-[10px] bg-white/15 px-2 py-0.5 rounded-full">Liquid</span>
            </div>
        </div>

        {{-- Stat Card: Total Invested --}}
        <x-stat-card 
            label="Total Invested" 
            value="Rs {{ number_format($totalInvested, 2) }}" 
            icon="add_card" 
            color="emerald" 
            subtitle="All-time capital injections" />

        {{-- Stat Card: Moved to Business --}}
        <x-stat-card 
            label="Moved to Business" 
            value="Rs {{ number_format($totalTransferredToBusiness, 2) }}" 
            icon="forward" 
            color="blue" 
            subtitle="Injected into Cash & Bank" />

        {{-- Stat Card: Vendor Advance Funded --}}
        <x-stat-card 
            label="Vendor Advances" 
            value="Rs {{ number_format($totalVendorAdvanceFunded, 2) }}" 
            icon="local_shipping" 
            color="amber" 
            subtitle="Funded directly to suppliers" />

        {{-- Stat Card: Owner Drawings --}}
        <x-stat-card 
            label="Owner Drawings" 
            value="Rs {{ number_format($totalWithdrawn, 2) }}" 
            icon="outbound" 
            color="rose" 
            subtitle="Profit / capital withdrawn" />
    </div>

    {{-- Live Liquidity Summary Ribbon --}}
    <div class="p-4 rounded-2xl bg-white/70 dark:bg-zinc-900/70 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-800/80 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-100 dark:border-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                <span class="material-symbols-rounded text-lg">account_tree</span>
            </div>
            <div>
                <p class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Live Operating Liquidity (Today)</p>
                <p class="text-[11px] text-zinc-500">Real-time balances across business operations</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-5 text-xs">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-xs shadow-emerald-500/50"></span>
                <span class="text-zinc-500">Cash in Hand:</span>
                <span class="font-bold font-jetbrains text-zinc-900 dark:text-zinc-100">Rs {{ number_format($currentCashBalance, 2) }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500 shadow-xs shadow-blue-500/50"></span>
                <span class="text-zinc-500">Bank Balance:</span>
                <span class="font-bold font-jetbrains text-zinc-900 dark:text-zinc-100">Rs {{ number_format($currentBankBalance, 2) }}</span>
            </div>
            <div class="flex items-center gap-2 pl-3 border-l border-zinc-200 dark:border-zinc-800">
                <span class="text-zinc-500">Total Business Liquidity:</span>
                <span class="font-bold font-jetbrains text-indigo-600 dark:text-indigo-400">
                    Rs {{ number_format($currentCashBalance + $currentBankBalance + $currentInvestmentBalance, 2) }}
                </span>
            </div>
            <a href="{{ route('billing.cash-bank-ledger.index') }}" class="inline-flex items-center gap-1 font-bold text-xs text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 hover:underline">
                View Ledger Details <span class="material-symbols-rounded text-sm">arrow_forward</span>
            </a>
        </div>
    </div>

    {{-- Main Filter & Data Table Card --}}
    <x-card>
        {{-- Filter Toolbar --}}
        <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50 space-y-3">
            <form method="GET" x-ref="filterForm" class="flex flex-wrap gap-3 items-end">
                {{-- Type Filter --}}
                <div>
                    <label class="block text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Transaction Type</label>
                    <select name="type" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-xs font-semibold min-w-[170px] focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        <option value="">All Types</option>
                        <option value="Investment" {{ ($typeFilter ?? '') === 'Investment' ? 'selected' : '' }}>Investment (Inflow)</option>
                        <option value="Transfer to Cash" {{ ($typeFilter ?? '') === 'Transfer to Cash' ? 'selected' : '' }}>Transfer to Cash</option>
                        <option value="Transfer to Bank" {{ ($typeFilter ?? '') === 'Transfer to Bank' ? 'selected' : '' }}>Transfer to Bank</option>
                        <option value="Transfer from Cash" {{ ($typeFilter ?? '') === 'Transfer from Cash' ? 'selected' : '' }}>Transfer from Cash</option>
                        <option value="Transfer from Bank" {{ ($typeFilter ?? '') === 'Transfer from Bank' ? 'selected' : '' }}>Transfer from Bank</option>
                        <option value="Withdrawal" {{ ($typeFilter ?? '') === 'Withdrawal' ? 'selected' : '' }}>Owner Withdrawal</option>
                        <option value="Vendor Advance Outflow" {{ ($typeFilter ?? '') === 'Vendor Advance Outflow' ? 'selected' : '' }}>Vendor Advance Outflow</option>
                    </select>
                </div>

                {{-- Date From --}}
                <div>
                    <label class="block text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">From</label>
                    <input type="date" name="date_from" x-ref="dateFrom" value="{{ $dateFrom ?? '' }}" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                </div>

                {{-- Date To --}}
                <div>
                    <label class="block text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">To</label>
                    <input type="date" name="date_to" x-ref="dateTo" value="{{ $dateTo ?? '' }}" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                </div>

                {{-- Search Input --}}
                <div>
                    <label class="block text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Search</label>
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Name, notes, reference..." class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-xs min-w-[180px] focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                </div>

                {{-- Submit Filter --}}
                <div>
                    <label class="block text-[11px] font-bold text-transparent mb-1">&nbsp;</label>
                    <x-button type="submit" variant="primary" icon="filter_alt" size="sm">Filter</x-button>
                </div>

                @if($typeFilter || $dateFrom || $dateTo || $search)
                    <div>
                        <label class="block text-[11px] font-bold text-transparent mb-1">&nbsp;</label>
                        <a href="{{ route('billing.investments.index') }}" class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-xs font-bold border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/30 transition-all">
                            <span class="material-symbols-rounded text-sm">close</span> Clear
                        </a>
                    </div>
                @endif

                {{-- Quick Presets Right Aligned --}}
                <div class="flex gap-1.5 ml-auto items-end">
                    <div>
                        <label class="block text-[11px] font-bold text-zinc-400 uppercase tracking-wider mb-1">Quick Range</label>
                        <div class="flex gap-1.5">
                            <button type="button" @click="setDates('today')" class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-emerald-50 hover:border-emerald-300 hover:text-emerald-700 dark:hover:bg-emerald-950/40 dark:hover:border-emerald-800 dark:hover:text-emerald-400 transition-all">Today</button>
                            <button type="button" @click="setDates('7d')" class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-emerald-50 hover:border-emerald-300 hover:text-emerald-700 dark:hover:bg-emerald-950/40 dark:hover:border-emerald-800 dark:hover:text-emerald-400 transition-all">7 Days</button>
                            <button type="button" @click="setDates('30d')" class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-emerald-50 hover:border-emerald-300 hover:text-emerald-700 dark:hover:bg-emerald-950/40 dark:hover:border-emerald-800 dark:hover:text-emerald-400 transition-all">30 Days</button>
                            <button type="button" @click="setDates('month')" class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-emerald-50 hover:border-emerald-300 hover:text-emerald-700 dark:hover:bg-emerald-950/40 dark:hover:border-emerald-800 dark:hover:text-emerald-400 transition-all">This Month</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Transactions Data Table --}}
        <x-data-table :headers="['Date', 'Transaction Type', 'Investor / Source', 'Amount', 'Channel / Mode', 'Notes & Reference', 'Actions']">
            @forelse($transactions as $tx)
                @php
                    $badgeStyle = match($tx->type) {
                        'Investment' => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800', 'icon' => 'add_circle', 'prefix' => '+'],
                        'Transfer to Cash' => ['bg' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800', 'icon' => 'payments', 'prefix' => '➔ Cash'],
                        'Transfer to Bank' => ['bg' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800', 'icon' => 'account_balance', 'prefix' => '➔ Bank'],
                        'Transfer from Cash' => ['bg' => 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-950/40 dark:text-teal-300 dark:border-teal-800', 'icon' => 'savings', 'prefix' => 'Cash ➔'],
                        'Transfer from Bank' => ['bg' => 'bg-cyan-50 text-cyan-700 border-cyan-200 dark:bg-cyan-950/40 dark:text-cyan-300 dark:border-cyan-800', 'icon' => 'savings', 'prefix' => 'Bank ➔'],
                        'Withdrawal' => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800', 'icon' => 'money_off', 'prefix' => '-'],
                        'Vendor Advance Outflow' => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800', 'icon' => 'local_shipping', 'prefix' => 'Advance'],
                        default => ['bg' => 'bg-zinc-50 text-zinc-700 border-zinc-200', 'icon' => 'swap_horiz', 'prefix' => '']
                    };
                @endphp
                <tr class="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/50 transition-colors group">
                    {{-- Date --}}
                    <td class="px-6 py-4 whitespace-nowrap text-xs font-semibold text-zinc-600 dark:text-zinc-400">
                        {{ $tx->date->format('d M Y') }}
                        <span class="block text-[10px] text-zinc-400">{{ $tx->date->format('l') }}</span>
                    </td>

                    {{-- Transaction Type Badge --}}
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold border {{ $badgeStyle['bg'] }}">
                            <span class="material-symbols-rounded text-sm">{{ $badgeStyle['icon'] }}</span>
                            {{ $tx->type }}
                        </span>
                    </td>

                    {{-- Investor / Person --}}
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 flex items-center justify-center font-bold text-xs uppercase border border-zinc-200 dark:border-zinc-700">
                            {{ substr($tx->person_name ?: 'Owner', 0, 1) }}
                        </div>
                        <div>
                            <span>{{ $tx->person_name ?: 'Owner / Business' }}</span>
                            @if($tx->creator)
                                <span class="block text-[10px] font-normal text-zinc-400">by {{ $tx->creator->name }}</span>
                            @endif
                        </div>
                    </td>

                    {{-- Amount --}}
                    <td class="px-6 py-4 whitespace-nowrap font-jetbrains font-bold text-sm text-zinc-900 dark:text-zinc-100">
                        <span class="{{ $tx->type === 'Investment' ? 'text-emerald-600 dark:text-emerald-400' : ($tx->type === 'Withdrawal' ? 'text-rose-600 dark:text-rose-400' : '') }}">
                            Rs {{ number_format($tx->amount, 2) }}
                        </span>
                    </td>

                    {{-- Payment Mode & Transfer Type --}}
                    <td class="px-6 py-4 whitespace-nowrap text-xs text-zinc-600 dark:text-zinc-400">
                        <span class="font-semibold">{{ $tx->payment_mode }}</span>
                        @if($tx->bank_transfer_type)
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 font-mono ml-1 text-zinc-600 dark:text-zinc-400">{{ $tx->bank_transfer_type }}</span>
                        @endif
                    </td>

                    {{-- Notes & Reference --}}
                    <td class="px-6 py-4 text-xs text-zinc-500 max-w-xs truncate">
                        {{ $tx->notes ?: '—' }}
                        @if($tx->reference_number)
                            <span class="block text-[10px] text-zinc-400 font-mono mt-0.5">Ref: {{ $tx->reference_number }}</span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            <button type="button" 
                                    @click="$dispatch('open-capital-details', {{ json_encode([
                                        'id' => $tx->id,
                                        'type' => $tx->type,
                                        'date' => $tx->date->format('d M Y (l)'),
                                        'amount' => number_format($tx->amount, 2),
                                        'person_name' => $tx->person_name ?: 'Owner / Business',
                                        'creator' => $tx->creator?->name ?? 'System',
                                        'payment_mode' => $tx->payment_mode,
                                        'bank_transfer_type' => $tx->bank_transfer_type ?: 'None',
                                        'reference_number' => $tx->reference_number ?: 'None',
                                        'notes' => $tx->notes ?: 'No additional notes provided.',
                                        'badge_bg' => $badgeStyle['bg'],
                                        'badge_icon' => $badgeStyle['icon'],
                                        'delete_url' => route('billing.investments.destroy', $tx->id),
                                    ]) }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-bold text-zinc-600 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 border border-zinc-200 dark:border-zinc-700 transition-colors" 
                                    title="View Details">
                                <span class="material-symbols-rounded text-[16px]">visibility</span>
                                <span>View</span>
                            </button>
                            <form action="{{ route('billing.investments.destroy', $tx->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this {{ $tx->type }} of Rs {{ number_format($tx->amount, 2) }}? Ledger balances will be automatically recalculated.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 rounded-xl text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-transparent hover:border-rose-200 dark:hover:border-rose-800 transition-colors" title="Delete Transaction">
                                    <span class="material-symbols-rounded text-[16px]">delete</span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <x-slot:empty>
                    <x-empty-state 
                        icon="savings" 
                        title="No Capital Transactions Found" 
                        description="Click '+ Add Investment' to log capital inflow or transfer funds into operating accounts." />
                </x-slot:empty>
            @endforelse

            @if($transactions->hasPages())
                <x-slot:pagination>
                    {{ $transactions->withQueryString()->links() }}
                </x-slot:pagination>
            @endif
        </x-data-table>
    </x-card>
</div>

@push('modals')
{{-- Standardized Record Capital Transaction Modal matching Second Image --}}
<x-modal name="record-capital-modal" title="Record Capital Transaction" subtitle="Log capital injections, fund transfers, or owner drawings" icon="savings" maxWidth="720" :show="$errors->any()">
    <form id="record-capital-form" 
          action="{{ route('billing.investments.store') }}" 
          method="POST"
          @submit="if (isOverLimit || submitting) { $event.preventDefault(); return false; } submitting = true;"
          x-data="{
              selectedType: 'Investment',
              date: '{{ now()->format('Y-m-d') }}',
              cashAmount: '',
              bankAmount: '',
              personName: '',
              refNumber: '',
              bankTransferType: 'UPI',
              notes: '',
              submitting: false,
              poolBalance: {{ (float) $currentInvestmentBalance }},
              cashBalance: {{ (float) $currentCashBalance }},
              bankBalance: {{ (float) $currentBankBalance }},

              get totalAmount() {
                  let c = parseFloat(this.cashAmount) || 0;
                  let b = parseFloat(this.bankAmount) || 0;
                  return (c + b).toFixed(2);
              },

              setType(type) {
                  this.selectedType = type;
                  if (type === 'Transfer to Cash' || type === 'Transfer from Cash') {
                      if (!this.cashAmount && this.bankAmount) {
                          this.cashAmount = this.bankAmount;
                          this.bankAmount = '';
                      }
                  } else if (type === 'Transfer to Bank' || type === 'Transfer from Bank') {
                      if (!this.bankAmount && this.cashAmount) {
                          this.bankAmount = this.cashAmount;
                          this.cashAmount = '';
                      }
                  }
              },

              fillMax() {
                  if (this.selectedType === 'Transfer from Cash') {
                      this.cashAmount = this.cashBalance > 0 ? this.cashBalance.toFixed(2) : '0.00';
                      this.bankAmount = '';
                  } else if (this.selectedType === 'Transfer from Bank') {
                      this.bankAmount = this.bankBalance > 0 ? this.bankBalance.toFixed(2) : '0.00';
                      this.cashAmount = '';
                  } else if (this.selectedType === 'Transfer to Bank') {
                      this.bankAmount = this.poolBalance > 0 ? this.poolBalance.toFixed(2) : '0.00';
                      this.cashAmount = '';
                  } else {
                      this.cashAmount = this.poolBalance > 0 ? this.poolBalance.toFixed(2) : '0.00';
                      this.bankAmount = '';
                  }
              },

              get isOverLimit() {
                  let amt = parseFloat(this.totalAmount) || 0;
                  if (['Transfer to Cash', 'Transfer to Bank', 'Withdrawal'].includes(this.selectedType)) {
                      return amt > this.poolBalance;
                  }
                  if (this.selectedType === 'Transfer from Cash') {
                      return (parseFloat(this.cashAmount) || 0) > this.cashBalance;
                  }
                  if (this.selectedType === 'Transfer from Bank') {
                      return (parseFloat(this.bankAmount) || 0) > this.bankBalance;
                  }
                  return false;
              },

              get limitMessage() {
                  if (['Transfer to Cash', 'Transfer to Bank', 'Withdrawal'].includes(this.selectedType)) {
                      return '⚠️ Entered amount exceeds available Capital Pool balance of Rs ' + this.poolBalance.toLocaleString('en-IN', {minimumFractionDigits: 2});
                  }
                  if (this.selectedType === 'Transfer from Cash') {
                      return '⚠️ Entered amount exceeds available Cash in Hand of Rs ' + this.cashBalance.toLocaleString('en-IN', {minimumFractionDigits: 2});
                  }
                  if (this.selectedType === 'Transfer from Bank') {
                      return '⚠️ Entered amount exceeds available Bank balance of Rs ' + this.bankBalance.toLocaleString('en-IN', {minimumFractionDigits: 2});
                  }
                  return '';
              }
          }"
          x-on:open-investment.window="setType($event.detail)">
        @csrf

        {{-- Action Type Selection matching Category selector in second image --}}
        <div class="mb-6">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 font-outfit mb-3.5">
                Category / Action <span class="text-zinc-400 dark:text-zinc-500 text-xs ml-0.5">*</span>
            </label>
            <div class="grid grid-cols-3 sm:grid-cols-6 gap-2.5 sm:gap-3">
                @php
                    $types = [
                        ['key' => 'Investment', 'label' => 'Investment', 'icon' => 'savings', 'color' => 'text-emerald-500'],
                        ['key' => 'Transfer to Cash', 'label' => 'To Cash', 'icon' => 'payments', 'color' => 'text-blue-500'],
                        ['key' => 'Transfer to Bank', 'label' => 'To Bank', 'icon' => 'account_balance', 'color' => 'text-indigo-500'],
                        ['key' => 'Transfer from Cash', 'label' => 'From Cash', 'icon' => 'input', 'color' => 'text-teal-500'],
                        ['key' => 'Transfer from Bank', 'label' => 'From Bank', 'icon' => 'output', 'color' => 'text-cyan-500'],
                        ['key' => 'Withdrawal', 'label' => 'Drawings', 'icon' => 'money_off', 'color' => 'text-rose-500'],
                    ];
                @endphp
                @foreach($types as $t)
                <label @click="setType('{{ $t['key'] }}')" 
                       class="group relative flex flex-col items-center gap-2 py-4 px-1 rounded-2xl border-2 cursor-pointer transition-all duration-200 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/80 dark:has-[:checked]:bg-emerald-500/12 has-[:checked]:shadow-[0_0_0_1px_rgba(16,185,129,0.15),0_4px_12px_rgba(16,185,129,0.15)] border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 bg-white/50 dark:bg-zinc-900/50 text-center">
                    <input type="radio" name="type" value="{{ $t['key'] }}" class="sr-only" x-model="selectedType" required>
                    <div class="relative">
                        <span class="material-symbols-rounded text-[28px] {{ $t['color'] }}">{{ $t['icon'] }}</span>
                        <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-emerald-500 text-white flex items-center justify-center scale-0 group-has-[:checked]:scale-100 transition-transform duration-200">
                            <span class="material-symbols-rounded text-[12px]">check</span>
                        </span>
                    </div>
                    <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 group-has-[:checked]:text-emerald-700 dark:group-has-[:checked]:text-emerald-300 group-has-[:checked]:font-bold transition-all truncate w-full">{{ $t['label'] }}</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Real-Time Balance Context Alert for Investment Inflows --}}
        <div x-show="selectedType === 'Investment'" x-cloak class="p-3.5 mb-6 rounded-2xl bg-purple-50/80 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2 text-purple-900 dark:text-purple-200">
                <span class="material-symbols-rounded text-purple-600 text-lg">savings</span>
                <span>Current Capital Pool Reserve: <strong class="font-jetbrains text-purple-700 dark:text-purple-300 font-bold">Rs {{ number_format($currentInvestmentBalance, 2) }}</strong></span>
            </div>
            <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold uppercase tracking-wider bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                Capital Inflow
            </span>
        </div>

        {{-- Real-Time Balance Context Alert when moving funds FROM pool --}}
        <div x-show="['Transfer to Cash', 'Transfer to Bank', 'Withdrawal'].includes(selectedType)" x-cloak class="p-3.5 mb-6 rounded-2xl bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2 text-emerald-900 dark:text-emerald-200">
                <span class="material-symbols-rounded text-emerald-600 text-lg">account_balance_wallet</span>
                <span>Available in Capital Pool: <strong class="font-jetbrains text-emerald-700 dark:text-emerald-300 font-bold">Rs {{ number_format($currentInvestmentBalance, 2) }}</strong></span>
            </div>
            <button type="button" @click="fillMax()" class="px-3 py-1 rounded-xl text-[10px] font-bold uppercase bg-emerald-600 hover:bg-emerald-700 text-white transition-colors shadow-xs">
                Max Pool
            </button>
        </div>

        {{-- Real-Time Balance Context Alert when moving funds TO pool from operating --}}
        <div x-show="selectedType === 'Transfer from Cash'" x-cloak class="p-3.5 mb-6 rounded-2xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2 text-blue-900 dark:text-blue-200">
                <span class="material-symbols-rounded text-blue-600 text-lg">payments</span>
                <span>Available Cash in Hand: <strong class="font-jetbrains text-blue-700 dark:text-blue-300 font-bold">Rs {{ number_format($currentCashBalance, 2) }}</strong></span>
            </div>
            <button type="button" @click="fillMax()" class="px-3 py-1 rounded-xl text-[10px] font-bold uppercase bg-blue-600 hover:bg-blue-700 text-white transition-colors shadow-xs">
                Max Cash
            </button>
        </div>
        <div x-show="selectedType === 'Transfer from Bank'" x-cloak class="p-3.5 mb-6 rounded-2xl bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2 text-indigo-900 dark:text-indigo-200">
                <span class="material-symbols-rounded text-indigo-600 text-lg">account_balance</span>
                <span>Available Bank Balance: <strong class="font-jetbrains text-indigo-700 dark:text-indigo-300 font-bold">Rs {{ number_format($currentBankBalance, 2) }}</strong></span>
            </div>
            <button type="button" @click="fillMax()" class="px-3 py-1 rounded-xl text-[10px] font-bold uppercase bg-indigo-600 hover:bg-indigo-700 text-white transition-colors shadow-xs">
                Max Bank
            </button>
        </div>

        {{-- Real-Time Overdraft / Exceeds Balance Warning --}}
        <div x-show="isOverLimit" x-cloak class="p-3.5 mb-6 rounded-2xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 flex items-center gap-2 text-xs text-rose-700 dark:text-rose-300 font-semibold animate-pulse">
            <span class="material-symbols-rounded text-rose-600 text-lg">warning</span>
            <span x-text="limitMessage"></span>
        </div>

        {{-- Separate Input Boxes for Cash and Bank Amount with Smart Dynamic Display --}}
        <div class="mb-6 p-5 rounded-2xl bg-zinc-50/90 dark:bg-zinc-800/50 border border-zinc-200/80 dark:border-zinc-700/80 space-y-4">
            <div class="flex items-center justify-between border-b border-zinc-200/60 dark:border-zinc-700/60 pb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                    <span class="material-symbols-rounded text-emerald-500 text-lg">payments</span>
                    <span x-text="['Transfer to Cash', 'Transfer from Cash'].includes(selectedType) ? 'Cash Transfer Amount' : (['Transfer to Bank', 'Transfer from Bank'].includes(selectedType) ? 'Bank Transfer Amount' : 'Transaction Amount & Distribution')"></span>
                </span>
                <span class="text-xs font-bold text-zinc-500">
                    Total Amount: <strong class="font-jetbrains text-sm font-black text-emerald-600 dark:text-emerald-400">Rs <span x-text="totalAmount"></span></strong>
                </span>
            </div>

            {{-- 1. If Category is To Cash or From Cash: Show ONLY Cash Amount --}}
            <div x-show="['Transfer to Cash', 'Transfer from Cash'].includes(selectedType)" x-cloak>
                <div class="p-3.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5 flex items-center gap-1">
                        <span class="material-symbols-rounded text-emerald-500 text-base">payments</span>
                        Cash Amount (Rs) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0.01" name="cash_amount" x-model="cashAmount" placeholder="Enter cash amount..." class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/50 px-3 py-2 text-sm font-jetbrains font-bold focus:border-emerald-500 focus:ring-0">
                </div>
            </div>

            {{-- 2. If Category is To Bank or From Bank: Show ONLY Bank Amount --}}
            <div x-show="['Transfer to Bank', 'Transfer from Bank'].includes(selectedType)" x-cloak>
                <div class="p-3.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5 flex items-center gap-1">
                        <span class="material-symbols-rounded text-blue-500 text-base">account_balance</span>
                        Bank Amount (Rs) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0.01" name="bank_amount" x-model="bankAmount" placeholder="Enter bank amount..." class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/50 px-3 py-2 text-sm font-jetbrains font-bold focus:border-blue-500 focus:ring-0">
                </div>
            </div>

            {{-- 3. If Category is Investment or Drawings: Show BOTH Cash and Bank Split Inputs --}}
            <div x-show="['Investment', 'Withdrawal'].includes(selectedType)" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Cash Amount Input --}}
                <div class="p-3.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5 flex items-center gap-1">
                        <span class="material-symbols-rounded text-emerald-500 text-base">payments</span>
                        Cash Amount (Rs)
                    </label>
                    <input type="number" step="0.01" min="0" name="cash_amount" x-model="cashAmount" placeholder="0.00" class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/50 px-3 py-2 text-sm font-jetbrains font-bold focus:border-emerald-500 focus:ring-0">
                </div>

                {{-- Bank Amount Input --}}
                <div class="p-3.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5 flex items-center gap-1">
                        <span class="material-symbols-rounded text-blue-500 text-base">account_balance</span>
                        Bank Amount (Rs)
                    </label>
                    <input type="number" step="0.01" min="0" name="bank_amount" x-model="bankAmount" placeholder="0.00" class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/50 px-3 py-2 text-sm font-jetbrains font-bold focus:border-blue-500 focus:ring-0">
                </div>
            </div>

            {{-- Bank Transfer Channel (when Bank is selected or Bank Amount > 0) --}}
            <div x-show="['Transfer to Bank', 'Transfer from Bank'].includes(selectedType) || parseFloat(bankAmount) > 0" x-cloak class="pt-1">
                <label class="block text-xs font-bold uppercase text-zinc-500 mb-1.5">Bank Transfer Channel / Type</label>
                <select name="bank_transfer_type" x-model="bankTransferType" class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm font-semibold">
                    <option value="UPI">UPI (GPay / PhonePe / Paytm)</option>
                    <option value="NEFT">NEFT</option>
                    <option value="IMPS">IMPS</option>
                    <option value="RTGS">RTGS</option>
                    <option value="Cheque">Cheque</option>
                    <option value="Other">Other Bank Transfer</option>
                </select>
            </div>
        </div>

        {{-- Date & Investor Name in 2 columns --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-6">
            <x-form.input type="date" name="date" label="Date" required x-model="date" icon="calendar_month" />
            <x-form.input name="person_name" label="Investor / Owner Name" x-model="personName" placeholder="e.g. Sriram / Partner" icon="person" />
        </div>

        {{-- Reference Number & Description --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-6">
            <x-form.input name="reference_number" label="Reference / UTR Number" x-model="refNumber" placeholder="Optional transaction ID..." icon="tag" />
            <x-form.input name="notes" label="Description / Purpose" x-model="notes" placeholder="e.g. Initial capital injection from partner" icon="description" />
        </div>

        {{-- Modal Footer with Cancel and Save Buttons --}}
        <x-slot:footer>
            <x-button type="button" variant="outline" x-on:click="show = false">Cancel</x-button>
            <x-button type="submit" form="record-capital-form" variant="primary" icon="check" class="px-8 !bg-emerald-600 hover:!bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed" x-bind:disabled="isOverLimit || submitting">
                <span x-text="submitting ? 'Saving...' : 'Save Transaction'"></span>
            </x-button>
        </x-slot:footer>
    </form>
</x-modal>

{{-- Capital Transaction Details Modal --}}
<x-modal name="capital-details-modal" title="Capital Transaction Details" subtitle="Full audit breakdown and payment trail" icon="info" maxWidth="560">
    <div x-data="{ tx: null }" x-on:open-capital-details.window="tx = $event.detail; $dispatch('open-modal', 'capital-details-modal')" class="space-y-4">
        <template x-if="tx">
            <div class="space-y-4">
                {{-- Header Banner with Type & Amount --}}
                <div class="p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/80 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-sm">
                            <span class="material-symbols-rounded text-xl" x-text="tx.badge_icon"></span>
                        </div>
                        <div>
                            <span class="text-[10px] font-black uppercase text-zinc-500 tracking-wider">Transaction Type</span>
                            <h4 class="font-cabinet font-bold text-base text-zinc-900 dark:text-zinc-100" x-text="tx.type"></h4>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] font-bold text-zinc-500 uppercase">Amount</span>
                        <p class="font-jetbrains font-black text-xl text-emerald-600 dark:text-emerald-400">Rs <span x-text="tx.amount"></span></p>
                    </div>
                </div>

                {{-- Details Grid --}}
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="p-3 rounded-xl bg-zinc-50/50 dark:bg-zinc-800/30 border border-zinc-200/50 dark:border-zinc-700/50">
                        <span class="text-[10px] font-bold text-zinc-400 uppercase block mb-1">Date</span>
                        <span class="font-semibold text-zinc-900 dark:text-zinc-100" x-text="tx.date"></span>
                    </div>
                    <div class="p-3 rounded-xl bg-zinc-50/50 dark:bg-zinc-800/30 border border-zinc-200/50 dark:border-zinc-700/50">
                        <span class="text-[10px] font-bold text-zinc-400 uppercase block mb-1">Investor / Owner</span>
                        <span class="font-semibold text-zinc-900 dark:text-zinc-100" x-text="tx.person_name"></span>
                    </div>
                    <div class="p-3 rounded-xl bg-zinc-50/50 dark:bg-zinc-800/30 border border-zinc-200/50 dark:border-zinc-700/50">
                        <span class="text-[10px] font-bold text-zinc-400 uppercase block mb-1">Payment Mode</span>
                        <span class="font-semibold text-zinc-900 dark:text-zinc-100" x-text="tx.payment_mode"></span>
                    </div>
                    <div class="p-3 rounded-xl bg-zinc-50/50 dark:bg-zinc-800/30 border border-zinc-200/50 dark:border-zinc-700/50">
                        <span class="text-[10px] font-bold text-zinc-400 uppercase block mb-1">Channel / Type</span>
                        <span class="font-semibold text-zinc-900 dark:text-zinc-100" x-text="tx.bank_transfer_type"></span>
                    </div>
                    <div class="p-3 rounded-xl bg-zinc-50/50 dark:bg-zinc-800/30 border border-zinc-200/50 dark:border-zinc-700/50">
                        <span class="text-[10px] font-bold text-zinc-400 uppercase block mb-1">Reference / UTR</span>
                        <span class="font-mono text-zinc-900 dark:text-zinc-100" x-text="tx.reference_number"></span>
                    </div>
                    <div class="p-3 rounded-xl bg-zinc-50/50 dark:bg-zinc-800/30 border border-zinc-200/50 dark:border-zinc-700/50">
                        <span class="text-[10px] font-bold text-zinc-400 uppercase block mb-1">Recorded By</span>
                        <span class="font-semibold text-zinc-900 dark:text-zinc-100" x-text="tx.creator"></span>
                    </div>
                </div>

                {{-- Description / Notes --}}
                <div class="p-3.5 rounded-xl bg-zinc-50/50 dark:bg-zinc-800/30 border border-zinc-200/50 dark:border-zinc-700/50 text-xs">
                    <span class="text-[10px] font-bold text-zinc-400 uppercase block mb-1">Description / Notes</span>
                    <p class="text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap" x-text="tx.notes"></p>
                </div>
            </div>
        </template>
    </div>
    <x-slot:footer>
        <div class="flex items-center justify-between w-full gap-3">
            <template x-if="tx && tx.delete_url">
                <form :action="tx.delete_url" method="POST" onsubmit="return confirm('Delete this transaction? Ledger balances will be automatically recalculated.')">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="outline" icon="delete" class="!text-rose-600 hover:!bg-rose-50 dark:hover:!bg-rose-950/40 border-rose-200 dark:border-rose-800">
                        Delete Transaction
                    </x-button>
                </form>
            </template>
            <x-button type="button" variant="outline" x-on:click="show = false" class="ml-auto">Close</x-button>
        </div>
    </x-slot:footer>
</x-modal>
@endpush
@endsection

