@extends('layouts.pdf')
@section('title', 'Customer Ledger Statement')
@section('meta', 'Statement Period: Up to ' . now()->format('d M Y'))

@section('content')

<table class="summary-grid">
    <tr>
        <td style="width: 60%; padding-right: 10px;">
            <div class="summary-card">
                <div class="summary-label">Customer Details</div>
                <div class="summary-value" style="margin-bottom: 5px;">{{ $customer->name }}</div>
                <div style="font-size: 10px; color: #4b5563; line-height: 1.4;">
                    {{ $customer->address }}<br>
                    <strong>Phone: {{ $customer->phone }}</strong>
                    @if($customer->gst_number)
                        <br>GSTIN: {{ $customer->gst_number }}
                    @endif
                </div>
            </div>
        </td>
        <td style="width: 40%; padding-left: 10px;">
            <div class="summary-card" style="border-left: 3px solid #10b981;">
                <div class="summary-label">Current Balance</div>
                <div class="summary-value text-emerald" style="font-size: 18px;">Rs {{ number_format($customer->balance, 2) }}</div>
                <div style="font-size: 8px; color: #9ca3af; margin-top: 5px; font-weight: bold;">OUTSTANDING RECEIVABLE</div>
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
            <th style="width: 16%;" class="text-right">Running Balance</th>
        </tr>
    </thead>
    <tbody>
        @php $runningBalance = 0; @endphp
        @foreach($ledger as $row)
            @php 
                $runningBalance += $row['debit'];
                $runningBalance -= $row['credit'];
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
                <td class="text-right font-bold">Rs {{ number_format($runningBalance, 2) }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="4" class="text-right font-bold" style="padding: 12px; background-color: #f9fafb;">Final Outstanding Balance</td>
            <td class="text-right font-bold text-emerald" style="padding: 12px; font-size: 12px; background-color: #f9fafb;">
                Rs {{ number_format($runningBalance, 2) }}
            </td>
        </tr>
    </tbody>
</table>

@endsection
