<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Day-Load Invoice - {{ $dateObj->format('d M Y') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            color: #0f172a;
            background: #fff;
            line-height: 1.6;
            padding: 48px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .container { max-width: 900px; margin: 0 auto; }

        .toolbar { display: flex; gap: 10px; margin-bottom: 32px; align-items: center; }
        .toolbar a, .toolbar button {
            display: inline-flex; align-items: center; gap: 6px;
            min-height: 44px;
            padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 600;
            text-decoration: none; cursor: pointer; border: none; transition: all .2s;
        }
        .toolbar .btn-print { background: #059669; color: #fff; }
        .toolbar .btn-print:hover { background: #047857; }
        .toolbar .btn-pdf { background: #2563eb; color: #fff; }
        .toolbar .btn-pdf:hover { background: #1d4ed8; }

        .invoice-wrap {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 4px 24px rgba(0,0,0,.04);
        }

        .header {
            display: flex; justify-content: space-between; align-items: flex-start;
            border-bottom: 3px solid #0f172a; padding-bottom: 24px; margin-bottom: 28px;
        }
        .brand h1 { font-size: 26px; font-weight: 900; letter-spacing: -.03em; text-transform: uppercase; color: #0f172a; }
        .brand h1 span { color: #059669; }
        .brand .tag { font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .12em; margin-top: 2px; }
        .brand .address { margin-top: 14px; font-size: 12px; color: #64748b; line-height: 1.6; }
        .brand .address strong { color: #0f172a; }
        .meta { text-align: right; }
        .meta .badge {
            display: inline-block; background: #ecfdf5; color: #059669;
            padding: 6px 18px; border-radius: 10px; margin-bottom: 10px;
        }
        .meta .badge h2 { font-size: 16px; font-weight: 800; text-transform: uppercase; letter-spacing: -.01em; }
        .meta .label { font-size: 10px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; }
        .meta .value { font-size: 16px; font-weight: 700; color: #0f172a; font-family: 'JetBrains Mono', monospace; margin-top: 2px; }
        .meta .day { font-size: 13px; color: #64748b; margin-top: 2px; }
        .meta .batch-ref { margin-top: 12px; padding-top: 12px; border-top: 1px solid #e2e8f0; }
        .meta .batch-ref .value { font-size: 13px; }

        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 32px; }
        .stat-card {
            padding: 18px 12px; border-radius: 12px; text-align: center;
            border: 1px solid; transition: none;
        }
        .stat-card .s-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px; }
        .stat-card .s-value { font-size: 22px; font-weight: 900; font-family: 'JetBrains Mono', monospace; }
        .stat-card .s-unit { font-size: 12px; font-weight: 600; }
        .sc-blue { background: #eff6ff; border-color: #bfdbfe; }
        .sc-blue .s-label { color: #2563eb; }
        .sc-blue .s-value { color: #1d4ed8; }
        .sc-green { background: #ecfdf5; border-color: #a7f3d0; }
        .sc-green .s-label { color: #059669; }
        .sc-green .s-value { color: #047857; }
        .sc-amber { background: #fffbeb; border-color: #fde68a; }
        .sc-amber .s-label { color: #d97706; }
        .sc-amber .s-value { color: #b45309; }
        .sc-violet { background: #f5f3ff; border-color: #ddd6fe; }
        .sc-violet .s-label { color: #7c3aed; }
        .sc-violet .s-value { color: #6d28d9; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead th {
            background: #f8fafc; font-size: 10px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .06em; color: #64748b; padding: 12px 16px;
            text-align: left; border-bottom: 2px solid #e2e8f0;
        }
        tbody td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        tbody tr:nth-child(even) { background: #fafbfc; }
        tfoot td {
            padding: 14px 16px; border-top: 2px solid #e2e8f0;
            font-weight: 700; background: #f8fafc; font-size: 13px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .text-muted { color: #94a3b8; }
        .text-emerald { color: #059669; }
        .text-rose { color: #e11d48; }

        .footer {
            text-align: center; font-size: 10px; color: #94a3b8;
            padding-top: 20px; border-top: 1px solid #e2e8f0; margin-top: 24px;
        }

        .no-print { display: flex; }
        @media screen and (max-width: 720px) {
            body { padding: 16px; }
            .toolbar { flex-wrap: wrap; }
            .toolbar a, .toolbar button { flex: 1 1 auto; justify-content: center; }
            .invoice-wrap { padding: 20px; overflow-x: auto; }
            .header { flex-direction: column; gap: 20px; }
            .meta { text-align: left; }
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media print {
            body { padding: 0; background: #fff; }
            .toolbar { display: none !important; }
            .invoice-wrap { border: none; box-shadow: none; padding: 20px 0; }
            .stat-card { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            thead th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tbody tr:nth-child(even) { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tfoot td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .sc-blue, .sc-green, .sc-amber, .sc-violet { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
<div class="container">

<div class="toolbar no-print">
    <button onclick="window.print()" class="btn-print">Print</button>
    <a href="{{ route('billing.day-load.pdf', $dateObj->format('Y-m-d')) }}" class="btn-pdf">Download PDF</a>
</div>

<div class="invoice-wrap">

<div class="header">
    <div class="brand">
        <h1>MSR BROILERS<span>Pro</span></h1>
        <div class="tag">MSR BROILERS Management Solutions</div>
        <div class="address">
            <strong>MSR BROILERS Farm Unit #1</strong><br>
            Tamil Nadu, India &bull; +91 98765 43210
        </div>
    </div>
    <div class="meta">
        <div class="badge"><h2>Day-Load Invoice</h2></div>
        <div class="label">Billing Date</div>
        <div class="value">{{ $dateObj->format('d M Y') }}</div>
        <div class="day">{{ $dateObj->format('l') }}</div>
        @php
            $batchIds = $entries->pluck('batch_id')->unique()->values();
        @endphp
        @if($batchIds->count() > 1)
        <div class="batch-ref">
            <div class="label">Batches Included</div>
            <div class="value">
                @foreach($batchIds as $bId)
                    #BAT{{ str_pad($bId, 4, '0', STR_PAD_LEFT) }}@if(!$loop->last), @endif
                @endforeach
                ({{ $batchIds->count() }} Batches)
            </div>
        </div>
        @elseif($batch)
        <div class="batch-ref">
            <div class="label">Batch Reference</div>
            <div class="value">#BAT{{ str_pad($batch->id, 4, '0', STR_PAD_LEFT) }}</div>
        </div>
        @endif
    </div>
</div>

<div class="stats">
    <div class="stat-card sc-blue">
        <div class="s-label">Total Boxes</div>
        <div class="s-value">{{ $entries->sum('no_of_boxes') }}</div>
    </div>
    <div class="stat-card sc-green">
        <div class="s-label">Bird Weight</div>
        <div class="s-value">{{ number_format((float) $entries->sum('bird_weight'), 2) }} <span class="s-unit">kg</span></div>
    </div>
    <div class="stat-card sc-amber">
        <div class="s-label">Farm Weight</div>
        <div class="s-value">{{ number_format((float) $entries->sum('farm_weight'), 2) }} <span class="s-unit">kg</span></div>
    </div>
    <div class="stat-card sc-violet">
        <div class="s-label">Loss Weight</div>
        <div class="s-value">{{ number_format((float) $entries->sum('loss_weight'), 2) }} <span class="s-unit">kg</span></div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:36px;">#</th>
            <th>Vendor</th>
            <th>Dealer</th>
            <th class="text-center" style="width:70px;">Boxes</th>
            <th class="text-right" style="width:100px;">Bird Wt</th>
            <th class="text-right" style="width:100px;">Farm Wt</th>
            <th class="text-right" style="width:90px;">Loss</th>
            <th class="text-right" style="width:100px;">Total</th>
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
            <tr style="background:#f8fafc;border-top:2px solid #cbd5e1;border-bottom:1px solid #e2e8f0;">
                <td colspan="8" style="padding:10px 14px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                        <div>
                            <span style="font-weight:900;font-size:12px;color:#0f172a;text-transform:uppercase;letter-spacing:0.04em;">
                                🚚 BATCH #{{ $loop->iteration }} (#BAT{{ str_pad($batchId, 4, '0', STR_PAD_LEFT) }}) &bull; {{ $batchVendor }}
                            </span>
                            <span style="font-size:10px;color:#64748b;font-weight:700;margin-left:6px;">({{ $bEntries->count() }} {{ \Illuminate\Support\Str::plural('entry', $bEntries->count()) }})</span>
                        </div>
                        <div style="font-size:11px;font-family:'JetBrains Mono', monospace;display:flex;gap:12px;color:#475569;">
                            <span>Boxes: <strong>{{ $bEntries->sum('no_of_boxes') }}</strong></span>
                            <span>Bird Wt: <strong>{{ number_format($batchBird, 2) }} kg</strong></span>
                            @if($batchFarm > 0)
                                <span style="color:#b45309;">Farm: <strong>{{ number_format($batchFarm, 2) }} kg</strong></span>
                                <span style="color:#e11d48;">Loss: <strong>-{{ number_format($batchLoss, 2) }} kg @if($batchLossPct > 0)({{ $batchLossPct }}%)@endif</strong></span>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
            @foreach($bEntries as $entry)
            <tr>
                <td class="text-center font-mono text-muted">{{ $entry->sort_order ?: $rowCounter++ }}</td>
                <td><strong>{{ $entry->vendor->firm_name ?? '-' }}</strong></td>
                <td>{{ $entry->dealer->firm_name ?? '-' }}</td>
                <td class="text-center font-bold font-mono">{{ $entry->no_of_boxes }}</td>
                <td class="text-right font-mono">{{ number_format((float) $entry->bird_weight, 2) }}</td>
                <td class="text-right font-mono">{{ $entry->farm_weight ? number_format((float) $entry->farm_weight, 2) : '—' }}</td>
                <td class="text-right font-mono" style="{{ ($entry->loss_weight ?? 0) > 0 ? 'color:#e11d48;font-weight:700;' : 'color:#94a3b8;' }}">{{ $entry->loss_weight ? number_format((float) $entry->loss_weight, 2) : '—' }}</td>
                <td class="text-right font-mono font-bold" style="{{ $entry->total_weight ? 'color:#059669;' : 'color:#94a3b8;' }}">{{ $entry->total_weight ? number_format((float) $entry->total_weight, 2) : '—' }}</td>
            </tr>
            @endforeach
            @if($groupedByBatch->count() > 1)
            <tr style="background:#f1f5f9;font-weight:700;font-size:11px;border-bottom:2px solid #cbd5e1;">
                <td colspan="3" style="text-transform:uppercase;color:#64748b;padding:8px 14px;">Subtotal (Batch #{{ $loop->iteration }})</td>
                <td class="text-center font-mono" style="color:#2563eb;">{{ $bEntries->sum('no_of_boxes') }}</td>
                <td class="text-right font-mono">{{ number_format((float) $bEntries->sum('bird_weight'), 2) }}</td>
                <td class="text-right font-mono">{{ number_format((float) $bEntries->sum('farm_weight'), 2) }}</td>
                <td class="text-right font-mono text-rose">{{ number_format((float) $bEntries->sum('loss_weight'), 2) }}</td>
                <td class="text-right font-mono text-emerald">{{ number_format((float) $bEntries->sum('total_weight'), 2) }}</td>
            </tr>
            @endif
        @empty
        <tr>
            <td colspan="8" class="text-center" style="padding:30px;color:#94a3b8;">No entries found for this date.</td>
        </tr>
        @endforelse
    </tbody>
    @if($entries->count() > 0)
    <tfoot>
        <tr style="border-top:2px solid #0f172a;background:#f8fafc;font-weight:900;">
            <td colspan="3" style="text-transform:uppercase;font-size:11px;">Grand Totals (All Batches)</td>
            <td class="text-center font-bold font-mono" style="color:#2563eb;">{{ $entries->sum('no_of_boxes') }}</td>
            <td class="text-right font-bold font-mono">{{ number_format((float) $entries->sum('bird_weight'), 2) }}</td>
            <td class="text-right font-bold font-mono">{{ number_format((float) $entries->sum('farm_weight'), 2) }}</td>
            <td class="text-right font-bold font-mono text-rose">{{ number_format((float) $entries->sum('loss_weight'), 2) }}</td>
            <td class="text-right font-bold font-mono text-emerald">{{ number_format((float) $entries->sum('total_weight'), 2) }}</td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="footer">
    This is a computer-generated document. No signature is required.<br>
    Generated on {{ now()->format('d M Y, h:i A') }}
</div>

</div>
</div>
</body>
</html>
