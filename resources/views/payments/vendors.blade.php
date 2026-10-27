@extends('layouts.app')
@section('title', 'Vendor Payments & Advances')

@section('content')

<div class="animate-fade-in space-y-6" x-data="{
    advanceModalOpen: false,
    advDate: '{{ now()->format('Y-m-d') }}',
    advVendorId: '',
    advCash: '',
    advBank: '',
    advInvestment: '',
    advMode: 'Cash',
    advBankType: '',
    advRef: '',
    advNotes: '',

    maxCash: {{ (float) $currentCashBalance }},
    maxBank: {{ (float) $currentBankBalance }},
    maxInvestment: {{ (float) $currentInvestmentBalance }},

    get totalAdvance() {
        return (parseFloat(this.advCash || 0) + parseFloat(this.advBank || 0) + parseFloat(this.advInvestment || 0)).toFixed(2);
    },

    openAdvanceModal() {
        this.advCash = '';
        this.advBank = '';
        this.advInvestment = '';
        this.advNotes = '';
        this.advRef = '';
        this.advVendorId = '{{ $vendors->first()->id ?? '' }}';
        this.advanceModalOpen = true;
    }
}">
    <x-page-header title="Vendor Payments & Advances" subtitle="Track payments, advance deposits, and settlement adjustments with suppliers">
        <x-slot:actions>
            <div class="flex flex-wrap gap-2">
                <x-button variant="outline" href="{{ route('payments.vendors.export') }}" icon="download">
                    Export
                </x-button>
                <button type="button" @click="openAdvanceModal()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white shadow-sm transition-all">
                    <span class="material-symbols-rounded text-[18px]">local_shipping</span>
                    + Record Advance
                </button>
                @can('create payments')
                <x-button variant="primary" href="{{ route('payments.vendors.create') }}" icon="add" class="!bg-purple-600 hover:!bg-purple-700">
                    Record Payout
                </x-button>
                @endcan
            </div>
        </x-slot:actions>
    </x-page-header>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <x-stat-card 
            label="Total Paid Out" 
            value="Rs {{ number_format($totalPaidOut, 0) }}" 
            icon="payments" 
            color="purple" 
            subtitle="Cleared against vendor bills" />
        <x-stat-card 
            label="Payable to Vendors" 
            value="Rs {{ number_format($vendors->where('outstanding_balance', '>', 0)->sum('outstanding_balance'), 0) }}" 
            icon="error" 
            color="rose" 
            subtitle="Net outstanding liability" />
        <x-stat-card 
            label="Total Advances Given" 
            value="Rs {{ number_format($totalAdvancesGiven, 0) }}" 
            icon="account_balance_wallet" 
            color="amber" 
            subtitle="All-time advance deposits" />
        <x-stat-card 
            label="Unadjusted Advance" 
            value="Rs {{ number_format($totalAdvancesRemaining, 0) }}" 
            icon="pending" 
            color="emerald" 
            subtitle="Available to adjust in Day-Load" />
    </div>

    {{-- Live Source Balances Bar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/60 dark:border-zinc-700/60">
        <div class="flex items-center gap-2">
            <span class="material-symbols-rounded text-indigo-500 text-lg">account_balance</span>
            <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300">Available Funding Sources for Advance:</span>
        </div>
        <div class="flex flex-wrap items-center gap-4">
            <div class="inline-flex items-center gap-1.5 text-xs">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span class="text-zinc-500">Cash:</span>
                <span class="font-bold font-jetbrains text-zinc-900 dark:text-zinc-100">Rs {{ number_format($currentCashBalance, 2) }}</span>
            </div>
            <div class="inline-flex items-center gap-1.5 text-xs">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <span class="text-zinc-500">Bank:</span>
                <span class="font-bold font-jetbrains text-zinc-900 dark:text-zinc-100">Rs {{ number_format($currentBankBalance, 2) }}</span>
            </div>
            <div class="inline-flex items-center gap-1.5 text-xs">
                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                <span class="text-zinc-500">Investment Pool:</span>
                <span class="font-bold font-jetbrains text-zinc-900 dark:text-zinc-100">Rs {{ number_format($currentInvestmentBalance, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Tabs & Content --}}
    <x-card>
        {{-- Tab Navigation --}}
        <div class="flex border-b border-zinc-200/50 dark:border-zinc-800/50 px-6 pt-4 gap-6">
            <a href="{{ request()->fullUrlWithQuery(['tab' => 'payouts']) }}" 
               class="pb-3 text-sm font-bold border-b-2 transition-all {{ ($tab ?? 'payouts') === 'payouts' ? 'border-purple-600 text-purple-600 dark:border-purple-400 dark:text-purple-400' : 'border-transparent text-zinc-500 hover:text-zinc-700' }}">
                <span class="inline-flex items-center gap-1.5">
                    <span class="material-symbols-rounded text-[18px]">payments</span>
                    Standard Payouts
                </span>
            </a>
            <a href="{{ request()->fullUrlWithQuery(['tab' => 'advances']) }}" 
               class="pb-3 text-sm font-bold border-b-2 transition-all {{ ($tab ?? '') === 'advances' ? 'border-amber-500 text-amber-600 dark:border-amber-400 dark:text-amber-400' : 'border-transparent text-zinc-500 hover:text-zinc-700' }}">
                <span class="inline-flex items-center gap-1.5">
                    <span class="material-symbols-rounded text-[18px]">local_shipping</span>
                    Vendor Advances
                    @if($totalAdvancesRemaining > 0)
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 font-bold">Active</span>
                    @endif
                </span>
            </a>
        </div>

        {{-- Filter Bar --}}
        <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <input type="hidden" name="tab" value="{{ $tab ?? 'payouts' }}">
                <div>
                    <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Vendor</label>
                    <select name="vendor_id" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm min-w-[160px]">
                        <option value="">All Vendors</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}" {{ ($vendorFilter ?? '') == $v->id ? 'selected' : '' }}>{{ $v->firm_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">To</label>
                    <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Search</label>
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Vendor or reference..." class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm min-w-[160px]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">&nbsp;</label>
                    <x-button type="submit" variant="primary" icon="filter_alt" size="sm" class="!bg-purple-600 hover:!bg-purple-700">Filter</x-button>
                </div>
                @if($vendorFilter || $dateFrom || $dateTo || $search)
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">&nbsp;</label>
                        <a href="{{ route('payments.vendors.index', ['tab' => $tab]) }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-rose-50 hover:text-rose-600 transition-all">
                            <span class="material-symbols-rounded text-sm">close</span> Clear
                        </a>
                    </div>
                @endif
            </form>
        </div>

        {{-- Tab 1: Payouts Table --}}
        @if(($tab ?? 'payouts') === 'payouts')
            <x-data-table :headers="['Vendor / Firm', 'Payout Date', 'Amount Paid', 'Payment Mode', 'Cash / Bank', 'Balance After', 'Actions']">
                @forelse($payments as $p)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <x-avatar :name="$p->vendor->firm_name ?? '?'" size="sm" />
                                <div>
                                    <p class="font-cabinet font-bold text-zinc-900 dark:text-zinc-100">{{ $p->vendor->firm_name ?? '-' }}</p>
                                    <p class="font-outfit text-xs text-zinc-500">{{ $p->vendor->contact_person ?? 'NO CONTACT' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $p->date->format('d M, Y') }}</p>
                            <p class="text-xs text-zinc-500">{{ $p->date->format('l') }}</p>
                        </td>
                        <td class="px-6 py-4 font-jetbrains font-medium text-rose-600 dark:text-rose-400 text-right">
                            <x-currency :amount="$p->amount" />
                            @if(($p->entry_count ?? 1) > 1)
                                <span class="block text-[10px] font-sans font-normal text-zinc-400 dark:text-zinc-500 mt-0.5">
                                    ({{ $p->entry_count }} bills consolidated)
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($p->payment_mode === 'Advance Adjustment')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                    <span class="material-symbols-rounded text-[14px]">local_shipping</span>
                                    Advance Adj
                                </span>
                            @elseif($p->payment_mode === 'Split')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-indigo-100 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                    Split
                                </span>
                            @else
                                <x-badge variant="zinc">{{ $p->payment_mode }}</x-badge>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col items-start gap-0.5">
                                @if ($p->payment_mode === 'Advance Adjustment')
                                    <span class="text-xs font-semibold text-amber-700 dark:text-amber-400 flex items-center gap-1">
                                        <span class="material-symbols-rounded text-[14px]">savings</span>
                                        {{ $p->notes ?: 'Adjusted from Advance' }}
                                    </span>
                                @else
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
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right font-jetbrains font-medium text-zinc-900 dark:text-zinc-100">
                            <x-currency :amount="$p->pending_balance_after" />
                        </td>
                        <td class="px-6 py-4 text-center">
                            <x-button variant="outline" href="{{ route('payments.vendors.ledger', $p->vendor_id) }}" size="sm">
                                Ledger
                            </x-button>
                        </td>
                    </tr>
                @empty
                    <x-slot:empty>
                        <x-empty-state 
                            icon="account_balance_wallet" 
                            title="No Payouts Recorded" 
                            description="Ready to clear your vendor balances?" />
                    </x-slot:empty>
                @endforelse

                @if($payments->hasPages())
                    <x-slot:pagination>
                        {{ $payments->withQueryString()->links() }}
                    </x-slot:pagination>
                @endif
            </x-data-table>
        @else
            {{-- Tab 2: Advances Table --}}
            <x-data-table :headers="['Vendor', 'Advance Date', 'Total Advance', 'Funding Breakdown', 'Adjusted in Day-Load', 'Remaining Advance', 'Status', 'Actions']">
                @forelse($advances as $adv)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <x-avatar :name="$adv->vendor->firm_name ?? '?'" size="sm" />
                                <div>
                                    <p class="font-cabinet font-bold text-zinc-900 dark:text-zinc-100">{{ $adv->vendor->firm_name ?? '-' }}</p>
                                    <p class="font-outfit text-xs text-zinc-500">{{ $adv->vendor->contact_person ?? 'NO CONTACT' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-600 dark:text-zinc-400">
                            {{ $adv->date->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-jetbrains font-bold text-sm text-zinc-900 dark:text-zinc-100">
                            Rs {{ number_format($adv->total_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs">
                            <div class="flex flex-col gap-0.5 font-jetbrains">
                                @if($adv->cash_amount > 0)
                                    <span class="text-emerald-600 dark:text-emerald-400">Cash: Rs {{ number_format($adv->cash_amount, 2) }}</span>
                                @endif
                                @if($adv->bank_amount > 0)
                                    <span class="text-blue-600 dark:text-blue-400">Bank: Rs {{ number_format($adv->bank_amount, 2) }}</span>
                                @endif
                                @if($adv->investment_amount > 0)
                                    <span class="text-purple-600 dark:text-purple-400">Investment: Rs {{ number_format($adv->investment_amount, 2) }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-jetbrains font-medium text-xs text-zinc-600 dark:text-zinc-400">
                            Rs {{ number_format($adv->adjusted_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-jetbrains font-bold text-sm text-amber-600 dark:text-amber-400">
                            Rs {{ number_format($adv->remaining_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($adv->status === 'Fully Adjusted')
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300">
                                    Fully Adjusted
                                </span>
                            @elseif($adv->status === 'Partially Adjusted')
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-300">
                                    Partially Adjusted
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300">
                                    Pending
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            @if($adv->adjusted_amount == 0)
                                <form action="{{ route('payments.vendors.advances.destroy', $adv) }}" method="POST" class="inline" onsubmit="return confirm('Delete this vendor advance? Balances will be restored.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 text-zinc-400 hover:text-rose-600 rounded-lg transition-colors" title="Delete Advance">
                                        <span class="material-symbols-rounded text-[18px]">delete</span>
                                    </button>
                                </form>
                            @else
                                <span class="text-zinc-400 text-[11px] italic">In Use (Adjusted)</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-slot:empty>
                        <x-empty-state 
                            icon="local_shipping" 
                            title="No Vendor Advances Found" 
                            description="Click '+ Record Advance' to issue advance funds for vendors." />
                    </x-slot:empty>
                @endforelse

                @if($advances->hasPages())
                    <x-slot:pagination>
                        {{ $advances->withQueryString()->links() }}
                    </x-slot:pagination>
                @endif
            </x-data-table>
        @endif
    </x-card>

    {{-- Record Vendor Advance Multi-Source Modal --}}
    <div x-show="advanceModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-black/60 backdrop-blur-sm" @click="advanceModalOpen = false"></div>

            <div class="relative inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl border border-zinc-200 dark:border-zinc-800">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-zinc-100 dark:border-zinc-800">
                    <h3 class="text-lg font-bold font-cabinet text-zinc-900 dark:text-zinc-100">
                        Record Vendor Advance
                    </h3>
                    <button type="button" @click="advanceModalOpen = false" class="text-zinc-400 hover:text-zinc-600">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>

                <form action="{{ route('payments.vendors.advances.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold uppercase text-zinc-500 mb-1">Select Vendor</label>
                        <select name="vendor_id" x-model="advVendorId" required class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 px-3 py-2 text-sm font-semibold">
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}">{{ $v->firm_name }} ({{ $v->contact_person ?? 'No Contact' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-zinc-500 mb-1">Advance Date</label>
                        <input type="date" name="date" x-model="advDate" required class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 px-3 py-2 text-sm">
                    </div>

                    {{-- Multi-Source Funding Split Section --}}
                    <div class="p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700 space-y-3">
                        <div class="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 flex items-center justify-between">
                            <span>Funding Source Split</span>
                            <span class="text-amber-600 dark:text-amber-400 font-mono text-sm font-black">Total: Rs <span x-text="totalAdvance"></span></span>
                        </div>

                        {{-- Cash Input --}}
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <label class="font-bold text-zinc-600 dark:text-zinc-400">💵 From Cash</label>
                                <span class="text-[11px] text-zinc-400">Available: Rs {{ number_format($currentCashBalance, 2) }}</span>
                            </div>
                            <input type="number" step="0.01" min="0" name="cash_amount" x-model="advCash" placeholder="0.00" class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm font-jetbrains font-bold">
                        </div>

                        {{-- Bank Input --}}
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <label class="font-bold text-zinc-600 dark:text-zinc-400">🏦 From Bank</label>
                                <span class="text-[11px] text-zinc-400">Available: Rs {{ number_format($currentBankBalance, 2) }}</span>
                            </div>
                            <input type="number" step="0.01" min="0" name="bank_amount" x-model="advBank" placeholder="0.00" class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm font-jetbrains font-bold">
                        </div>

                        {{-- Investment Input --}}
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <label class="font-bold text-zinc-600 dark:text-zinc-400">🏛️ From Investment Pool</label>
                                <span class="text-[11px] text-zinc-400">Available: Rs {{ number_format($currentInvestmentBalance, 2) }}</span>
                            </div>
                            <input type="number" step="0.01" min="0" name="investment_amount" x-model="advInvestment" placeholder="0.00" class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm font-jetbrains font-bold">
                        </div>
                    </div>

                    {{-- Bank Channel (when Bank is funded) --}}
                    <div x-show="parseFloat(advBank) > 0" x-cloak class="p-3 rounded-xl bg-blue-50/60 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800">
                        <label class="block text-xs font-bold uppercase text-blue-700 dark:text-blue-300 mb-1">Bank Transfer Channel / Type</label>
                        <select name="bank_transfer_type" x-model="advBankType" class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm">
                            <option value="UPI">UPI (GPay / PhonePe / Paytm)</option>
                            <option value="NEFT">NEFT</option>
                            <option value="IMPS">IMPS</option>
                            <option value="RTGS">RTGS</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Other">Other Bank Transfer</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-zinc-500 mb-1">Reference / UTR Number</label>
                        <input type="text" name="reference_number" x-model="advRef" placeholder="Transaction ref or UTR..." class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 px-3 py-2 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-zinc-500 mb-1">Notes</label>
                        <textarea name="notes" x-model="advNotes" rows="2" placeholder="e.g. Advance paid for lorry loading" class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 px-3 py-2 text-sm"></textarea>
                    </div>

                    <div class="pt-2 flex gap-3">
                        <button type="button" @click="advanceModalOpen = false" class="flex-1 px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 font-bold text-xs">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-md">
                            Record Advance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

