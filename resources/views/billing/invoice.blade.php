<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $bill->invoice_no ?? ('INV-W-' . str_pad($bill->id, 4, '0', STR_PAD_LEFT)) }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    
    @vite(['resources/css/app.css'])
    <style>
        * { font-family: 'Outfit', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .invoice-card {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body class="bg-zinc-100 min-h-screen py-12 px-4">
    <div class="max-w-4xl mx-auto">

        @php
            $isDaily = ($bill instanceof \App\Models\DailyBill) || !empty($bill->date);
            $grossTotal = (float)($dayLoadTotal ?? $bill->amount);
            $discountAmt = (float)($bill->discount_amount ?? 0);
            $discountPct = (float)($bill->discount_percentage ?? 0);
            if ($discountPct > 0 && $discountAmt <= 0 && $grossTotal > 0) {
                $discountAmt = round(($grossTotal * $discountPct) / 100, 2);
            } elseif ($discountPct <= 0 && $grossTotal > 0 && $discountAmt > 0) {
                $discountPct = round(($discountAmt / $grossTotal) * 100, 2);
            }
            $netAmt = max(0, round($grossTotal - $discountAmt, 2));
            $prevOutstanding = (float)($bill->previous_outstanding ?? 0);
            $totalPayable = round($prevOutstanding + $netAmt, 2);
        @endphp

        {{-- ===== TOOLBAR ===== --}}
        <div class="mb-6 flex flex-col gap-3 no-print sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ $isDaily ? route('billing.daily.index') : route('billing.weekly.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 border border-zinc-300 bg-white text-zinc-600 text-sm font-bold rounded-xl hover:bg-zinc-50 transition-all shadow-sm">
                <span class="material-symbols-rounded text-[18px]">arrow_back</span> Back to Bills
            </a>
            <div class="flex flex-wrap gap-3">
                <button onclick="window.print()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl transition-all shadow-md">
                    <span class="material-symbols-rounded text-[18px]">print</span> Print
                </button>
                <a href="{{ $isDaily ? route('billing.daily.pdf', $bill->id) : route('billing.weekly.pdf', $bill->id) }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl transition-all shadow-md">
                    <span class="material-symbols-rounded text-[18px]">download</span> Download PDF
                </a>
                @if(!$bill->is_approved ?? true)
                    <a href="{{ $isDaily ? route('billing.daily.whatsapp', $bill->id) : route('billing.weekly.whatsapp', $bill->id) }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-500 hover:bg-green-600 text-white text-sm font-bold rounded-xl transition-all shadow-md"
                       target="_blank">
                        <span class="material-symbols-rounded text-[18px]">chat</span> WhatsApp
                    </a>
                @endif
            </div>
        </div>

        {{-- ===== INVOICE CONTENT CARD ===== --}}
        <div class="bg-white border border-zinc-200 shadow-sm rounded-2xl overflow-hidden invoice-card" id="invoice-print">

            {{-- ===== HEADER ===== --}}
            <div class="px-8 pt-8 pb-6 border-b-2 border-zinc-900 flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-black uppercase tracking-tight text-zinc-900">MSR <span class="text-emerald-600">BROILERS</span></h1>
                    <p class="text-[9px] font-bold text-zinc-400 uppercase tracking-widest mt-1">MSR BROILERS Management Solutions</p>
                    
                    <div class="mt-6 text-xs text-zinc-500 space-y-1">
                        <p class="font-bold text-zinc-700 uppercase tracking-wider text-[10px]">Bill To</p>
                        <p class="text-sm font-bold text-zinc-900">{{ $bill->dealer?->firm_name ?? ($bill->customer?->name ?? 'N/A') }}</p>
                        <p>{{ $bill->dealer?->location ?? ($bill->customer?->address ?? '') }}</p>
                        <p><span class="font-bold text-zinc-700">Phone:</span> {{ $bill->dealer?->phone ?? ($bill->customer?->phone ?? 'N/A') }}</p>
                        @if($bill->dealer?->gst_number ?? $bill->customer?->gst_number ?? null)
                            <p>GSTIN: <span class="font-mono text-zinc-600">{{ $bill->dealer?->gst_number ?? $bill->customer?->gst_number }}</span></p>
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <span class="inline-block px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200 mb-3">
                        {{ $isDaily ? 'Daily Tax Invoice' : 'Weekly Invoice' }}
                    </span>
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Invoice No</p>
                    <p class="text-xl font-black font-mono text-zinc-900">{{ $bill->invoice_no ?? ($bill->invoice_number ?? ('INV-' . str_pad($bill->id, 4, '0', STR_PAD_LEFT))) }}</p>
                    <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                        {{ $bill->status === 'Paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $bill->status ?? 'Active' }}
                    </span>

                    <div class="mt-4 text-xs text-zinc-500 space-y-1">
                        @if($isDaily)
                            <p><span class="font-bold text-zinc-700">Billing Date:</span> {{ $bill->date?->format('d M Y (l)') ?? now()->format('d M Y') }}</p>
                        @else
                            <p><span class="font-bold text-zinc-700">Period:</span> {{ $bill->period_start?->format('d M Y') }} — {{ $bill->period_end?->format('d M Y') }}</p>
                        @endif
                        <p><span class="font-bold text-zinc-700">Generated:</span> {{ now()->format('d M Y') }}</p>
                        <p><span class="font-bold text-zinc-700">Payment Mode:</span> {{ $bill->payment_mode ?? 'Credit' }}</p>
                    </div>
                </div>
            </div>

            {{-- ===== FINANCIAL STATS CARDS ===== --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 px-8 py-6 bg-zinc-50/50 border-b border-zinc-200">
                <div class="p-4 rounded-xl border border-blue-200 bg-blue-50/30 text-center">
                    <p class="text-[9px] font-bold text-blue-600 uppercase tracking-wider mb-1">Previous Outstanding</p>
                    <p class="text-lg font-black font-mono text-blue-800">₹{{ number_format((float)($bill->previous_outstanding ?? 0), 2) }}</p>
                </div>
                <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/30 text-center">
                    <p class="text-[9px] font-bold text-emerald-600 uppercase tracking-wider mb-1">Gross Amount</p>
                    <p class="text-lg font-black font-mono text-emerald-800">+ ₹{{ number_format($grossTotal, 2) }}</p>
                </div>
                @if($discountAmt > 0)
                <div class="p-4 rounded-xl border border-rose-200 bg-rose-50/30 text-center">
                    <p class="text-[9px] font-bold text-rose-600 uppercase tracking-wider mb-1">Discount @if($discountPct > 0)({{ $discountPct }}%)@endif</p>
                    <p class="text-lg font-black font-mono text-rose-800">- ₹{{ number_format($discountAmt, 2) }}</p>
                </div>
                @else
                <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/30 text-center">
                    <p class="text-[9px] font-bold text-amber-600 uppercase tracking-wider mb-1">Payments Received</p>
                    <p class="text-lg font-black font-mono text-amber-800">- ₹{{ number_format($totalPaid ?? (float)($bill->payments_during_day ?? $bill->payments_during_week ?? 0), 2) }}</p>
                </div>
                @endif
                <div class="p-4 rounded-xl border border-purple-200 bg-purple-50/30 text-center">
                    <p class="text-[9px] font-bold text-purple-600 uppercase tracking-wider mb-1">Net Invoice Amount</p>
                    <p class="text-lg font-black font-mono text-purple-800">₹{{ number_format($netAmt, 2) }}</p>
                </div>
            </div>

            {{-- ===== DAY-LOAD ENTRIES ===== --}}
            <div>
                <div class="px-8 py-3 bg-zinc-50 border-b border-zinc-200 flex items-center gap-2">
                    <span class="material-symbols-rounded text-zinc-500 text-[18px]">local_shipping</span>
                    <h3 class="font-bold text-zinc-700 text-xs uppercase tracking-wider">Day-Load Entries</h3>
                    @if(isset($dayLoadEntries) && $dayLoadEntries->isNotEmpty())
                        <span class="ml-auto text-[10px] font-bold text-zinc-500 bg-zinc-200/50 px-2 py-0.5 rounded-full">
                            {{ $dayLoadEntries->count() }} entries | {{ number_format($dayLoadEntries->sum('bird_weight'), 2) }} kg total
                        </span>
                    @endif
                </div>

                @if(isset($dayLoadEntries) && $dayLoadEntries->isNotEmpty())
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest bg-zinc-50/50 border-b border-zinc-200">
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3">Batch & Vendor</th>
                                <th class="px-6 py-3">Bill / Status</th>
                                <th class="px-6 py-3 text-right">Net Weight (kg)</th>
                                <th class="px-6 py-3 text-right">Customer Rate</th>
                                <th class="px-6 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-150">
                            @foreach($dayLoadEntries as $entry)
                                @php
                                    $kg    = (float) $entry->bird_weight;
                                    $rate  = (float) $entry->customer_rate;
                                    $total = round($kg * $rate, 2);
                                    $collected = (float) ($entry->dealer_collected ?? 0);
                                @endphp
                                <tr class="hover:bg-zinc-50/50 transition-colors">
                                    <td class="px-6 py-3 font-medium text-zinc-800">
                                        {{ $entry->batch?->billing_date?->format('d M Y') ?? '—' }}
                                        <span class="block text-[10px] text-zinc-400">{{ $entry->batch?->billing_date?->format('l') }}</span>
                                    </td>
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-black font-mono bg-blue-50 text-blue-700 border border-blue-200">
                                                #Batch {{ $entry->batch_id }}
                                            </span>
                                            <span class="text-xs font-bold text-zinc-800">{{ $entry->vendor->firm_name ?? '-' }}</span>
                                        </div>
                                        @if($entry->remarks)
                                            <span class="block text-[10px] text-zinc-400 mt-0.5">{{ $entry->remarks }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3">
                                        @if($entry->daily_bill_id || $entry->dailyBill)
                                            <div class="space-y-1">
                                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-violet-100 text-violet-800 border border-violet-200">
                                                    #{{ $entry->dailyBill->invoice_no ?? 'Daily Bill' }}
                                                </span>
                                                @if($collected >= $total && $total > 0)
                                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Paid: ₹{{ number_format($collected, 0) }} ✅</span>
                                                @elseif($collected > 0)
                                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">Paid: ₹{{ number_format($collected, 0) }} ⏳</span>
                                                @else
                                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-zinc-100 text-zinc-700">Daily Bill Generated</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-[11px] text-zinc-400 font-medium">Recorded Load</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-right font-mono text-zinc-700">{{ number_format($kg, 2) }} kg</td>
                                    <td class="px-6 py-3 text-right font-mono text-zinc-700">₹{{ number_format($rate, 2) }}</td>
                                    <td class="px-6 py-3 text-right font-bold font-mono text-zinc-900">₹{{ number_format($total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-zinc-50/50 border-t border-zinc-200">
                                <td colspan="3" class="px-6 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wider">
                                    Day-Load Total
                                </td>
                                <td class="px-6 py-3 text-right font-mono font-bold text-zinc-700">
                                    {{ number_format($dayLoadEntries->sum('bird_weight'), 2) }} kg
                                </td>
                                <td></td>
                                <td class="px-6 py-3 text-right font-black font-mono text-emerald-600 text-base">
                                    ₹{{ number_format($grossTotal, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                @else
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest bg-zinc-50/50 border-b border-zinc-200">
                                <th class="px-6 py-3">Description</th>
                                <th class="px-6 py-3 text-right">Qty (kg)</th>
                                <th class="px-6 py-3 text-right">Rate</th>
                                <th class="px-6 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-150">
                            @forelse($bill->items as $item)
                                <tr>
                                    <td class="px-6 py-3 font-medium text-zinc-800">{{ $item->item_name }}</td>
                                    <td class="px-6 py-3 text-right font-mono text-zinc-600">{{ number_format($item->quantity_kg, 2) }}</td>
                                    <td class="px-6 py-3 text-right font-mono text-zinc-600">₹{{ number_format($item->rate_per_kg, 2) }}</td>
                                    <td class="px-6 py-3 text-right font-bold font-mono text-zinc-900">₹{{ number_format($item->quantity_kg * $item->rate_per_kg, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-6 text-center text-zinc-400 text-sm">No line items found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- ===== STOCK TRANSFERS ===== --}}
            @if((isset($transfersOut) && $transfersOut->isNotEmpty()) || (isset($transfersIn) && $transfersIn->isNotEmpty()))
            <div class="border-t border-zinc-200">
                <div class="px-8 py-3 bg-zinc-50 border-b border-zinc-200 flex items-center gap-2">
                    <span class="material-symbols-rounded text-zinc-500 text-[18px]">swap_horiz</span>
                    <h3 class="font-bold text-zinc-700 text-xs uppercase tracking-wider">Stock Transfers</h3>
                </div>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest bg-zinc-50/50 border-b border-zinc-200">
                            <th class="px-6 py-3">Type</th>
                            <th class="px-6 py-3">Dealer</th>
                            <th class="px-6 py-3 text-right">Transferred Weight (kg)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-150">
                        @if(isset($transfersOut))
                            @foreach($transfersOut as $transfer)
                                <tr>
                                    <td class="px-6 py-3"><span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">Transferred Out</span></td>
                                    <td class="px-6 py-3 font-medium text-zinc-800">{{ $transfer->resultingEntry->dealer->firm_name ?? 'Unknown' }}</td>
                                    <td class="px-6 py-3 text-right font-mono text-zinc-800">{{ number_format($transfer->new_values['transferred_weight'] ?? 0, 2) }} kg</td>
                                </tr>
                            @endforeach
                        @endif
                        @if(isset($transfersIn))
                            @foreach($transfersIn as $transfer)
                                <tr>
                                    <td class="px-6 py-3"><span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">Received In</span></td>
                                    <td class="px-6 py-3 font-medium text-zinc-800">{{ $transfer->entry->dealer->firm_name ?? 'Unknown' }}</td>
                                    <td class="px-6 py-3 text-right font-mono text-zinc-800">{{ number_format($transfer->new_values['transferred_weight'] ?? 0, 2) }} kg</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
                
                @php
                    $totalOut = isset($transfersOut) ? $transfersOut->sum(fn($t) => $t->new_values['transferred_weight'] ?? 0) : 0;
                    $totalIn = isset($transfersIn) ? $transfersIn->sum(fn($t) => $t->new_values['transferred_weight'] ?? 0) : 0;
                    $finalWeight = isset($dayLoadEntries) ? $dayLoadEntries->sum('bird_weight') : 0;
                    $originalWeight = $finalWeight + $totalOut - $totalIn;
                @endphp
                <div class="px-6 py-3 bg-zinc-50 border-t border-zinc-200 grid grid-cols-3 gap-4 text-center">
                    <div class="p-2 bg-white rounded-lg border border-zinc-200 shadow-sm">
                        <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-1">Initial Weight</p>
                        <p class="font-mono font-bold text-zinc-800">{{ number_format($originalWeight, 2) }} kg</p>
                    </div>
                    <div class="p-2 bg-white rounded-lg border border-zinc-200 shadow-sm">
                        <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-1">Net Transfer</p>
                        <p class="font-mono font-bold {{ $totalIn > $totalOut ? 'text-emerald-600' : ($totalOut > $totalIn ? 'text-rose-600' : 'text-zinc-800') }}">
                            @if($totalIn > $totalOut)
                                +{{ number_format($totalIn - $totalOut, 2) }} kg
                            @elseif($totalOut > $totalIn)
                                -{{ number_format($totalOut - $totalIn, 2) }} kg
                            @else
                                0.00 kg
                            @endif
                        </p>
                    </div>
                    <div class="p-2 bg-white rounded-lg border border-zinc-200 shadow-sm">
                        <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-1">Billed Weight</p>
                        <p class="font-mono font-bold text-indigo-600">{{ number_format($finalWeight, 2) }} kg</p>
                    </div>
                </div>
            </div>
            @endif

            {{-- ===== PAYMENT HISTORY ===== --}}
            <div class="border-t border-zinc-200">
                <div class="px-8 py-3 bg-zinc-50 border-b border-zinc-200 flex items-center gap-2">
                    <span class="material-symbols-rounded text-zinc-500 text-[18px]">receipt_long</span>
                    <h3 class="font-bold text-zinc-700 text-xs uppercase tracking-wider">Payment History</h3>
                    <span class="ml-auto text-[10px] font-bold text-zinc-500 bg-zinc-200/50 px-2 py-0.5 rounded-full">
                        Total Paid: ₹{{ number_format($totalPaid ?? 0, 2) }}
                    </span>
                </div>

                @if(isset($allPayments) && $allPayments->isNotEmpty())
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest bg-zinc-50/50 border-b border-zinc-200">
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3">Description</th>
                                <th class="px-6 py-3 text-center">Mode</th>
                                <th class="px-6 py-3 text-right">Cash</th>
                                <th class="px-6 py-3 text-right">Bank</th>
                                <th class="px-6 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-150">
                            @foreach($allPayments as $payment)
                                @php
                                    $desc = $payment->notes ?? 'Payment';
                                    if (str_contains(strtolower($desc), 'monday') || str_contains(strtolower($desc), 'monday split')) {
                                        $desc = '🗓️ Monday Split Payment';
                                    } elseif (str_contains(strtolower($desc), 'friday') || str_contains(strtolower($desc), 'friday split')) {
                                        $desc = '🗓️ Friday Split Payment';
                                    } elseif (str_contains(strtolower($desc), 'allocated') || str_contains(strtolower($desc), 'auto-allocated')) {
                                        $desc = '💰 Day-Load Payment';
                                    } else {
                                        $desc = '💳 ' . ($payment->notes ?? 'Ledger Payment');
                                    }
                                @endphp
                                <tr class="hover:bg-zinc-50/50 transition-colors text-xs">
                                    <td class="px-6 py-3 font-medium text-zinc-800">
                                        {{ $payment->date?->format('d M Y') }}
                                        <span class="block text-[9px] text-zinc-400">{{ $payment->date?->format('l') }}</span>
                                    </td>
                                    <td class="px-6 py-3 text-zinc-600 text-xs">{{ $desc }}</td>
                                    <td class="px-6 py-3 text-center">
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold {{ $payment->payment_mode === 'Cash' ? 'bg-zinc-100 text-zinc-700 border border-zinc-200' : 'bg-blue-50 text-blue-700 border border-blue-100' }}">
                                            {{ $payment->payment_mode }}
                                            @if($payment->bank_transfer_type)
                                                ({{ $payment->bank_transfer_type }})
                                            @endif
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-right font-mono text-zinc-600">
                                        {{ (float)$payment->cash_amount > 0 ? '₹' . number_format((float)$payment->cash_amount, 2) : '—' }}
                                    </td>
                                    <td class="px-6 py-3 text-right font-mono text-zinc-600">
                                        {{ (float)$payment->bank_amount > 0 ? '₹' . number_format((float)$payment->bank_amount, 2) : '—' }}
                                    </td>
                                    <td class="px-6 py-3 text-right font-bold font-mono text-zinc-900">
                                        ₹{{ number_format((float)$payment->amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="px-8 py-6 text-center text-zinc-400 text-sm">
                        <span class="material-symbols-rounded text-3xl block mb-2">payments</span>
                        No payments recorded yet.
                    </div>
                @endif

                {{-- Payment & Discount Summary Footer --}}
                <div class="px-8 py-5 bg-zinc-50 border-t border-zinc-200">
                    <div class="flex justify-between items-center max-w-md ml-auto">
                        <div class="grid grid-cols-2 gap-x-10 gap-y-2 w-full text-xs">
                            <span class="text-blue-700 font-semibold">Previous Outstanding</span>
                            <span class="text-right font-mono font-bold text-blue-700">₹{{ number_format($prevOutstanding, 2) }}</span>

                            <span class="text-zinc-700 font-bold">Today's Bill Amount</span>
                            <span class="text-right font-mono font-bold text-zinc-900">+ ₹{{ number_format($grossTotal, 2) }}</span>

                            @if($discountAmt > 0)
                            <span class="text-rose-600 font-bold flex items-center gap-1">
                                <span class="material-symbols-rounded text-sm">sell</span> Discount @if($discountPct > 0)({{ $discountPct }}%)@endif
                            </span>
                            <span class="text-right font-mono font-bold text-rose-600">- ₹{{ number_format($discountAmt, 2) }}</span>
                            @endif

                            <span class="text-zinc-800 font-extrabold pt-2 border-t border-zinc-200">Total Payable</span>
                            <span class="text-right font-mono font-black text-zinc-900 pt-2 border-t border-zinc-200 text-sm">₹{{ number_format($totalPayable, 2) }}</span>

                            <span class="text-zinc-500 font-medium pt-2">Total Paid / Received</span>
                            <span class="text-right font-mono font-bold text-emerald-600 pt-2">- ₹{{ number_format($totalPaid ?? 0, 2) }}</span>

                            <span class="font-black {{ ($remainingDue ?? 0) <= 0 ? 'text-emerald-700' : 'text-rose-600' }} text-sm pt-2 border-t-2 border-zinc-300">
                                {{ ($remainingDue ?? 0) <= 0 ? '✅ Fully Settled' : '⏳ Remaining Due' }}
                            </span>
                            <span class="text-right font-black font-mono text-base pt-2 border-t-2 border-zinc-300 {{ ($remainingDue ?? 0) <= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                ₹{{ number_format($remainingDue ?? 0, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== FOOTER ===== --}}
            <div class="px-8 py-6 text-center border-t border-zinc-200">
                <p class="text-sm font-bold text-zinc-700 mb-1">Thank you for your business! 🙏</p>
                <p class="text-xs text-zinc-400">Please settle the payment within the weekly credit cycle.</p>
            </div>

        </div>

    </div>
</body>
</html>
