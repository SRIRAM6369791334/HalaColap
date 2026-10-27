@extends('layouts.app')
@section('title', 'Purchase Invoice #' . $purchase->id)

@section('content')
<div class="animate-fade-in space-y-6">

    <div class="mb-4">
        <a href="{{ route('purchases.invoices') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors w-max">
            <span class="material-symbols-rounded text-[20px]">arrow_back</span>
            Back to Invoices
        </a>
    </div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold font-cabinet text-zinc-900 dark:text-zinc-100 tracking-tight">Purchase Invoice Details</h1>
            <p class="text-sm text-zinc-500 mt-1">Reference ID: {{ $purchase->invoice_no ?? '#PUR' . str_pad($purchase->id, 5, '0', STR_PAD_LEFT) }}</p>
        </div>
        <div class="flex items-center gap-3">
            <x-button href="{{ route('purchases.print', $purchase->id) }}" variant="outline" icon="print" target="_blank">
                Print Invoice
            </x-button>
            @can('edit purchases')
            <x-button href="{{ route('purchases.edit', $purchase->id) }}" variant="secondary" icon="edit" class="!bg-teal-50 !text-teal-700 !border-teal-200 hover:!bg-teal-100 dark:!bg-teal-900/30 dark:!text-teal-400 dark:!border-teal-800/50">
                Edit Entry
            </x-button>
            @endcan
        </div>
    </div>

    <div class="rounded-3xl p-8 bg-white/40 dark:bg-zinc-900/40 backdrop-blur-2xl border border-zinc-200/60 dark:border-zinc-800/60 shadow-[0_8px_32px_rgba(0,0,0,0.04)]">
        
        {{-- 1. Invoice Metadata Header --}}
        <div class="flex flex-wrap justify-between items-start border-b border-zinc-200/60 dark:border-zinc-800/60 pb-6 mb-8 gap-6">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2 text-xl font-black text-emerald-600 tracking-tight">
                    <span class="material-symbols-rounded text-[28px]">layers</span>
                    <span>MSR BROILERS</span>
                </div>
                <p class="text-xs text-zinc-500 uppercase tracking-widest font-semibold">Farm Management & Supply Chain</p>
            </div>
            <div class="flex flex-col items-end text-right">
                <span class="text-xs font-bold text-zinc-400 uppercase tracking-widest mb-1">INVOICE</span>
                <span class="text-2xl font-black font-mono text-zinc-900 dark:text-zinc-100 leading-none">{{ $purchase->invoice_no ?? '#PUR' . str_pad($purchase->id, 5, '0', STR_PAD_LEFT) }}</span>
            </div>
        </div>

        {{-- 2. Partner & Billing Details Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 border-b border-zinc-200/60 dark:border-zinc-800/60 pb-8 mb-8">
            <div class="flex flex-col gap-2">
                <div class="text-xs font-bold uppercase text-zinc-400 tracking-widest">Procured From (Vendor)</div>
                <div class="text-lg font-bold text-emerald-700 dark:text-emerald-400">{{ $purchase->vendor_name }}</div>
                <p class="text-xs text-zinc-500">Registered Partner Master Record</p>
            </div>
            <div class="flex flex-col gap-2">
                <div class="text-xs font-bold uppercase text-zinc-400 tracking-widest">Billing Date</div>
                <div class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ $purchase->date->format('d F, Y') }}</div>
                <p class="text-xs text-zinc-500">Inward Transaction Date</p>
            </div>
            <div class="flex flex-col gap-2">
                <div class="text-xs font-bold uppercase text-zinc-400 tracking-widest">Payment State / Mode</div>
                <div>
                    @if(strtolower($purchase->payment_mode) === 'cash')
                        <x-badge color="emerald" class="uppercase font-bold tracking-wider text-[10px]">CASH</x-badge>
                    @elseif(strtolower($purchase->payment_mode) === 'upi')
                        <x-badge color="blue" class="uppercase font-bold tracking-wider text-[10px]">UPI</x-badge>
                    @else
                        <x-badge color="rose" class="uppercase font-bold tracking-wider text-[10px]">{{ $purchase->payment_mode }}</x-badge>
                    @endif
                </div>
                <p class="text-xs text-zinc-500 mt-1">Settled Payment Mode</p>
            </div>
        </div>

        {{-- 3. Itemized List Table --}}
        <div class="border border-zinc-200/80 dark:border-zinc-800 rounded-2xl overflow-hidden mb-8 shadow-[inset_0_2px_4px_rgba(0,0,0,0.02)] bg-white/50 dark:bg-zinc-900/50">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-800/50 text-xs font-bold text-zinc-500 uppercase tracking-widest border-b border-zinc-200 dark:border-zinc-800">
                        <th class="px-6 py-4">Item / Product Description</th>
                        <th class="px-6 py-4 text-right">Quantity</th>
                        <th class="px-6 py-4 text-right">Unit Rate</th>
                        <th class="px-6 py-4 text-right">Taxable Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/50">
                    @php $computedSubtotal = 0; @endphp
                    @foreach($purchase->items as $item)
                        @php 
                            $rowTotal = $item->quantity * $item->rate;
                            $computedSubtotal += $rowTotal;
                        @endphp
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-400 mt-2"></div>
                                    <div>
                                        <span class="font-bold text-sm text-zinc-900 dark:text-zinc-100 block">{{ $item->item_name }}</span>
                                        <span class="text-xs text-zinc-400">Stock procurement & placement in {{ $item->warehouse->name ?? 'Default Warehouse' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                {{ number_format($item->quantity, 2) }} {{ $item->unit }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-zinc-600 dark:text-zinc-400">
                                ₹{{ number_format($item->rate, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-zinc-900 dark:text-zinc-100">
                                ₹{{ number_format($rowTotal, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- 4. Financial Calculations Summary --}}
        <div class="flex justify-end mt-8">
            <div class="w-full max-w-sm bg-zinc-50/80 dark:bg-zinc-900/80 rounded-2xl p-6 border border-zinc-200/60 dark:border-zinc-800/60">
                <div class="flex justify-between items-center py-2 text-sm text-zinc-500 dark:text-zinc-400 font-medium">
                    <span>Subtotal (Taxable)</span>
                    <span class="font-mono text-zinc-900 dark:text-zinc-100">₹{{ number_format($computedSubtotal, 2) }}</span>
                </div>
                <div class="flex justify-between items-center py-2 text-sm text-zinc-500 dark:text-zinc-400 font-medium">
                    <span>Integrated GST ({{ $purchase->gst_percentage }}%)</span>
                    <span class="font-mono text-zinc-900 dark:text-zinc-100">₹{{ number_format($purchase->gst_amount, 2) }}</span>
                </div>
                <div class="flex justify-between items-center mt-4 pt-4 border-t border-dashed border-zinc-300 dark:border-zinc-700">
                    <span class="text-base font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-widest">Grand Net Total</span>
                    <span class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400">₹{{ number_format($purchase->total_amount, 2) }}</span>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection


