@extends('layouts.app')
@section('title', 'Payment Ledger - ' . $vendor->firm_name)

@section('content')
<div class="space-y-6">
    <div class="mb-4">
        <a href="{{ route('masters.vendors.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
            <span class="material-symbols-rounded text-[20px]">arrow_back</span>
            Back to Directory
        </a>
    </div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <x-avatar name="{{ $vendor->firm_name }}" size="lg" />
            <div>
                <h1 class="text-2xl font-bold font-cabinet text-zinc-900 dark:text-zinc-100 tracking-tight">{{ $vendor->firm_name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <x-badge color="blue">Vendor / Supplier</x-badge>
                    <x-badge color="zinc">
                        <span class="material-symbols-rounded text-[14px] mr-1">alt_route</span>
                        {{ $vendor->route ?: 'General Area' }}
                    </x-badge>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-1 space-y-6">
            <div class="rounded-3xl p-6 bg-rose-500/40 dark:bg-rose-900/40 backdrop-blur-2xl text-rose-900 dark:text-rose-100 shadow-[0_8px_32px_rgba(244,63,94,0.15)] border border-rose-300/50 dark:border-rose-700/50 relative overflow-hidden transition-all duration-300 hover:-translate-y-1">
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/20 dark:bg-rose-400/10 rounded-full blur-2xl"></div>
                <div class="absolute -left-10 -bottom-10 w-32 h-32 bg-rose-400/20 dark:bg-rose-600/20 rounded-full blur-2xl"></div>
                <div class="relative z-10 text-center">
                    <div class="inline-flex items-center justify-center px-3 py-1 mb-3 text-[10px] font-bold uppercase tracking-[0.2em] text-rose-700 dark:text-rose-300 bg-rose-500/10 dark:bg-rose-500/20 rounded-full border border-rose-500/20">
                        Outstanding Payable
                    </div>
                    <div class="text-4xl font-black tracking-tight font-cabinet mb-8 text-rose-950 dark:text-white drop-shadow-sm flex items-baseline justify-center gap-1">
                        <span class="text-xl font-bold text-rose-800/60 dark:text-rose-200/60">₹</span>
                        {{ number_format($vendor->outstanding_balance, 2) }}
                    </div>
                    
                    <form action="{{ route('payments.vendors.payments.store', $vendor) }}" method="POST" class="text-left space-y-4 bg-white/80 dark:bg-zinc-900/80 p-5 rounded-[1.25rem] backdrop-blur-xl shadow-sm border border-white/50 dark:border-zinc-700/50" x-data="{ cashAmount: 0, bankAmount: 0 }">
                        @csrf
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-extrabold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 pl-1">Date</label>
                            <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 rounded-xl border border-zinc-200/80 dark:border-zinc-700/80 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 bg-white/50 dark:bg-zinc-800/50 transition-all duration-300 shadow-sm outline-none">
                        </div>
                        
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-extrabold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 pl-1">Cash Amount</label>
                            <input type="number" name="cash_amount" min="0" step="0.01" placeholder="0.00" x-model.number="cashAmount" required class="w-full px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 rounded-xl border border-zinc-200/80 dark:border-zinc-700/80 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 bg-white/50 dark:bg-zinc-800/50 transition-all duration-300 shadow-sm outline-none">
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-extrabold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 pl-1">Bank Amount</label>
                            <input type="number" name="bank_amount" min="0" step="0.01" placeholder="0.00" x-model.number="bankAmount" required class="w-full px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 rounded-xl border border-zinc-200/80 dark:border-zinc-700/80 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 bg-white/50 dark:bg-zinc-800/50 transition-all duration-300 shadow-sm outline-none">
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-extrabold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 pl-1">Total</label>
                            <div class="w-full px-4 py-2.5 text-sm font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/20 rounded-xl border border-rose-200 dark:border-rose-900 shadow-sm" x-text="'₹ ' + (cashAmount + bankAmount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-extrabold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 pl-1">Payment Mode</label>
                            <select name="payment_mode" required class="w-full px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 rounded-xl border border-zinc-200/80 dark:border-zinc-700/80 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 bg-white/50 dark:bg-zinc-800/50 transition-all duration-300 shadow-sm outline-none">
                                <option value="Cash">Cash</option>
                                <option value="UPI">UPI</option>
                                <option value="NEFT">NEFT</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>

                        <div class="space-y-1.5" x-show="bankAmount > 0" x-transition>
                            <label class="text-[11px] font-extrabold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 pl-1">Bank Transfer Type</label>
                            <select name="bank_transfer_type" :required="bankAmount > 0" class="w-full px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 rounded-xl border border-zinc-200/80 dark:border-zinc-700/80 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 bg-white/50 dark:bg-zinc-800/50 transition-all duration-300 shadow-sm outline-none">
                                <option value="">Select type...</option>
                                <option value="UPI">UPI</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="NEFT">NEFT</option>
                                <option value="RTGS">RTGS</option>
                                <option value="IMPS">IMPS</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="space-y-1.5 mb-2">
                            <label class="text-[11px] font-extrabold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 pl-1">Notes (Optional)</label>
                            <input type="text" name="notes" placeholder="Reference number or remarks" class="w-full px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 rounded-xl border border-zinc-200/80 dark:border-zinc-700/80 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 bg-white/50 dark:bg-zinc-800/50 transition-all duration-300 shadow-sm outline-none placeholder:text-zinc-400">
                        </div>

                        <button type="submit" class="w-full mt-2 py-3 px-4 bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-500 hover:to-rose-400 text-white text-sm font-bold rounded-xl shadow-[0_4px_14px_0_rgba(225,29,72,0.39)] hover:shadow-[0_6px_20px_rgba(225,29,72,0.23)] hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center gap-2">
                            <span class="material-symbols-rounded text-[20px]">payments</span>
                            Record Payment
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white/30 dark:bg-zinc-900/40 backdrop-blur-2xl border border-white/60 dark:border-zinc-800/80 rounded-[2rem] overflow-hidden shadow-[0_8px_32px_rgba(31,38,135,0.07)] z-10 relative">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider">Transaction Ledger</h4>
                        <button onclick="window.print()" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 flex items-center gap-1">
                            <span class="material-symbols-rounded text-[16px]">print</span> Print
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-xs text-zinc-500 uppercase tracking-wider border-b border-zinc-200 dark:border-zinc-700/50">
                                    <th class="px-4 py-3 font-semibold">Date</th>
                                    <th class="px-4 py-3 font-semibold">Type</th>
                                    <th class="px-4 py-3 font-semibold">Reference</th>
                                    <th class="px-4 py-3 font-semibold text-right text-rose-600 dark:text-rose-400">Purchase (Dr)</th>
                                    <th class="px-4 py-3 font-semibold text-right text-emerald-600 dark:text-emerald-400">Payment (Cr)</th>
                                    <th class="px-4 py-3 font-semibold text-center w-12 no-print"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $txn)
                                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 border-b border-zinc-100 dark:border-zinc-800">
                                        <td class="px-4 py-3 text-sm font-medium">{{ $txn->date->format('d M Y') }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            @if($txn->type === 'Purchase')
                                                <x-badge color="rose">{{ $txn->type }}</x-badge>
                                            @elseif($txn->type === 'Day-Load')
                                                <x-badge color="blue">{{ $txn->type }}</x-badge>
                                            @else
                                                <x-badge color="emerald">{{ $txn->type }}</x-badge>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ $txn->reference }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right font-jetbrains font-bold text-rose-600 dark:text-rose-400">
                                            @if($txn->type === 'Purchase' && $txn->is_credit)
                                                ₹{{ number_format($txn->amount, 2) }}
                                            @elseif($txn->type === 'Day-Load')
                                                {{ number_format($txn->amount, 1) }} kg
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right font-jetbrains font-bold text-emerald-600 dark:text-emerald-400">
                                            @if($txn->type === 'Payment' || ($txn->type === 'Purchase' && !$txn->is_credit))
                                                ₹{{ number_format($txn->amount, 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center no-print">
                                            @if($txn->type === 'Payment')
                                                <form action="{{ route('payments.vendors.payments.destroy', [$vendor, $txn->id]) }}" method="POST" onsubmit="return confirm('Delete payment?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-rose-500 hover:text-rose-700">
                                                        <span class="material-symbols-rounded text-[18px]">delete</span>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-8 text-zinc-500 text-sm">No transactions found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
@media print {
    body { background: white !important; }
    nav, aside, header, .no-print { display: none !important; }
    .shadow-sm, .shadow-\[0_8px_32px_rgba\(31\,38\,135\,0\.07\)\] { border: none !important; box-shadow: none !important; }
}
</style>
@endsection
