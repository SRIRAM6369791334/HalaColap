@extends('layouts.app')
@section('title', "Ledger Details - $date")

@section('content')
<div class="animate-fade-in">
    <x-page-header title="Ledger Details" :subtitle="Carbon\Carbon::parse($date)->format('l, d M Y')">
        <x-slot:actions>
            @if(!$ledger->is_approved)
                <form method="POST" action="{{ route('billing.cash-bank-ledger.approve', $ledger) }}" class="inline" onsubmit="return confirm('Approve ledger for {{ Carbon\Carbon::parse($date)->format('d M Y') }}? This will sweep closing cash of Rs {{ number_format((float) $ledger->closing_cash_balance, 0) }} into the bank account and is irreversible.')">
                    @csrf
                    <x-button type="submit" variant="primary" icon="check_circle">
                        Approve Day
                    </x-button>
                </form>
            @endif
            <x-button variant="outline" href="{{ route('payments.dealers.create') }}" icon="payments" target="_blank">
                Add Dealer Payment
            </x-button>
            <x-button variant="outline" href="{{ route('expenses.create') }}" icon="money_off" target="_blank">
                Add Expense
            </x-button>
            <x-button variant="outline" href="{{ route('billing.cash-bank-ledger.index') }}" icon="arrow_back">
                Back
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Summary Cards (3 in a row) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-8">
        <x-stat-card label="Cash Income" value="Rs {{ number_format((float) $ledger->cash_income, 0) }}" icon="payments" color="emerald" />
        <x-stat-card label="Bank Income" value="Rs {{ number_format((float) $ledger->bank_income, 0) }}" icon="account_balance" color="blue" />
        <x-stat-card label="Cash Expense" value="Rs {{ number_format((float) $ledger->cash_expense, 0) }}" icon="money_off" color="rose" />
        <x-stat-card label="Bank Expense" value="Rs {{ number_format((float) $ledger->bank_expense, 0) }}" icon="account_balance" color="amber" />
        <x-stat-card label="Operating Cash & Bank" value="Rs {{ number_format((float) $ledger->closing_cash_balance + (float) $ledger->closing_bank_balance, 0) }}" icon="account_balance_wallet" color="indigo" subtitle="In Hand + Bank" />
        <x-stat-card label="Capital Pool Reserve" value="Rs {{ number_format((float) $currentInvestmentBalance, 0) }}" icon="savings" color="purple" subtitle="Liquid Reserve" />
    </div>

    {{-- 3-Way Summary: Cash, Bank, and Capital Pool / Advances --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- Cash Summary --}}
        <div class="p-5 rounded-2xl bg-gradient-to-br from-emerald-50 to-emerald-100/50 dark:from-emerald-900/20 dark:to-emerald-800/10 border border-emerald-200/50 dark:border-emerald-800/30 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <span class="material-symbols-rounded text-emerald-600 text-[24px]">payments</span>
                    <h3 class="font-cabinet font-bold text-emerald-800 dark:text-emerald-300">Cash Summary</h3>
                </div>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600/70 mb-1">Opening</p>
                        <p class="font-jetbrains font-bold text-emerald-700 dark:text-emerald-300">Rs {{ number_format((float) $ledger->opening_cash_balance, 0) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600/70 mb-1">Income</p>
                        <p class="font-jetbrains font-bold text-emerald-600">+ Rs {{ number_format((float) $ledger->cash_income, 0) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600/70 mb-1">Expenses</p>
                        <p class="font-jetbrains font-bold text-rose-600">- Rs {{ number_format((float) $ledger->cash_expense, 0) }}</p>
                    </div>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-emerald-200/50 dark:border-emerald-800/30 flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300 uppercase">Closing Cash in Hand</span>
                <span class="font-jetbrains font-black text-xl text-emerald-600">Rs {{ number_format((float) $ledger->closing_cash_balance, 2) }}</span>
            </div>
        </div>

        {{-- Bank Summary --}}
        <div class="p-5 rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100/50 dark:from-blue-900/20 dark:to-blue-800/10 border border-blue-200/50 dark:border-blue-800/30 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <span class="material-symbols-rounded text-blue-600 text-[24px]">account_balance</span>
                    <h3 class="font-cabinet font-bold text-blue-800 dark:text-blue-300">Bank Summary</h3>
                </div>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-blue-600/70 mb-1">Opening</p>
                        <p class="font-jetbrains font-bold text-blue-700 dark:text-blue-300">Rs {{ number_format((float) $ledger->opening_bank_balance, 0) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-blue-600/70 mb-1">Income</p>
                        <p class="font-jetbrains font-bold text-blue-600">+ Rs {{ number_format((float) $ledger->bank_income, 0) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-blue-600/70 mb-1">Expenses</p>
                        <p class="font-jetbrains font-bold text-rose-600">- Rs {{ number_format((float) $ledger->bank_expense, 0) }}</p>
                    </div>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-blue-200/50 dark:border-blue-800/30 flex items-center justify-between">
                <span class="text-xs font-bold text-blue-800 dark:text-blue-300 uppercase">Closing Bank Balance</span>
                <span class="font-jetbrains font-black text-xl text-blue-600">Rs {{ number_format((float) $ledger->closing_bank_balance, 2) }}</span>
            </div>
        </div>

        {{-- Capital & Vendor Advances Summary --}}
        <div class="p-5 rounded-2xl bg-gradient-to-br from-indigo-50 via-purple-50/40 to-amber-50/40 dark:from-indigo-900/20 dark:via-purple-900/10 dark:to-amber-900/10 border border-indigo-200/50 dark:border-indigo-800/30 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-rounded text-indigo-600 text-[24px]">savings</span>
                        <h3 class="font-cabinet font-bold text-indigo-900 dark:text-indigo-200">Capital & Advances</h3>
                    </div>
                    <a href="{{ route('billing.investments.index') }}" class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline uppercase">View Pool</a>
                </div>
                <div class="space-y-1.5 text-xs">
                    <div class="flex items-center justify-between text-zinc-600 dark:text-zinc-300">
                        <span>🏛️ Capital Pool Balance:</span>
                        <strong class="font-jetbrains text-indigo-700 dark:text-indigo-300">Rs {{ number_format($currentInvestmentBalance, 2) }}</strong>
                    </div>
                    <div class="flex items-center justify-between text-zinc-600 dark:text-zinc-300">
                        <span>🚚 Advances Paid Today:</span>
                        <strong class="font-jetbrains text-amber-600 dark:text-amber-400">Rs {{ number_format($dayAdvancesTotal, 2) }}</strong>
                    </div>
                    @if($dayInvestments > 0)
                        <div class="flex items-center justify-between text-purple-700 dark:text-purple-300">
                            <span>📥 New Capital Invested:</span>
                            <strong class="font-jetbrains">+ Rs {{ number_format($dayInvestments, 2) }}</strong>
                        </div>
                    @endif
                    @if($dayTransfersToBusiness > 0)
                        <div class="flex items-center justify-between text-emerald-600">
                            <span>➡️ Injected to Operating:</span>
                            <strong class="font-jetbrains">Rs {{ number_format($dayTransfersToBusiness, 2) }}</strong>
                        </div>
                    @endif
                    @if($dayTransfersFromBusiness > 0)
                        <div class="flex items-center justify-between text-teal-600">
                            <span>⬅️ Surplus Moved to Pool:</span>
                            <strong class="font-jetbrains">Rs {{ number_format($dayTransfersFromBusiness, 2) }}</strong>
                        </div>
                    @endif
                    @if($dayDrawings > 0)
                        <div class="flex items-center justify-between text-rose-700 dark:text-rose-300 bg-rose-50/90 dark:bg-rose-950/60 p-2.5 rounded-xl border border-rose-200 dark:border-rose-800 mt-1 shadow-xs">
                            <span class="font-bold flex items-center gap-1.5">
                                <span class="material-symbols-rounded text-rose-600 text-base">money_off</span>
                                Owner Drawings / Withdrawn:
                            </span>
                            <strong class="font-jetbrains font-black text-rose-600 dark:text-rose-400 text-sm">- Rs {{ number_format($dayDrawings, 2) }}</strong>
                        </div>
                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400 italic pl-1">
                            * Drawn directly from Capital Pool Reserve (Operating Cash & Bank are unaffected).
                        </p>
                    @endif
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-indigo-200/50 dark:border-indigo-800/30 flex items-center justify-between">
                <span class="text-[11px] font-black text-indigo-900 dark:text-indigo-200 uppercase">💎 Total Liquidity</span>
                <span class="font-jetbrains font-black text-lg text-indigo-700 dark:text-indigo-300">
                    Rs {{ number_format((float) $ledger->closing_cash_balance + (float) $ledger->closing_bank_balance + (float) $currentInvestmentBalance, 2) }}
                </span>
            </div>
        </div>
    </div>

    {{-- Dealer Payments --}}
    <x-card class="mb-6">
        <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50">
            <h2 class="font-cabinet text-lg font-bold text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                <span class="material-symbols-rounded text-emerald-500 text-[20px]">payments</span>
                Dealer Payments
                <span class="ml-auto text-sm font-normal text-zinc-500">
                    Cash: Rs {{ number_format($dealerPayments->sum('cash_amount'), 0) }} |
                    Bank: Rs {{ number_format($dealerPayments->sum('bank_amount'), 0) }} |
                    Total: Rs {{ number_format($dealerPayments->sum('cash_amount') + $dealerPayments->sum('bank_amount'), 0) }}
                </span>
            </h2>
        </div>
        @if(isset($dealerAdjustment) && $dealerAdjustment > 0)
        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-200 dark:border-amber-800/30">
            <div class="flex items-center gap-2 text-amber-800 dark:text-amber-400 font-bold mb-2">
                <span class="material-symbols-rounded text-[20px]">info</span>
                Dealer Adjustment (Stock Sold to Customers)
            </div>
            <p class="text-sm text-amber-700 dark:text-amber-300 mb-2">
                <strong>Why this adjustment?</strong> When stock is transferred from a Dealer and sold directly to a Customer, the payment is recorded under <strong>Customer Payments</strong>. To avoid counting the same stock's value twice, the Dealer's original cost for this transferred stock (<strong>Rs {{ number_format($dealerAdjustment, 0) }}</strong>) is automatically deducted from the Dealer's Income (Cash & Bank).
            </p>
            <div class="mt-3 bg-white/50 dark:bg-black/20 rounded-lg overflow-hidden border border-amber-200/50 dark:border-amber-700/50">
                <table class="w-full text-left text-xs">
                    <thead class="bg-amber-100/50 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">
                        <tr>
                            <th class="px-4 py-2 font-semibold">Dealer -> Customer (Stock Transfer)</th>
                            <th class="px-4 py-2 font-semibold text-right">Dealer Stock Cost</th>
                            <th class="px-4 py-2 font-semibold text-right">Customer Paid</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-amber-100 dark:divide-amber-800/30">
                        @foreach($adjustmentDetails as $adj)
                        <tr>
                            <td class="px-4 py-2">
                                <span class="font-medium">{{ $adj->dealer }}</span> 
                                <span class="material-symbols-rounded text-[14px] align-middle mx-1 text-amber-500">arrow_right_alt</span> 
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $adj->customer_name ?? 'Customer' }}</span>
                                <div class="text-[10px] text-amber-600/70 mt-0.5">Inv: {{ $adj->invoice }} • {{ $adj->qty }} kg</div>
                            </td>
                            <td class="px-4 py-2 text-right">
                                <div class="font-jetbrains font-semibold text-rose-600">- Rs {{ number_format($adj->amount, 0) }}</div>
                                <div class="text-[10px] text-amber-600/70 mt-0.5">@ Rs {{ number_format($adj->rate, 2) }}/kg</div>
                            </td>
                            <td class="px-4 py-2 text-right">
                                <div class="font-jetbrains font-semibold text-emerald-600">+ Rs {{ number_format($adj->customer_amount ?? 0, 0) }}</div>
                                <div class="text-[10px] text-amber-600/70 mt-0.5">Added to Income</div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-amber-50/50 dark:bg-amber-900/20 border-t border-amber-200/50 dark:border-amber-700/50">
                        @php
                            $totalDealerCost = collect($adjustmentDetails)->sum('amount');
                            $totalCustomerPaid = collect($adjustmentDetails)->sum('customer_amount');
                            $netDifference = $totalCustomerPaid - $totalDealerCost;
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-bold text-amber-800 dark:text-amber-300">Total Calculation</td>
                            <td class="px-4 py-3 text-right">
                                <div class="font-jetbrains font-bold text-rose-600">- Rs {{ number_format($totalDealerCost, 0) }}</div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="font-jetbrains font-bold text-emerald-600">+ Rs {{ number_format($totalCustomerPaid, 0) }}</div>
                                <div class="text-[11px] font-bold mt-1 px-2 py-1 rounded inline-block {{ $netDifference >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400' }}">
                                    Net Impact: {{ $netDifference >= 0 ? '+' : '' }}Rs {{ number_format($netDifference, 0) }}
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif
        @if($dealerPayments->isEmpty())
            <x-empty-state icon="payments" title="No dealer payments" description="No dealer payments recorded for this date." />
        @else
            <x-data-table :headers="['Dealer', 'Cash', 'Bank Transfer', 'Mode', 'Reference']">
                @foreach($dealerPayments as $p)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">{{ $p->dealer?->firm_name ?? 'Unknown' }}</td>
                        <td class="px-6 py-4">
                            @if((float) $p->cash_amount > 0)
                                <span class="font-jetbrains font-bold text-emerald-600">Rs {{ number_format((float) $p->cash_amount, 0) }}</span>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if((float) $p->bank_amount > 0)
                                <span class="font-jetbrains font-bold text-blue-600">Rs {{ number_format((float) $p->bank_amount, 0) }}</span>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <x-badge variant="{{ $p->payment_mode === 'Cash' ? 'emerald' : ($p->payment_mode === 'Split' ? 'amber' : 'blue') }}">
                                {{ $p->payment_mode }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-500">{{ $p->reference_number ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    {{-- Day-Load Entries --}}
    <x-card class="mb-6">
        <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50">
            <h2 class="font-cabinet text-lg font-bold text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                <span class="material-symbols-rounded text-amber-500 text-[20px]">local_shipping</span>
                Day-Load Entries
                @php
                    $displayEntries = (isset($dayLoadEntries) && $dayLoadEntries->isNotEmpty()) ? $dayLoadEntries : ($dayLoadBatch ? $dayLoadBatch->entries : collect());
                @endphp
                @if($displayEntries->isNotEmpty())
                    <span class="ml-auto text-sm font-normal text-zinc-500">
                        {{ $displayEntries->count() }} entries |
                        {{ number_format($displayEntries->sum('no_of_boxes')) }} boxes |
                        {{ number_format($displayEntries->sum('bird_weight'), 1) }} kg
                    </span>
                @endif
            </h2>
        </div>
        @if($displayEntries->isEmpty())
            <x-empty-state icon="local_shipping" title="No day-load entries" description="No day-load activity recorded for this date." />
        @else
            <x-data-table :headers="['#', 'Vendor', 'Dealer', 'Boxes', 'Weight', 'Dealer Income', 'Collected', 'Status']">
                @foreach($displayEntries as $e)
                    @php
                        $income = (float) $e->bird_weight * (float) $e->billing_rate;
                        $collected = (float) $e->dealer_collected;
                        $remaining = $income - $collected;
                    @endphp
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                        <td class="px-6 py-4 font-mono text-xs">
                            @if($e->sort_order)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-black bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300 dark:border-amber-700">#{{ $e->sort_order }}</span>
                            @else
                                <span class="text-zinc-400 dark:text-zinc-500 font-jetbrains">{{ $loop->iteration }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">{{ $e->vendor?->firm_name ?? 'Unknown' }}</td>
                        <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300 font-semibold">{{ $e->dealer?->firm_name ?? 'Unknown' }}</td>
                        <td class="px-6 py-4 font-jetbrains text-zinc-600 dark:text-zinc-400">{{ number_format((float) $e->no_of_boxes) }}</td>
                        <td class="px-6 py-4 font-jetbrains text-zinc-600 dark:text-zinc-400">{{ number_format((float) $e->bird_weight, 1) }} kg</td>
                        <td class="px-6 py-4 font-jetbrains font-bold text-zinc-900 dark:text-zinc-100">Rs {{ number_format($income, 0) }}</td>
                        <td class="px-6 py-4 font-jetbrains {{ $collected > 0 ? 'text-emerald-600' : 'text-zinc-400' }}">
                            Rs {{ number_format($collected, 0) }}
                            @if($remaining > 0)
                                <span class="text-rose-500 text-[11px] block">(Due: Rs {{ number_format($remaining, 0) }})</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $status = $e->dealer_payment_status ?? 'Pending';
                                $variant = match($status) {
                                    'Paid' => 'success',
                                    'Partial' => 'warning',
                                    'Overpaid' => 'info',
                                    default => 'zinc',
                                };
                            @endphp
                            <x-badge :variant="$variant">{{ $status }}</x-badge>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    {{-- Customer Payments --}}
    <x-card class="mb-6">
        <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50">
            <h2 class="font-cabinet text-lg font-bold text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                <span class="material-symbols-rounded text-blue-500 text-[20px]">credit_card</span>
                Customer Payments
                <span class="ml-auto text-sm font-normal text-zinc-500">
                    Cash: Rs {{ number_format($customerPayments->sum('cod_amount'), 0) }} |
                    Bank: Rs {{ number_format($customerPayments->sum('bank_transfer_amount'), 0) }} |
                    Total: Rs {{ number_format($customerPayments->sum('cod_amount') + $customerPayments->sum('bank_transfer_amount'), 0) }}
                </span>
            </h2>
        </div>
        @if($customerPayments->isEmpty())
            <x-empty-state icon="credit_card" title="No customer payments" description="No customer payments recorded for this date." />
        @else
            <x-data-table :headers="['Customer', 'Cash (COD)', 'Bank Transfer', 'Mode', 'Type']">
                @foreach($customerPayments as $p)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">{{ $p->customer?->name ?? 'Unknown' }}</td>
                        <td class="px-6 py-4">
                            @if((float) $p->cod_amount > 0)
                                <span class="font-jetbrains font-bold text-emerald-600">Rs {{ number_format((float) $p->cod_amount, 0) }}</span>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if((float) $p->bank_transfer_amount > 0)
                                <span class="font-jetbrains font-bold text-blue-600">Rs {{ number_format((float) $p->bank_transfer_amount, 0) }}</span>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <x-badge variant="{{ $p->payment_mode === 'Cash' ? 'emerald' : 'blue' }}">
                                {{ $p->payment_mode }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-500">{{ $p->payment_type ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    {{-- Vendor Payments --}}
    <x-card class="mb-6">
        <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50">
            <h2 class="font-cabinet text-lg font-bold text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                <span class="material-symbols-rounded text-purple-500 text-[20px]">local_shipping</span>
                Vendor Payments
                <span class="ml-auto text-sm font-normal text-zinc-500">
                    Cash: Rs {{ number_format($vendorPayments->sum('cash_amount'), 0) }} |
                    Bank: Rs {{ number_format($vendorPayments->sum('bank_amount'), 0) }} |
                    Total: Rs {{ number_format($vendorPayments->sum('cash_amount') + $vendorPayments->sum('bank_amount'), 0) }}
                </span>
            </h2>
        </div>
        @if($vendorPayments->isEmpty())
            <x-empty-state icon="local_shipping" title="No vendor payments" description="No vendor payments recorded for this date." />
        @else
            <x-data-table :headers="['Vendor', 'Cash', 'Bank Transfer', 'Mode', 'Reference']">
                @foreach($vendorPayments as $p)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">{{ $p->vendor?->firm_name ?? 'Unknown' }}</td>
                        <td class="px-6 py-4">
                            @if((float) $p->cash_amount > 0)
                                <span class="font-jetbrains font-bold text-emerald-600">Rs {{ number_format((float) $p->cash_amount, 0) }}</span>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if((float) $p->bank_amount > 0)
                                <span class="font-jetbrains font-bold text-blue-600">Rs {{ number_format((float) $p->bank_amount, 0) }}</span>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <x-badge variant="{{ $p->payment_mode === 'Cash' ? 'emerald' : 'blue' }}">
                                {{ $p->payment_mode }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-500">{{ $p->reference_number ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    {{-- Expenses --}}
    <x-card class="mb-6">
        <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50">
            <h2 class="font-cabinet text-lg font-bold text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                <span class="material-symbols-rounded text-rose-500 text-[20px]">money_off</span>
                Expenses
                <span class="ml-auto text-sm font-normal text-zinc-500">Total: Rs {{ number_format($expenses->sum('amount'), 0) }}</span>
            </h2>
        </div>
        @if($expenses->isEmpty())
            <x-empty-state icon="money_off" title="No expenses" description="No expenses recorded for this date." />
        @else
            <x-data-table :headers="['Description', 'Category', 'Amount', 'Payment Method']">
                @foreach($expenses as $e)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">{{ $e->description ?? '—' }}</td>
                        <td class="px-6 py-4">
                            <x-badge variant="zinc">{{ $e->category ?? 'Uncategorized' }}</x-badge>
                        </td>
                        <td class="px-6 py-4 font-jetbrains font-bold text-rose-600">Rs {{ number_format((float) $e->amount, 0) }}</td>
                        <td class="px-6 py-4 text-sm text-zinc-500">{{ $e->payment_method ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    {{-- Vendor Advances Paid Today --}}
    @if($vendorAdvances->isNotEmpty())
    <x-card class="mb-6">
        <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50">
            <h2 class="font-cabinet text-lg font-bold text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                <span class="material-symbols-rounded text-amber-500 text-[20px]">local_shipping</span>
                Vendor Advances Issued
                <span class="ml-auto text-sm font-normal text-zinc-500">Total: Rs {{ number_format($vendorAdvances->sum('total_amount'), 0) }}</span>
            </h2>
        </div>
        <x-data-table :headers="['Vendor', 'Total Advance', 'Cash Paid', 'Bank Paid', 'Investment Funded', 'Remaining', 'Status']">
            @foreach($vendorAdvances as $adv)
                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                    <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">{{ $adv->vendor->firm_name ?? '—' }}</td>
                    <td class="px-6 py-4 font-jetbrains font-bold text-amber-600">Rs {{ number_format($adv->total_amount, 2) }}</td>
                    <td class="px-6 py-4 font-jetbrains text-xs text-zinc-700 dark:text-zinc-300">Rs {{ number_format($adv->cash_amount, 2) }}</td>
                    <td class="px-6 py-4 font-jetbrains text-xs text-zinc-700 dark:text-zinc-300">Rs {{ number_format($adv->bank_amount, 2) }}</td>
                    <td class="px-6 py-4 font-jetbrains text-xs text-purple-600 dark:text-purple-400">Rs {{ number_format($adv->investment_amount, 2) }}</td>
                    <td class="px-6 py-4 font-jetbrains font-bold text-xs text-zinc-900 dark:text-zinc-100">Rs {{ number_format($adv->remaining_amount, 2) }}</td>
                    <td class="px-6 py-4">
                        <x-badge variant="zinc">{{ $adv->status }}</x-badge>
                    </td>
                </tr>
            @endforeach
        </x-data-table>
    </x-card>
    @endif

    {{-- Capital Inflows & Outflows Today --}}
    @if($capitalInflows->isNotEmpty() || $capitalOutflows->isNotEmpty())
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        @if($capitalInflows->isNotEmpty())
        <x-card>
            <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50">
                <h2 class="font-cabinet text-base font-bold text-emerald-700 dark:text-emerald-400 flex items-center gap-2">
                    <span class="material-symbols-rounded text-emerald-600 text-[18px]">add_circle</span>
                    Capital Movements & Transfers
                    <span class="ml-auto text-xs font-mono">Rs {{ number_format($capitalInflows->sum('amount'), 2) }}</span>
                </h2>
            </div>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800 p-2">
                @foreach($capitalInflows as $cin)
                <div class="p-3 flex items-center justify-between text-xs">
                    <div>
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ $cin->type }}</span>
                            @if($cin->type === 'Investment')
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200 dark:border-purple-800">Capital Pool Reserve</span>
                            @elseif($cin->type === 'Transfer to Cash')
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">Injected to Cash</span>
                            @elseif($cin->type === 'Transfer to Bank')
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800">Injected to Bank</span>
                            @endif
                        </div>
                        <p class="text-[11px] text-zinc-500">{{ $cin->person_name ?: 'Owner' }} • {{ $cin->payment_mode }}</p>
                    </div>
                    <span class="font-jetbrains font-bold text-emerald-600 text-sm">+ Rs {{ number_format($cin->amount, 2) }}</span>
                </div>
                @endforeach
            </div>
        </x-card>
        @endif

        @if($capitalOutflows->isNotEmpty())
        <x-card>
            <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50">
                <h2 class="font-cabinet text-base font-bold text-rose-700 dark:text-rose-400 flex items-center gap-2">
                    <span class="material-symbols-rounded text-rose-600 text-[18px]">money_off</span>
                    Capital Outflows & Drawings
                    <span class="ml-auto text-xs font-mono">Rs {{ number_format($capitalOutflows->sum('amount'), 2) }}</span>
                </h2>
            </div>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800 p-2">
                @foreach($capitalOutflows as $cout)
                <div class="p-3 flex items-center justify-between text-xs">
                    <div>
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ $cout->type }}</span>
                            @if($cout->type === 'Withdrawal')
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">Owner Drawings</span>
                            @elseif($cout->type === 'Transfer from Cash')
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-teal-100 text-teal-800 dark:bg-teal-950/60 dark:text-teal-300 border border-teal-200 dark:border-teal-800">Surplus Cash to Pool</span>
                            @elseif($cout->type === 'Transfer from Bank')
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-cyan-100 text-cyan-800 dark:bg-cyan-950/60 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-800">Surplus Bank to Pool</span>
                            @endif
                        </div>
                        <p class="text-[11px] text-zinc-500">{{ $cout->person_name ?: 'Owner' }} • {{ $cout->payment_mode }}</p>
                    </div>
                    <span class="font-jetbrains font-bold text-rose-600 text-sm">- Rs {{ number_format($cout->amount, 2) }}</span>
                </div>
                @endforeach
            </div>
        </x-card>
        @endif
    </div>
    @endif

    {{-- Balance Summary --}}
    <x-card>
        <div class="p-4">
            <h2 class="font-cabinet text-lg font-bold text-zinc-900 dark:text-zinc-50 mb-4">Balance Summary</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-900">
                    <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 mb-1">Opening Cash</p>
                    <p class="font-jetbrains font-bold text-lg text-zinc-900 dark:text-zinc-100">Rs {{ number_format((float) $ledger->opening_cash_balance, 0) }}</p>
                </div>
                <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-600 mb-1">Closing Cash</p>
                    <p class="font-jetbrains font-bold text-lg text-emerald-600">Rs {{ number_format((float) $ledger->closing_cash_balance, 0) }}</p>
                </div>
                <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-900">
                    <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 mb-1">Opening Bank</p>
                    <p class="font-jetbrains font-bold text-lg text-zinc-900 dark:text-zinc-100">Rs {{ number_format((float) $ledger->opening_bank_balance, 0) }}</p>
                </div>
                <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-900/20">
                    <p class="text-xs font-bold uppercase tracking-wider text-blue-600 mb-1">Closing Bank</p>
                    <p class="font-jetbrains font-bold text-lg text-blue-600">Rs {{ number_format((float) $ledger->closing_bank_balance, 0) }}</p>
                </div>
            </div>
        </div>
    </x-card>
</div>
@endsection
