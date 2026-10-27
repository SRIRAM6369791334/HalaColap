@extends('layouts.pdf')
@section('title', 'Purchase History Statement')
@section('meta', 'Generated: ' . now()->format('d M Y, h:i A'))

@section('content')

<table class="summary-grid">
    <tr>
        <td style="width: 50%; padding-right: 10px;">
            <div class="summary-card">
                <div class="summary-label">Vendor Details</div>
                <div class="summary-value" style="margin-bottom: 5px;">{{ $vendor->firm_name }}</div>
                <div style="font-size: 10px; color: #4b5563; line-height: 1.4;">
                    <strong>Contact Person:</strong> {{ $vendor->contact_person ?: 'N/A' }}<br>
                    <strong>Phone:</strong> {{ $vendor->phone }}<br>
                    <strong>Location:</strong> {{ $vendor->location ?: 'N/A' }}
                </div>
            </div>
        </td>
        <td style="width: 50%; padding-left: 10px;">
            <div class="summary-card" style="border-left: 3px solid #10b981;">
                <div class="summary-label">Account Info</div>
                <div style="font-size: 10px; color: #4b5563; line-height: 1.4; margin-top: 5px;">
                    <strong>GSTIN:</strong> {{ $vendor->gst_number ?: 'UNREGISTERED' }}<br>
                    <strong>Route:</strong> {{ $vendor->route ?: 'General' }}
                </div>
            </div>
        </td>
    </tr>
</table>

<h2>Purchase History</h2>
<table class="data-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Item Details</th>
            <th class="text-right">Quantity</th>
            <th class="text-right">Total Amount (Rs)</th>
        </tr>
    </thead>
    <tbody>
        @php $totalAmount = 0; @endphp
        @forelse($purchases as $purchase)
            @php $totalAmount += $purchase->total_amount; @endphp
            <tr>
                <td>{{ $purchase->date->format('d M Y') }}</td>
                <td>
                    @if($purchase->items->isNotEmpty())
                        {{ $purchase->items->pluck('item_name')->join(', ') }}
                    @else
                        {{ $purchase->item }}
                    @endif
                </td>
                <td class="text-right">
                    @if($purchase->items->isNotEmpty())
                        {{ number_format($purchase->items->sum('quantity'), 2) }} {{ $purchase->items->first()->unit }}
                    @else
                        {{ number_format($purchase->quantity, 2) }} {{ $purchase->unit }}
                    @endif
                </td>
                <td class="text-right font-bold">Rs {{ number_format($purchase->total_amount, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center" style="padding: 20px;">No purchases found.</td>
            </tr>
        @endforelse
        @if($purchases->count() > 0)
            <tr>
                <td colspan="3" class="text-right font-bold" style="padding: 12px; background-color: #f9fafb;">Total Business Volume</td>
                <td class="text-right font-bold text-emerald" style="padding: 12px; background-color: #f9fafb;">
                    Rs {{ number_format($totalAmount, 2) }}
                </td>
            </tr>
        @endif
    </tbody>
</table>

@endsection
