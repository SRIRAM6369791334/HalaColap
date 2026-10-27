@extends('layouts.pdf')
@section('title', 'Supplier Ledger Statement')
@section('meta', 'Statement Period: Up to ' . now()->format('d M Y'))

@section('content')

<table class="summary-grid">
    <tr>
        <td style="width: 60%; padding-right: 10px;">
            <div class="summary-card">
                <div class="summary-label">Supplier Details</div>
                <div class="summary-value" style="margin-bottom: 5px;">{{ $dealer->firm_name }}</div>
                <div style="font-size: 10px; color: #4b5563; line-height: 1.4;">
                    {{ $dealer->contact_person }}<br>
                    <strong>Phone: {{ $dealer->phone }}</strong>
                    @if($dealer->gst_number)
                        <br>GSTIN: {{ $dealer->gst_number }}
                    @endif
                </div>
            </div>
        </td>
        <td style="width: 40%; padding-left: 10px;">
            <div class="summary-card" style="border-left: 3px solid #f43f5e;">
                <div class="summary-label">Pending Payable</div>
                <div class="summary-value text-rose" style="font-size: 18px;">Rs {{ number_format($dealer->displayed_outstanding, 2) }}</div>
                @if($dealer->dayload_outstanding > 0)
                    <div style="font-size: 8px; color: #9ca3af; margin-top: 2px;">Old: Rs {{ number_format($dealer->pending_amount, 0) }} + Day-Load: Rs {{ number_format($dealer->dayload_outstanding, 0) }}</div>
                @endif
                <div style="font-size: 8px; color: #9ca3af; margin-top: 5px; font-weight: bold;">ACCOUNT PAYABLE LIABILITY</div>
            </div>
        </td>
    </tr>
</table>

<h2>Transaction History</h2>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 15%;">Date</th>
            <th style="width: 45%;">Transaction Description</th>
            <th style="width: 12%;" class="text-right">Debit (+)</th>
            <th style="width: 12%;" class="text-right">Credit (-)</th>
            <th style="width: 16%;" class="text-right">Liability Balance</th>
        </tr>
    </thead>
    <tbody>
        @php $runningLiability = 0; @endphp
        @foreach($ledger as $row)
            @php 
                $runningLiability += $row['debit'];
                $runningLiability -= $row['credit'];
            @endphp
            <tr>
                <td>{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</td>
                <td>{{ $row['desc'] }}</td>
                <td class="text-right text-rose">
                    {{ $row['debit'] > 0 ? 'Rs ' . number_format($row['debit'], 2) : '-' }}
                </td>
                <td class="text-right text-emerald">
                    {{ $row['credit'] > 0 ? 'Rs ' . number_format($row['credit'], 2) : '-' }}
                </td>
                <td class="text-right font-bold">Rs {{ number_format($runningLiability, 2) }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="4" class="text-right font-bold" style="padding: 12px; background-color: #f9fafb;">Final Outstanding Liability</td>
            <td class="text-right font-bold text-rose" style="padding: 12px; font-size: 12px; background-color: #f9fafb;">
                Rs {{ number_format($runningLiability, 2) }}
            </td>
        </tr>
    </tbody>
</table>

@if($dayLoadLedger->isNotEmpty())
    <h2>Day-Load Billing</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">Date</th>
                <th style="width: 45%;">Description</th>
                <th style="width: 14%;" class="text-right">Bird Value (Dr)</th>
                <th style="width: 14%;" class="text-right">Payment (Cr)</th>
                <th style="width: 12%;" class="text-right">D/L Balance</th>
            </tr>
        </thead>
        <tbody>
            @php $dlRunning = 0; @endphp
            @foreach($dayLoadLedger as $row)
                @php $dlRunning += $row['debit'] - $row['credit']; @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</td>
                    <td>{{ $row['desc'] }}</td>
                    <td class="text-right text-rose">
                        {{ $row['debit'] > 0 ? 'Rs ' . number_format($row['debit'], 2) : '-' }}
                    </td>
                    <td class="text-right text-emerald">
                        {{ $row['credit'] > 0 ? 'Rs ' . number_format($row['credit'], 2) : '-' }}
                    </td>
                    <td class="text-right font-bold">Rs {{ number_format($dlRunning, 2) }}</td>
                </tr>
            @endforeach
            @php
                // Sanity check: running balance must equal the accessor's computation.
                // Both derive from the same underlying data (non-cancelled entries minus
                // payments), but via two independent code paths — any mismatch signals a bug.
                $expectedFinal = max(0, $dlRunning);
                $accessorFinal = $dealer->dayload_outstanding;
            @endphp
            @if(abs($expectedFinal - $accessorFinal) > 0.01)
                <tr>
                    <td colspan="5" class="text-center" style="padding: 8px; background-color: #fef2f2; color: #dc2626; font-size: 10px;">
                        ⚠ DAY-LOAD BALANCE MISMATCH — Running total (Rs {{ number_format($expectedFinal, 2) }})
                        differs from system outstanding (Rs {{ number_format($accessorFinal, 2) }}).
                        Please verify data integrity.
                    </td>
                </tr>
            @endif
            <tr>
                <td colspan="4" class="text-right font-bold" style="padding: 8px; background-color: #f9fafb;">Final Day-Load Outstanding</td>
                <td class="text-right font-bold text-rose" style="padding: 8px; font-size: 11px; background-color: #f9fafb;">
                    Rs {{ number_format($expectedFinal, 2) }}
                </td>
            </tr>
        </tbody>
    </table>
@endif

@endsection
