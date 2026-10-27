@extends('layouts.pdf')
@section('title', 'DAILY TAX INVOICE')
@section('meta', "Invoice No: {$bill->invoice_no}")

@push('styles')
<style>
    .invoice-details { width: 100%; margin-bottom: 25px; }
    .invoice-details td { vertical-align: top; width: 50%; }
    .section-label { font-size: 10px; font-weight: bold; color: #7c3aed; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; margin-bottom: 10px; }
    .total-box { float: right; width: 220px; background-color: #111827; color: #fff; padding: 20px; border-radius: 8px; margin-top: 20px; }
    .total-row { margin-bottom: 8px; font-size: 10px; text-transform: uppercase; opacity: 0.8; }
    .total-row span { float: right; }
    .grand-total { margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.2); font-size: 16px; font-weight: bold; color: #a78bfa; }
    .grand-total span { float: right; }
    .section-bar { background: #f3f4f6; padding: 6px 12px; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #374151; border-top: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; margin-bottom: 10px; margin-top: 15px; }
    .pay-table { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 15px; }
    .pay-table th { padding: 6px 10px; font-size: 8px; font-weight: bold; text-transform: uppercase; color: #4b5563; border-bottom: 1px solid #d1d5db; background-color: #f9fafb; text-align: left; }
    .pay-table td { padding: 6px 10px; border-bottom: 1px solid #e5e7eb; color: #374151; }
    .pay-table .tr { text-align: right; }
</style>
@endpush

@section('content')

<table class="invoice-details">
    <tr>
        <td>
            <div class="section-label">Bill To Dealer / Client</div>
            <div style="font-size: 14px; font-weight: bold; margin-bottom: 5px; color: #111827;">{{ $bill->dealer?->firm_name ?? $bill->customer?->name ?? 'N/A' }}</div>
            <div style="color: #4b5563; line-height: 1.4;">
                {{ $bill->dealer?->location ?? $bill->customer?->address ?? 'No address provided' }}<br>
                <strong>Phone: {{ $bill->dealer?->phone ?? $bill->customer?->phone ?? 'N/A' }}</strong>
                @if($bill->dealer?->gst_number ?? $bill->customer?->gst_number ?? false)
                    <br><span style="font-size: 10px; color: #9ca3af;">GSTIN: {{ $bill->dealer?->gst_number ?? $bill->customer?->gst_number }}</span>
                @endif
            </div>
        </td>
        <td style="padding-left: 40px;">
            <div class="section-label">Invoice Summary</div>
            <table style="font-size: 10px; width: 100%;">
                <tr>
                    <td style="color: #6b7280; padding: 4px 0; border: none;">Status:</td>
                    <td class="text-right font-bold" style="border: none;">{{ strtoupper($bill->status) }}</td>
                </tr>
                <tr>
                    <td style="color: #6b7280; padding: 4px 0; border: none;">Date:</td>
                    <td class="text-right font-bold" style="border: none;">{{ $bill->date->format('d M Y') }}</td>
                </tr>
                <tr>
                    <td style="color: #6b7280; padding: 4px 0; border: none;">Billing Type:</td>
                    <td class="text-right font-bold" style="border: none;">Daily Invoice</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

@if(isset($dayLoadEntries) && $dayLoadEntries->isNotEmpty())
<table class="data-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Batch & Vendor</th>
            <th>Daily Status</th>
            <th class="text-right">Weight (kg)</th>
            <th class="text-right">Rate</th>
            <th class="text-right">Total Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($dayLoadEntries as $entry)
        @php
            $kg = (float) $entry->bird_weight;
            $rate = (float) $entry->customer_rate;
            $total = round($kg * $rate, 2);
            $collected = (float) ($entry->dealer_collected ?? 0);
        @endphp
        <tr>
            <td>
                <div class="font-bold text-zinc-900">{{ $entry->batch?->billing_date?->format('d M Y') }}</div>
            </td>
            <td><strong>#Batch {{ $entry->batch_id }}</strong> &bull; {{ $entry->vendor?->firm_name ?? '—' }}</td>
            <td>
                @if($entry->daily_bill_id)
                    #{{ $entry->dailyBill?->invoice_no }} (Paid: ₹{{ number_format($collected, 0) }})
                @else
                    Unbilled
                @endif
            </td>
            <td class="text-right">{{ number_format($kg, 2) }} kg</td>
            <td class="text-right">Rs {{ number_format($rate, 2) }}</td>
            <td class="text-right font-bold">Rs {{ number_format($total, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<table class="data-table">
    <thead>
        <tr>
            <th>Item Description</th>
            <th class="text-center">Quantity</th>
            <th class="text-right">Unit Price</th>
            <th class="text-right">Taxable Amt</th>
        </tr>
    </thead>
    <tbody>
        @foreach($bill->items as $item)
        <tr>
            <td>
                <div class="font-bold text-zinc-900">{{ $item->item_name }}</div>
            </td>
            <td class="text-center">{{ number_format($item->quantity_kg, 2) }} {{ $item->unit }}</td>
            <td class="text-right">Rs {{ number_format($item->rate_per_kg, 2) }}</td>
            <td class="text-right font-bold">Rs {{ number_format($item->quantity_kg * $item->rate_per_kg, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- SECTION: PAYMENT HISTORY --}}
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
                    @if(!empty($payment->bank_transfer_type)) ({{ $payment->bank_transfer_type }}) @endif
                </td>
                <td class="tr" style="color:#059669;">
                    {{ (float)$payment->cash_amount > 0 ? 'Rs ' . number_format((float)$payment->cash_amount, 2) : '—' }}
                </td>
                <td class="tr" style="color:#2563eb;">
                    {{ (float)$payment->bank_amount > 0 ? 'Rs ' . number_format((float)$payment->bank_amount, 2) : '—' }}
                </td>
                <td class="tr" style="font-weight:bold;">Rs {{ number_format((float)$payment->amount, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@else
<div style="padding:8px; color:#9ca3af; font-size:9px; text-align:center; border: 1px solid #e5e7eb; margin-bottom: 12px;">
    No payments recorded yet.
</div>
@endif

<div class="total-box">
    <div class="total-row">
        Previous Outstanding
        <span>Rs {{ number_format((float)$bill->previous_outstanding, 2) }}</span>
    </div>
    <div class="total-row">
        Payments Today
        <span>- Rs {{ number_format((float)(isset($totalPaid) && $totalPaid > 0 ? $totalPaid : $bill->payments_during_day), 2) }}</span>
    </div>
    <div class="total-row">
        Discount
        <span>- Rs {{ number_format((float)$bill->discount_amount, 2) }}</span>
    </div>
    <div class="grand-total">
        Net Payable
        <span>Rs {{ number_format((float)(isset($remainingDue) ? $remainingDue : $bill->net_amount), 2) }}</span>
    </div>
</div>

<div style="clear: both; margin-top: 80px; text-align: center;">
    <div style="font-size: 9px; font-weight: bold; color: #9ca3af; text-transform: uppercase; margin-bottom: 10px;">
        NO SIGNATURE REQUIRED &bull; COMPUTER GENERATED &bull; AUTH VERIFIED
    </div>
    <p style="font-weight: bold; color: #374151; margin: 5px 0;">Thank you for choosing {{ config('app.name', 'MSR BROILERS ') }}!</p>
</div>

@endsection
