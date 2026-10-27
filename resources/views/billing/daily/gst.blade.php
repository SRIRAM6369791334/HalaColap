@extends('layouts.app')
@section('title', 'GST Billing Overview')

@section('content')
<div class="mb-6">
    <a href="{{ route('billing.daily.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 uppercase tracking-wider mb-2 inline-block">← Back to Daily Billing</a>
    <h1 class="text-2xl font-bold text-zinc-950">GST Billing & Tax Logs</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Filterable view specifically for tax evaluation and GST reporting</p>
</div>

<div class="bg-gradient-to-br from-white via-emerald-50/40 to-sky-50/40 rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-zinc-200 bg-gradient-to-r from-emerald-50/80 to-sky-50/80 flex justify-between items-center">
        <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-widest">Taxable Invoices</h3>
        <button class="text-xs font-bold text-emerald-600 hover:underline">Download GSTR-1 Helper (Excel)</button>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b border-zinc-200 bg-zinc-50/20">
                    <th class="px-5 py-3 text-xs font-semibold text-zinc-400 uppercase tracking-wider">Date</th>
                    <th class="px-5 py-3 text-xs font-semibold text-zinc-400 uppercase tracking-wider">Customer / GSTIN</th>
                    <th class="px-5 py-3 text-xs font-semibold text-zinc-400 uppercase tracking-wider">Description</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-zinc-400 uppercase tracking-wider">Taxable Value</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-zinc-400 uppercase tracking-wider">GST (5%)</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-zinc-400 uppercase tracking-wider">Total Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse($bills as $bill)
                    <tr class="hover:bg-gradient-to-r from-emerald-50/70 to-sky-50/70 transition-colors">
                        <td class="px-5 py-4 font-semibold text-zinc-950">{{ $bill->date->format('d M Y') }}</td>
                        <td class="px-5 py-4">
                            <p class="font-bold text-zinc-950">{{ $bill->customer->name }}</p>
                            <p class="text-xs text-zinc-500 font-semibold font-mono">{{ $bill->invoice_number }}</p>
                            <p class="text-[10px] text-zinc-400 font-mono">{{ $bill->customer->gst_number ?: 'NO GSTIN' }}</p>
                        </td>
                        <td class="px-5 py-4 text-zinc-500 italic">{{ $bill->items_description }}</td>
                        <td class="px-5 py-4 text-right font-mono text-zinc-600">Rs {{ number_format($bill->amount / 1.05, 2) }}</td>
                        <td class="px-5 py-4 text-right font-mono text-primary">Rs {{ number_format($bill->amount - ($bill->amount / 1.05), 2) }}</td>
                        <td class="px-5 py-4 text-right font-black text-zinc-950">Rs {{ number_format($bill->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-zinc-400 italic">No GST-enabled invoices found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-4 border-t border-zinc-200 italic text-[10px] text-zinc-400 uppercase font-bold tracking-widest text-center">
        Note: Tax values are calculated based on a default 5% Inclusive GST model for MSR BROILERS.
    </div>
</div>
@endsection
