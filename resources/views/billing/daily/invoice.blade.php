@extends('layouts.app')
@section('title', 'Daily Sale Invoice #' . $bill->id)

@section('content')
<div class="max-w-4xl mx-auto bg-gradient-to-br from-white via-emerald-50/30 to-sky-50/30 p-10 border border-zinc-200 shadow-lg rounded-2xl my-6 relative overflow-hidden" id="invoice-print">
    {{-- Decorative Header Accent --}}
    <div class="absolute top-0 left-0 w-full h-2 bg-emerald-600"></div>

    <div class="flex justify-between items-start border-b border-zinc-200 pb-8 mb-8">
        <div>
            <h1 class="text-4xl font-black text-emerald-600 tracking-tighter italic">MSR BROILERS <span class="text-zinc-950 not-italic tracking-normal font-bold"></span></h1>
            <p class="text-[10px] text-zinc-400 mt-1.5 uppercase tracking-[0.2em] font-black bg-emerald-50 px-3 py-1 rounded-full inline-block">MSR BROILERS Management Solutions</p>
            <div class="mt-6 text-sm text-zinc-500 space-y-1">
                <p class="font-bold text-zinc-950">MSR BROILERS Farm Unit #1</p>
                <p>Tamil Nadu, India</p>
                <p> +91 98765 43210</p>
            </div>
        </div>
        <div class="text-right">
            <div class="bg-emerald-50 text-emerald-700 px-4 py-2 rounded-xl inline-block mb-4">
                <h2 class="text-xl font-black uppercase tracking-tight">Tax Invoice</h2>
            </div>
            <p class="text-xs text-zinc-400 font-bold uppercase tracking-widest">Invoice Number</p>
            <p class="text-lg font-black text-zinc-950 font-mono">{{ $bill->invoice_number }}</p>
            <div class="mt-4">
                <p class="text-xs text-zinc-400 font-bold uppercase tracking-widest">
                    @if($bill->date_from && $bill->date_to && $bill->date_from->ne($bill->date_to))
                        Billing Period
                    @else
                        Date of Issue
                    @endif
                </p>
                <p class="text-sm font-bold text-zinc-950">
                    @if($bill->date_from && $bill->date_to && $bill->date_from->ne($bill->date_to))
                        {{ $bill->date_from->format('d M Y') }} — {{ $bill->date_to->format('d M Y') }}
                    @else
                        {{ $bill->date->format('d F, Y') }}
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-16 mb-12">
        <div>
            <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-3 border-b border-emerald-100 pb-1 inline-block">Bill To Dealer / Client</p>
            <h3 class="text-2xl font-black text-zinc-950">{{ $bill->dealer->firm_name ?? $bill->customer->name ?? 'N/A' }}</h3>
            <div class="mt-3 text-sm text-zinc-600 leading-relaxed space-y-1">
                <p>{{ $bill->dealer->address ?? $bill->customer->address ?? 'No address provided' }}</p>
                <p class="font-bold text-zinc-950"> {{ $bill->dealer->phone ?? $bill->customer->phone ?? 'N/A' }}</p>
            </div>
        </div>
        <div class="bg-emerald-50 rounded-2xl p-6 border border-zinc-200">
            <p class="text-[10px] font-black text-zinc-400 uppercase tracking-widest mb-4">Payment Summary</p>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-xs text-zinc-500">Payment Status</span>
                    <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-black uppercase tracking-widest">{{ $bill->status }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-zinc-500">Previous Outstanding</span>
                    <span class="text-xs font-mono font-bold text-zinc-950">Rs {{ number_format((float)$bill->previous_outstanding, 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-zinc-500">Payments Received</span>
                    <span class="text-xs font-mono font-bold text-emerald-700">- Rs {{ number_format((float)$bill->payments_during_day, 2) }}</span>
                </div>
                @if($bill->dealer_id)
                    @php
                        $startDate = $bill->date_from ? $bill->date_from->format('Y-m-d') : $bill->date->format('Y-m-d');
                        $endDate = $bill->date_to ? $bill->date_to->format('Y-m-d') : $bill->date->format('Y-m-d');
                        $itemizedPayments = \App\Models\DealerPayment::where('dealer_id', $bill->dealer_id)
                            ->whereBetween('date', [$startDate, $endDate])
                            ->orderBy('date', 'asc')
                            ->get();
                    @endphp
                    @if($itemizedPayments->isNotEmpty())
                        <div class="pt-2 border-t border-zinc-200/80 space-y-1 text-[11px]">
                            <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-1">Received Payment Dates:</p>
                            @foreach($itemizedPayments as $pay)
                                <div class="flex justify-between text-zinc-600 font-mono">
                                    <span>{{ \Carbon\Carbon::parse($pay->date)->format('d M Y') }} ({{ $pay->payment_mode ?? 'Cash' }}):</span>
                                    <span class="font-bold text-emerald-700">Rs {{ number_format((float)$pay->amount, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <div class="mb-12 overflow-hidden rounded-2xl border border-zinc-200 shadow-sm">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gradient-to-br from-white via-emerald-50/30 to-sky-50/30 border border-zinc-200 text-[10px] font-black text-emerald-800 uppercase tracking-widest">
                    <th class="px-8 py-4">Item Description</th>
                    <th class="px-8 py-4 text-center">Quantity</th>
                    <th class="px-8 py-4 text-right">Unit Price</th>
                    <th class="px-8 py-4 text-right">Taxable Amt</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($bill->items as $item)
                <tr class="text-zinc-950 hover:bg-emerald-50 transition-colors">
                    <td class="px-8 py-6">
                        <p class="font-black text-zinc-950">{{ $item->item_name }}</p>
                        <p class="text-[10px] text-zinc-400 mt-0.5 uppercase tracking-tighter font-bold">Standard MSR BROILERS Product</p>
                    </td>
                    <td class="px-8 py-6 text-center font-mono font-bold text-zinc-600">{{ number_format($item->quantity_kg, 2) }} {{ $item->unit }}</td>
                    <td class="px-8 py-6 text-right font-mono text-zinc-600">Rs {{ number_format($item->rate_per_kg, 2) }}</td>
                    <td class="px-8 py-6 text-right font-mono font-black text-zinc-950">Rs {{ number_format($item->quantity_kg * $item->rate_per_kg, 2) }}</td>
                </tr>
                @endforeach

                @if($bill->date_from && $bill->date_to && $bill->date_from->ne($bill->date_to))
                    @php
                        $startDate = \Carbon\Carbon::parse($bill->date_from);
                        $endDate = \Carbon\Carbon::parse($bill->date_to);
                        $loadDates = $bill->dayLoadEntries->map(fn($e) => $e->batch ? \Carbon\Carbon::parse($e->batch->billing_date)->format('Y-m-d') : null)->filter()->unique()->toArray();
                    @endphp
                    @for($dt = $startDate->copy(); $dt->lte($endDate); $dt->addDay())
                        @if(!in_array($dt->format('Y-m-d'), $loadDates))
                            <tr class="text-zinc-500 bg-zinc-50/70 border-t border-zinc-100">
                                <td class="px-8 py-3.5 italic font-outfit text-xs" colspan="4">
                                    <span class="font-bold text-zinc-700 not-italic">{{ $dt->format('d M Y (l)') }}</span> — <span class="text-amber-700 font-semibold">No Load Entries Recorded</span>
                                </td>
                            </tr>
                        @endif
                    @endfor
                @endif
            </tbody>
        </table>
    </div>

    <div class="flex justify-end mb-16">
        <div class="w-72 bg-gradient-to-br from-white via-emerald-50/30 to-sky-50/30 border border-zinc-200 rounded-3xl p-8 text-zinc-800 shadow-lg relative overflow-hidden">
            {{-- Decorative Circle --}}
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-emerald-500/20 rounded-full"></div>
            
            <div class="space-y-4 relative z-10">
                <div class="flex justify-between items-center text-xs opacity-60 font-bold uppercase tracking-widest">
                    <span>Subtotal</span>
                    <span>Rs {{ number_format($bill->amount, 2) }}</span>
                </div>
                @if((float)($bill->discount_amount ?? 0) > 0)
                    @php
                        $discPct = (float)($bill->discount_percentage ?? 0);
                        if ($discPct <= 0 && (float)$bill->amount > 0) {
                            $discPct = round(((float)$bill->discount_amount / (float)$bill->amount) * 100, 2);
                        }
                    @endphp
                    <div class="flex justify-between items-center text-xs font-bold uppercase tracking-widest pb-4 border-b border-zinc-200 text-rose-600">
                        <span>Discount @if($discPct > 0)({{ $discPct }}%)@endif</span>
                        <span>- Rs {{ number_format((float)$bill->discount_amount, 2) }}</span>
                    </div>
                @else
                    <div class="pb-2 border-b border-zinc-200"></div>
                @endif
                <div class="flex justify-between items-center pt-2">
                    <span class="text-xs font-black uppercase tracking-[0.2em] text-emerald-600">Total Net</span>
                    <span class="text-3xl font-black font-mono">Rs {{ number_format($bill->net_amount, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="border-t border-zinc-200 pt-10 text-center">
        <div class="flex justify-center gap-12 mb-8 text-[10px] font-black text-zinc-400 uppercase tracking-[0.3em]">
            <span>No Signature Required</span>
            <span class="text-emerald-500">Computer Generated</span>
            <span>Auth Verified</span>
        </div>
        <p class="text-sm text-zinc-950 font-black mb-1">Thank you for choosing MSR BROILERS Management!</p>
        <p class="text-xs text-zinc-400 font-medium">Please settle the payment according to the agreed credit terms.</p>
        
        <div class="mt-10 flex justify-center gap-4 no-print">
            <button onclick="window.print()" class="px-8 py-3 bg-gradient-to-br from-white via-emerald-50/30 to-sky-50/30 border-2 border-emerald-600 text-emerald-600 text-xs font-black uppercase tracking-widest rounded-xl hover:bg-emerald-50 transition-all shadow-md shadow-emerald-600/5 active:scale-95"> Print Invoice</button>
            <a href="{{ route('billing.daily.pdf', $bill) }}" class="px-8 py-3 bg-gradient-to-r from-emerald-600 to-sky-500 text-white text-xs font-black uppercase tracking-widest rounded-xl hover:bg-emerald-700 transition-all shadow-md shadow-emerald-600/20 active:scale-95 flex items-center gap-2"> Download PDF</a>
            <button onclick="window.close()" class="px-8 py-3 border-2 border-zinc-200 text-zinc-400 text-xs font-black uppercase tracking-widest rounded-xl hover:bg-emerald-50 transition-all active:scale-95">Close Window</button>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print, nav, aside, header { display: none !important; }
    body { background-color: white !important; padding: 0 !important; margin: 0 !important; }
    #invoice-print { 
        margin: 0 !important; 
        padding: 40px !important; 
        border: none !important; 
        box-shadow: none !important; 
        width: 100% !important; 
        max-width: none !important; 
        border-radius: 0 !important;
    }
}
</style>
@endsection
