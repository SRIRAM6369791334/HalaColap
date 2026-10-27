<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Summary - {{ $dateObj->format('d M Y') }}</title>
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

        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 32px; }
        .stat-card {
            padding: 18px 12px; border-radius: 12px; text-align: center;
            border: 1px solid; transition: none;
        }
        .stat-card .s-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px; }
        .stat-card .s-value { font-size: 22px; font-weight: 900; font-family: 'JetBrains Mono', monospace; }
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

        .section-title {
            font-size: 15px; font-weight: 800; color: #0f172a;
            margin-bottom: 12px; margin-top: 28px; display: flex; align-items: center; gap: 8px;
        }
        .section-title .icon { font-size: 18px; }

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
        }
    </style>
</head>
<body>
<div class="container">

<div class="toolbar no-print">
    <button onclick="window.print()" class="btn-print">Print</button>
    <a href="{{ route('purchases.invoices.pdf', $dateObj->format('Y-m-d')) }}" class="btn-pdf">Download PDF</a>
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
        <div class="badge"><h2>Daily Invoice Summary</h2></div>
        <div class="label">Date</div>
        <div class="value">{{ $dateObj->format('d M Y') }}</div>
        <div class="day">{{ $dateObj->format('l') }}</div>
    </div>
</div>

<div class="stats">
    <div class="stat-card sc-blue">
        <div class="s-label">Purchases</div>
        <div class="s-value">{{ $purchaseCount }}</div>
    </div>
    <div class="stat-card sc-green">
        <div class="s-label">Purchase Total</div>
        <div class="s-value">Rs {{ number_format($purchaseTotal, 0) }}</div>
    </div>
    <div class="stat-card sc-amber">
        <div class="s-label">Day-Load Batches</div>
        <div class="s-value">{{ isset($dayLoadBatches) ? $dayLoadBatches->count() : ($dayLoadBatch ? 1 : 0) }}</div>
    </div>
    <div class="stat-card sc-violet">
        <div class="s-label">Birds Loaded</div>
        <div class="s-value">{{ isset($dayLoadBatches) ? $dayLoadBatches->sum('total_boxes') : ($dayLoadBatch?->total_boxes ?? 0) }} <span style="font-size:12px;">boxes</span></div>
    </div>
</div>

@if($purchases->count() > 0)
<div class="section-title">Purchase Invoices</div>
<table>
    <thead>
        <tr>
            <th style="width:40px;">#</th>
            <th>Vendor</th>
            <th>Invoice No</th>
            <th>Items</th>
            <th class="text-right">Amount</th>
            <th class="text-right">Mode</th>
        </tr>
    </thead>
    <tbody>
        @foreach($purchases as $idx => $p)
        <tr>
            <td class="text-center font-mono text-muted">{{ $idx + 1 }}</td>
            <td><strong>{{ $p->vendor_name }}</strong></td>
            <td class="font-mono text-muted">{{ $p->invoice_no ?: '—' }}</td>
            <td>
                @php $firstItem = $p->items->first(); $othersCount = $p->items->count() - 1; @endphp
                @if($firstItem)
                    {{ $firstItem->item_name }} ({{ number_format($firstItem->quantity) }} {{ $firstItem->unit }})
                    @if($othersCount > 0)<span class="text-muted"> +{{ $othersCount }} more</span>@endif
                @else <span class="text-muted">—</span> @endif
            </td>
            <td class="text-right font-bold font-mono">Rs {{ number_format((float) $p->total_amount, 2) }}</td>
            <td class="text-right text-muted">{{ $p->payment_mode }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" style="text-transform:uppercase;font-size:11px;">Total</td>
            <td class="text-right font-bold font-mono text-emerald">Rs {{ number_format($purchases->sum('total_amount'), 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
@endif

@if($dayLoadBatch && $dayLoadEntries->count() > 0)
<div class="section-title">Day-Load Entries</div>
<table>
    <thead>
        <tr>
            <th style="width:40px;">#</th>
            <th>Vendor</th>
            <th>Dealer</th>
            <th class="text-center">Boxes</th>
            <th class="text-right">Box Wt</th>
            <th class="text-right">Empty Wt</th>
            <th class="text-right">Bird Wt</th>
            <th class="text-right">Customer Rate</th>
            <th class="text-right">Total Rate</th>
        </tr>
    </thead>
    <tbody>
        @foreach($dayLoadEntries as $idx => $entry)
        <tr>
            <td class="text-center font-mono text-muted">
                @if($entry->sort_order)
                    <strong style="color:#d97706;">#{{ $entry->sort_order }}</strong>
                @else
                    {{ $idx + 1 }}
                @endif
            </td>
            <td><strong>{{ $entry->vendor->firm_name ?? '-' }}</strong></td>
            <td>{{ $entry->dealer->firm_name ?? '-' }}</td>
            <td class="text-center font-bold font-mono">{{ $entry->no_of_boxes }}</td>
            <td class="text-right font-mono">{{ number_format((float) ($entry->box_weight ?? 0), 2) }}</td>
            <td class="text-right font-mono">{{ number_format((float) ($entry->empty_weight ?? 0), 2) }}</td>
            <td class="text-right font-mono">{{ number_format((float) $entry->bird_weight, 2) }}</td>
            <td class="text-right font-mono">Rs {{ number_format((float) $entry->customer_rate, 2) }}</td>
            <td class="text-right font-mono font-bold text-emerald">Rs {{ number_format((float) $entry->amount, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="text-transform:uppercase;font-size:11px;">Total</td>
            <td class="text-center font-bold font-mono" style="color:#2563eb;">{{ $dayLoadEntries->sum('no_of_boxes') }}</td>
            <td class="text-right font-bold font-mono">{{ number_format((float) $dayLoadEntries->sum('box_weight'), 2) }}</td>
            <td class="text-right font-bold font-mono">{{ number_format((float) $dayLoadEntries->sum('empty_weight'), 2) }}</td>
            <td class="text-right font-bold font-mono">{{ number_format((float) $dayLoadEntries->sum('bird_weight'), 2) }}</td>
            <td class="text-right font-bold font-mono">—</td>
            <td class="text-right font-bold font-mono text-emerald">Rs {{ number_format((float) $dayLoadEntries->sum('amount'), 2) }}</td>
        </tr>
        @php
            $farmWt = (float)(isset($dayLoadBatches) ? $dayLoadBatches->sum('total_farm_weight') : ($dayLoadBatch?->total_farm_weight ?? $dayLoadEntries->sum('farm_weight') ?? 0));
            $birdWt = (float)(isset($dayLoadBatches) ? $dayLoadBatches->sum('total_bird_weight') : ($dayLoadBatch?->total_bird_weight ?? $dayLoadEntries->sum('bird_weight') ?? 0));
            $lossWt = (float)(isset($dayLoadBatches) ? $dayLoadBatches->sum('total_loss_weight') : ($dayLoadBatch?->total_loss_weight ?? 0));
            if ($lossWt == 0 && $farmWt > $birdWt) {
                $lossWt = round($farmWt - $birdWt, 2);
            }
            $lossPct = $farmWt > 0 ? round(($lossWt / $farmWt) * 100, 2) : 0;
        @endphp
        @if($farmWt > 0 || $lossWt > 0)
        <tr style="background:#fffbeb;font-weight:bold;font-size:12px;border-top:1px solid #fde68a;">
            <td colspan="3" style="color:#b45309;">Farm & Weight Loss:</td>
            <td colspan="2" class="text-center">Farm Wt: <strong>{{ number_format($farmWt, 2) }} kg</strong></td>
            <td colspan="2" class="text-center" style="color:#e11d48;">Loss Wt: <strong>- {{ number_format($lossWt, 2) }} kg @if($lossPct > 0)({{ $lossPct }}%)@endif</strong></td>
            <td colspan="2" class="text-right" style="color:#047857;">Billed Net: <strong>{{ number_format($birdWt, 2) }} kg</strong></td>
        </tr>
        @endif
    </tfoot>
</table>
@endif

<div class="footer">
    This is a computer-generated document. No signature is required.<br>
    Generated on {{ now()->format('d M Y, h:i A') }}
</div>

</div>
</div>
</body>
</html>
