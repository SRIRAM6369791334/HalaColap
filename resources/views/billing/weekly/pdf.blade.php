@extends('layouts.pdf')
@section('title', 'INVOICE — ' . ($bill->invoice_no ?? 'INV'))
@section('meta', "Invoice No: {$bill->invoice_no} | Period: {$bill->period_start->format('d M')} – {$bill->period_end->format('d M Y')}")

@push('styles')
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
    
    /* Header Styles */
    .header-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    .brand-title { font-size: 20px; font-weight: bold; color: #111827; }
    .brand-green { color: #059669; }
    .subtitle { font-size: 8px; font-weight: bold; color: #9ca3af; text-transform: uppercase; letter-spacing: 1.5px; }
    
    .invoice-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 9px; font-weight: bold; text-transform: uppercase; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .invoice-badge-pending { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
    .meta-table { width: 100%; font-size: 10px; margin-top: 5px; }
    
    /* Party Info Row */
    .party-table { width: 100%; border-collapse: collapse; background: #fff; border-bottom: 2px solid #111827; margin-bottom: 15px; }
    .party-table td { padding: 10px 0 15px 0; vertical-align: top; width: 50%; }
    .section-label { font-size: 8px; font-weight: bold; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
    .party-name { font-size: 12px; font-weight: bold; color: #111827; }
    
    /* Financial Stats Cards */
    .stats-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .stats-table td { width: 25%; padding: 10px; border: 1px solid #e5e7eb; text-align: center; border-radius: 6px; }
    .stat-blue { background: #eff6ff; border-color: #bfdbfe; color: #1e40af; }
    .stat-green { background: #ecfdf5; border-color: #a7f3d0; color: #065f46; }
    .stat-amber { background: #fffbeb; border-color: #fde68a; color: #92400e; }
    .stat-purple { background: #f5f3ff; border-color: #ddd6fe; color: #5b21b6; }
    .stat-lbl { font-size: 7px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
    .stat-val { font-size: 12px; font-weight: bold; font-family: monospace; }

    /* Tables */
    .data-table { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 15px; }
    .data-table thead tr { background-color: #f9fafb; }
    .data-table th { padding: 8px 12px; font-size: 8px; font-weight: bold; text-transform: uppercase; color: #4b5563; border-bottom: 1px solid #d1d5db; text-align: left; }
    .data-table th.text-right { text-align: right; }
    .data-table td { padding: 8px 12px; border-bottom: 1px solid #e5e7eb; color: #374151; }
    .data-table td.text-right { text-align: right; }
    .data-table tfoot td { background: #f9fafb; font-weight: bold; border-top: 1px solid #d1d5db; }

    /* Split Cards */
    .split-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    .split-table td { padding: 0 10px 0 0; width: 50%; }
    .split-table td:last-child { padding: 0 0 0 10px; }
    .split-card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; background: #f9fafb; }
    .split-card.paid { border-color: #a7f3d0; background: #ecfdf5; }
    
    /* Section header bar */
    .section-bar { background: #f3f4f6; padding: 6px 12px; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #374151; border-top: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; margin-bottom: 10px; }

    .pay-footer { background: #f9fafb; border: 1px solid #e5e7eb; padding: 12px; margin-top: 15px; }
    .pay-footer table { width: 100%; font-size: 9px; }
    .pay-footer td { padding: 3px 0; }
    .pay-footer td.tr { text-align: right; }

    .footer-band { margin-top: 25px; text-align: center; font-size: 8px; color: #9ca3af; line-height: 1.5; }
</style>
@endpush

@section('content')

{{-- HEADER --}}
<table class="header-table">
    <tr>
        <td>
            <div class="brand-title">MSR BROILERS<span class="brand-green">Pro</span></div>
            <div class="subtitle">MSR BROILERS Management Solutions</div>
        </td>
        <td style="text-align:right; vertical-align:top;">
            <div class="invoice-badge {{ $bill->status === 'Paid' ? '' : 'invoice-badge-pending' }}">
                {{ $bill->status === 'Paid' ? '✅ Paid' : '⏳ ' . $bill->status }}
            </div>
        </td>
    </tr>
</table>

{{-- PARTY + PERIOD INFO --}}
<table class="party-table">
    <tr>
        <td>
            <div class="section-label">Bill To</div>
            <div class="party-name">{{ $bill->dealer?->firm_name ?? 'N/A' }}</div>
            <div style="color:#4b5563; margin-top:2px;">{{ $bill->dealer?->location ?? '' }}</div>
            <div style="color:#4b5563;">📞 {{ $bill->dealer?->phone ?? 'N/A' }}</div>
            @if($bill->dealer?->gst_number)
                <div style="color:#6b7280; font-size:8px; margin-top:2px;">GSTIN: {{ $bill->dealer->gst_number }}</div>
            @endif
        </td>
        <td style="text-align:right;">
            <div class="section-label">Period & Details</div>
            <div style="font-size:11px; font-weight:bold; color:#111827;">
                {{ $bill->period_start?->format('d M Y') }} — {{ $bill->period_end?->format('d M Y') }}
            </div>
            <table class="meta-table" style="float:right; width:auto;">
                <tr>
                    <td style="color:#6b7280; text-align:right; padding: 1px 0;">Invoice No:</td>
                    <td style="font-weight:bold; text-align:right; padding: 1px 0 1px 8px; font-family:monospace;">{{ $bill->invoice_no ?? ('INV-W-' . str_pad($bill->id, 4, '0', STR_PAD_LEFT)) }}</td>
                </tr>
                <tr>
                    <td style="color:#6b7280; text-align:right; padding: 1px 0;">Generated:</td>
                    <td style="text-align:right; padding: 1px 0 1px 8px;">{{ now()->format('d M Y') }}</td>
                </tr>
                <tr>
                    <td style="color:#6b7280; text-align:right; padding: 1px 0;">Payment Mode:</td>
                    <td style="text-align:right; padding: 1px 0 1px 8px;">{{ $bill->payment_mode ?? 'Credit' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- FINANCIAL STATS CARDS --}}
<table class="stats-table" style="width:100%;">
    <tr>
        <td class="stat-blue" style="padding: 8px;">
            <div class="stat-lbl">Previous Outstanding</div>
            <div class="stat-val">₹{{ number_format((float)($bill->previous_outstanding ?? 0), 2) }}</div>
        </td>
        <td class="stat-green" style="padding: 8px; border-left: 0;">
            <div class="stat-lbl">This Week's Day-Load</div>
            <div class="stat-val">+ ₹{{ number_format($dayLoadTotal ?? $bill->amount, 2) }}</div>
        </td>
        <td class="stat-amber" style="padding: 8px; border-left: 0;">
            <div class="stat-lbl">Payments During Week</div>
            <div class="stat-val">- ₹{{ number_format((float)($bill->payments_during_week ?? 0), 2) }}</div>
        </td>
        <td class="stat-purple" style="padding: 8px; border-left: 0;">
            <div class="stat-lbl">Net Invoice Amount</div>
            <div class="stat-val">₹{{ number_format((float)($bill->net_amount ?? $bill->amount), 2) }}</div>
        </td>
    </tr>
</table>

{{-- SECTION 2: DAY-LOAD ENTRIES --}}
<div class="section-bar">📦 Day-Load Entries</div>
@if(isset($dayLoadEntries) && $dayLoadEntries->isNotEmpty())
<table class="data-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Day</th>
            <th>Batch & Vendor</th>
            <th class="text-right">Weight (kg)</th>
            <th class="text-right">Customer Rate</th>
            <th class="text-right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($dayLoadEntries as $entry)
            @php
                $kg    = (float) $entry->bird_weight;
                $rate  = (float) $entry->customer_rate;
                $total = round($kg * $rate, 2);
            @endphp
            <tr>
                <td>{{ $entry->batch?->billing_date?->format('d M Y') ?? '—' }}</td>
                <td style="color:#6b7280;">{{ $entry->batch?->billing_date?->format('l') ?? '' }}</td>
                <td><strong>#Batch {{ $entry->batch_id }}</strong>@if($entry->sort_order) <strong style="color:#d97706;">(#{{ $entry->sort_order }})</strong>@endif &bull; {{ $entry->vendor?->firm_name ?? '—' }}</td>
                <td class="text-right" style="font-family:monospace;">{{ number_format($kg, 2) }} kg</td>
                <td class="text-right" style="font-family:monospace;">₹{{ number_format($rate, 2) }}</td>
                <td class="text-right" style="font-weight:bold; font-family:monospace; color:#047857;">₹{{ number_format($total, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="color:#4b5563; font-size:8px; text-transform:uppercase;">Day-Load Total</td>
            <td class="text-right" style="font-family:monospace; color:#374151;">{{ number_format($dayLoadEntries->sum('bird_weight'), 2) }} kg</td>
            <td></td>
            <td class="text-right" style="font-size:11px; font-family:monospace; color:#047857;">₹{{ number_format($dayLoadTotal ?? 0, 2) }}</td>
        </tr>
    </tfoot>
</table>
@else
<table class="data-table">
    <thead>
        <tr>
            <th>Description</th>
            <th class="text-right">Qty (kg)</th>
            <th class="text-right">Rate</th>
            <th class="text-right">Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($bill->items as $item)
        <tr>
            <td style="font-weight:bold;">{{ $item->item_name }}</td>
            <td class="text-right" style="font-family:monospace;">{{ number_format($item->quantity_kg, 2) }}</td>
            <td class="text-right" style="font-family:monospace;">₹{{ number_format($item->rate_per_kg, 2) }}</td>
            <td class="text-right" style="font-weight:bold; font-family:monospace; color:#1e40af;">₹{{ number_format($item->quantity_kg * $item->rate_per_kg, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="4" style="text-align:center; color:#9ca3af; padding:12px;">No items found.</td></tr>
        @endforelse
    </tbody>
</table>
@endif

{{-- SECTION 4: PAYMENT HISTORY --}}
<div class="section-bar">📜 Payment History</div>
@if(isset($allPayments) && $allPayments->isNotEmpty())
<table class="pay-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Description</th>
            <th>Mode</th>
            <th class="tr">Cash</th>
            <th class="tr">Bank</th>
            <th class="tr">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($allPayments as $payment)
            @php
                $note = $payment->notes ?? '';
                if (str_contains(strtolower($note), 'monday')) $displayDesc = 'Monday Split Payment';
                elseif (str_contains(strtolower($note), 'friday')) $displayDesc = 'Friday Split Payment';
                elseif (str_contains(strtolower($note), 'allocated')) $displayDesc = 'Day-Load Payment';
                else $displayDesc = $note ?: 'Ledger Payment';
            @endphp
            <tr>
                <td>
                    {{ $payment->date?->format('d M Y') }}
                    <div style="font-size:8px; color:#9ca3af;">{{ $payment->date?->format('l') }}</div>
                </td>
                <td style="color:#4b5563;">{{ $displayDesc }}</td>
                <td>
                    {{ $payment->payment_mode }}
                    @if($payment->bank_transfer_type) ({{ $payment->bank_transfer_type }}) @endif
                </td>
                <td class="tr" style="color:#059669;">
                    {{ (float)$payment->cash_amount > 0 ? '₹' . number_format((float)$payment->cash_amount, 2) : '—' }}
                </td>
                <td class="tr" style="color:#2563eb;">
                    {{ (float)$payment->bank_amount > 0 ? '₹' . number_format((float)$payment->bank_amount, 2) : '—' }}
                </td>
                <td class="tr" style="font-weight:bold;">₹{{ number_format((float)$payment->amount, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@else
<div style="padding:14px; color:#9ca3af; font-size:9px; text-align:center; border: 1px solid #e5e7eb;">
    No payments recorded yet.
</div>
@endif

{{-- Payment Summary Footer --}}
<div class="pay-footer">
    <table>
        <tr>
            <td style="color:#6b7280;">Total Invoice Amount</td>
            <td class="tr" style="font-weight:bold; font-family:monospace;">₹{{ number_format((float)($bill->net_amount ?? 0), 2) }}</td>
        </tr>
        <tr>
            <td style="color:#059669;">Total Paid</td>
            <td class="tr" style="font-weight:bold; font-family:monospace; color:#059669;">₹{{ number_format($totalPaid ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td style="font-weight:bold; font-size:11px; padding-top:6px; border-top:1px solid #d1d5db; color:#111827;">
                {{ ($remainingDue ?? 0) <= 0 ? '✅ Fully Paid' : '⏳ Remaining Due' }}
            </td>
            <td class="tr" style="font-weight:bold; font-size:12px; font-family:monospace; padding-top:6px; border-top:1px solid #d1d5db; {{ ($remainingDue ?? 0) <= 0 ? 'color:#059669;' : 'color:#dc2626;' }}">
                ₹{{ number_format($remainingDue ?? 0, 2) }}
            </td>
        </tr>
    </table>
</div>

{{-- FOOTER --}}
<div class="footer-band">
    <div style="font-weight:bold; color:#374151; margin-bottom:2px;">Thank you for your business! 🙏</div>
    <div>Please settle the payment within the weekly credit cycle.</div>
    <div style="margin-top:6px; font-size: 7px; color: #bdc3c7;">NO SIGNATURE REQUIRED • COMPUTER GENERATED DOCUMENT • AUTH VERIFIED</div>
</div>

@endsection
