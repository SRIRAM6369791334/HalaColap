@extends('layouts.pdf')
@section('title', 'DAY-LOAD INVOICE')
@section('meta', "Billing Date: {$dateObj->format('d M Y')}")

@push('styles')
<style>
    .summary-grid { width: 100%; margin-bottom: 25px; border-collapse: collapse; }
    .summary-grid td { padding: 10px; text-align: center; border: 1px solid #e5e7eb; }
    .summary-label { font-size: 9px; text-transform: uppercase; color: #6b7280; margin-bottom: 4px; }
    .summary-value { font-size: 16px; font-weight: bold; }
    .text-rose { color: #f43f5e; }
    .text-emerald { color: #10b981; }
    .text-blue { color: #3b82f6; }
    .text-amber { color: #f59e0b; }
</style>
@endpush

@section('content')

<table class="summary-grid">
    <tr>
        <td style="background: #eff6ff;">
            <div class="summary-label">Total Boxes</div>
            <div class="summary-value text-blue">{{ $entries->sum('no_of_boxes') }}</div>
        </td>
        <td style="background: #ecfdf5;">
            <div class="summary-label">Bird Weight</div>
            <div class="summary-value text-emerald">{{ number_format((float) $entries->sum('bird_weight'), 2) }} kg</div>
        </td>
        <td style="background: #fffbeb;">
            <div class="summary-label">Farm Weight</div>
            <div class="summary-value text-amber">{{ number_format((float) $entries->sum('farm_weight'), 2) }} kg</div>
        </td>
        <td style="background: #f5f3ff;">
            <div class="summary-label">Loss Weight</div>
            <div class="summary-value text-rose">{{ number_format((float) $entries->sum('loss_weight'), 2) }} kg</div>
        </td>
    </tr>
</table>

<h2>Billing Date: {{ $dateObj->format('d F, Y') }} ({{ $dateObj->format('l') }})</h2>

<table class="data-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Vendor</th>
            <th>Dealer</th>
            <th class="text-center">Boxes</th>
            <th class="text-right">Bird Wt</th>
            <th class="text-right">Farm Wt</th>
            <th class="text-right">Loss</th>
            <th class="text-right">Total</th>
        </tr>
    </thead>
    <tbody>
        @php
            $groupedByBatch = $entries->groupBy('batch_id');
            $rowCounter = 1;
        @endphp
        @forelse($groupedByBatch as $batchId => $bEntries)
            @php
                $firstEntry = $bEntries->first();
                $batchVendor = $firstEntry->vendor->firm_name ?? 'Vendor';
                $batchFarm = (float) $bEntries->sum('farm_weight');
                $batchBird = (float) $bEntries->sum('bird_weight');
                $batchLoss = (float) $bEntries->sum('loss_weight');
                $batchLossPct = $batchFarm > 0 ? round(($batchLoss / $batchFarm) * 100, 2) : 0;
            @endphp
            <tr style="background: #f3f4f6; font-weight: bold; border-top: 1px solid #9ca3af;">
                <td colspan="8" style="padding: 6px 8px; font-size: 10px;">
                    BATCH #{{ $loop->iteration }} (#BAT{{ str_pad($batchId, 4, '0', STR_PAD_LEFT) }}) &bull; {{ $batchVendor }}
                    <span style="font-weight: normal; margin-left: 8px;">
                        ({{ $bEntries->count() }} entries | Boxes: {{ $bEntries->sum('no_of_boxes') }} | Bird: {{ number_format($batchBird, 2) }} kg
                        @if($batchFarm > 0)
                         | Farm: {{ number_format($batchFarm, 2) }} kg | Loss: -{{ number_format($batchLoss, 2) }} kg
                        @endif
                        )
                    </span>
                </td>
            </tr>
            @foreach($bEntries as $entry)
            <tr>
                <td class="text-center">{{ $entry->sort_order ?: $rowCounter++ }}</td>
                <td>{{ $entry->vendor->firm_name ?? '-' }}</td>
                <td>{{ $entry->dealer->firm_name ?? '-' }}</td>
                <td class="text-center font-bold">{{ $entry->no_of_boxes }}</td>
                <td class="text-right">{{ number_format((float) $entry->bird_weight, 2) }}</td>
                <td class="text-right">{{ $entry->farm_weight ? number_format((float) $entry->farm_weight, 2) : '—' }}</td>
                <td class="text-right {{ ($entry->loss_weight ?? 0) > 0 ? 'text-rose font-bold' : '' }}">{{ $entry->loss_weight ? number_format((float) $entry->loss_weight, 2) : '—' }}</td>
                <td class="text-right font-bold">{{ $entry->total_weight ? number_format((float) $entry->total_weight, 2) : '—' }}</td>
            </tr>
            @endforeach
            @if($groupedByBatch->count() > 1)
            <tr style="background: #f9fafb; font-size: 9px; font-weight: bold;">
                <td colspan="3" style="text-transform: uppercase;">Subtotal (Batch #{{ $loop->iteration }})</td>
                <td class="text-center">{{ $bEntries->sum('no_of_boxes') }}</td>
                <td class="text-right">{{ number_format((float) $bEntries->sum('bird_weight'), 2) }}</td>
                <td class="text-right">{{ number_format((float) $bEntries->sum('farm_weight'), 2) }}</td>
                <td class="text-right text-rose">{{ number_format((float) $bEntries->sum('loss_weight'), 2) }}</td>
                <td class="text-right">{{ number_format((float) $bEntries->sum('total_weight'), 2) }}</td>
            </tr>
            @endif
        @empty
        <tr>
            <td colspan="8" class="text-center" style="padding: 30px; color: #9ca3af;">No entries found for this date.</td>
        </tr>
        @endforelse
    </tbody>
    @if($entries->count() > 0)
    <tfoot>
        <tr style="font-weight: bold; background: #e5e7eb; border-top: 2px solid #374151;">
            <td colspan="3" style="text-transform: uppercase; font-size: 10px;">Grand Totals (All Batches)</td>
            <td class="text-center">{{ $entries->sum('no_of_boxes') }}</td>
            <td class="text-right">{{ number_format((float) $entries->sum('bird_weight'), 2) }}</td>
            <td class="text-right">{{ number_format((float) $entries->sum('farm_weight'), 2) }}</td>
            <td class="text-right text-rose">{{ number_format((float) $entries->sum('loss_weight'), 2) }}</td>
            <td class="text-right">{{ number_format((float) $entries->sum('total_weight'), 2) }}</td>
        </tr>
    </tfoot>
    @endif
</table>

<p style="text-align: center; font-size: 10px; color: #9ca3af; margin-top: 30px;">
    This is a computer-generated document. No signature is required.
</p>
@endsection