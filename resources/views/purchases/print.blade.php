<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Invoice #{{ str_pad($purchase->id, 5, '0', STR_PAD_LEFT) }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            color: #0f172a;
            background: #ffffff;
            line-height: 1.5;
            padding: 40px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 24px;
            margin-bottom: 32px;
        }
        .logo-block h1 {
            font-size: 24px;
            font-weight: 900;
            letter-spacing: -0.04em;
            color: #0f172a;
            text-transform: uppercase;
        }
        .logo-block p {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-top: 2px;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h2 {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1;
        }
        .invoice-id {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            font-weight: 700;
            color: #0d9488;
            margin-top: 4px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 40px;
        }
        .meta-col h4 {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #94a3b8;
            margin-bottom: 6px;
        }
        .meta-col p {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
        }
        .meta-col span {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            display: block;
            margin-top: 2px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 32px;
        }
        th {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            padding: 12px 16px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #475569;
            text-align: left;
        }
        td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            color: #334155;
        }
        tr:last-child td {
            border-bottom: 2px solid #e2e8f0;
        }
        .item-name {
            font-weight: 700;
            color: #0f172a;
        }
        .item-meta {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        .text-right {
            text-align: right;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        .summary-wrapper {
            display: flex;
            justify-content: flex-end;
        }
        .summary-table {
            width: 300px;
            margin-bottom: 0;
        }
        .summary-table td {
            padding: 8px 12px;
            border: none;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }
        .summary-table tr.grand-total td {
            border-top: 2px solid #0f172a;
            padding-top: 12px;
            margin-top: 8px;
            font-size: 16px;
            font-weight: 900;
            color: #0f172a;
        }

        .footer {
            margin-top: 120px;
            padding-top: 24px;
            border-top: 1px dashed #e2e8f0;
            text-align: center;
        }
        .footer p {
            font-size: 10px;
            font-weight: 600;
            color: #94a3b8;
            letter-spacing: 0.05em;
        }

        /* Print controls banner */
        .no-print-banner {
            background: #f0fdfa;
            border: 1px solid #99f6e4;
            padding: 16px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        .no-print-banner-text h3 {
            font-size: 14px;
            font-weight: 800;
            color: #0f766e;
        }
        .no-print-banner-text p {
            font-size: 11px;
            color: #0d9488;
            font-weight: 500;
        }
        .banner-buttons {
            display: flex;
            gap: 10px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-primary {
            background: #0d9488;
            color: #ffffff;
            border: none;
            box-shadow: 0 4px 6px -1px rgba(13, 148, 136, 0.2);
        }
        .btn-primary:hover {
            background: #0f766e;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .btn-secondary:hover {
            background: #f8fafc;
            color: #0f172a;
            transform: translateY(-1px);
        }

        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        
        {{-- Interactive Banner (hidden on actual print) --}}
        <div class="no-print-banner no-print">
            <div class="no-print-banner-text">
                <h3>Print Preview Mode</h3>
                <p>This document is optimized for high-contrast paper or PDF printing.</p>
            </div>
            <div class="banner-buttons">
                <button onclick="window.print()" class="btn btn-primary">Print Document</button>
                <button onclick="window.history.back()" class="btn btn-secondary">Go Back</button>
            </div>
        </div>

        {{-- Invoice Header --}}
        <div class="header">
            <div class="logo-block">
                <h1>MSR BROILERS</h1>
                <p>Farm Management & Analytics</p>
            </div>
            <div class="invoice-title">
                <h2>PURCHASE INVOICE</h2>
                <div class="invoice-id">{{ $purchase->invoice_no ?? '#PUR' . str_pad($purchase->id, 5, '0', STR_PAD_LEFT) }}</div>
            </div>
        </div>

        {{-- Meta details --}}
        <div class="meta-grid">
            <div class="meta-col">
                <h4>Supplier / Vendor</h4>
                <p>{{ $purchase->vendor_name }}</p>
                <span>Account Bill ID: {{ $purchase->invoice_no ?: 'N/A' }}</span>
            </div>
            <div class="meta-col">
                <h4>Billing Date</h4>
                <p>{{ $purchase->date->format('d M, Y') }}</p>
                <span>Logged on: {{ $purchase->created_at->format('d M, Y h:i A') }}</span>
            </div>
            <div class="meta-col">
                <h4>Payment Details</h4>
                <p>{{ $purchase->payment_mode }}</p>
                <span>Refill Clearance: Completed</span>
            </div>
        </div>

        {{-- Procured Items Table --}}
        <table>
            <thead>
                <tr>
                    <th style="width: 45%;">Item Description</th>
                    <th style="text-align: right; width: 15%;">Quantity</th>
                    <th style="text-align: right; width: 20%;">Rate per Unit</th>
                    <th style="text-align: right; width: 20%;">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @php $subtotal = 0; @endphp
                @foreach($purchase->items as $item)
                @php 
                    $itemTotal = $item->quantity * $item->rate; 
                    $subtotal += $itemTotal; 
                @endphp
                <tr>
                    <td>
                        <div class="item-name">{{ $item->item_name }}</div>
                        <div class="item-meta">
                            Warehouse: {{ $item->warehouse->name ?? 'Unspecified' }} 
                            @if($item->batch)
                            | Batch: {{ $item->batch->batch_code }}
                            @endif
                        </div>
                    </td>
                    <td class="text-right font-mono font-bold">{{ number_format($item->quantity, 2) }} {{ $item->unit }}</td>
                    <td class="text-right font-mono">₹{{ number_format($item->rate, 2) }}</td>
                    <td class="text-right font-mono font-bold">₹{{ number_format($itemTotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Financial Calculation Summaries --}}
        <div class="summary-wrapper">
            <table class="summary-table">
                <tr>
                    <td>Subtotal Amount</td>
                    <td class="text-right font-mono">₹{{ number_format($subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td>GST Tax ({{ $purchase->gst_percentage }}%)</td>
                    <td class="text-right font-mono">₹{{ number_format($purchase->gst_amount, 2) }}</td>
                </tr>
                <tr class="grand-total">
                    <td>Grand Net Total</td>
                    <td class="text-right font-mono">₹{{ number_format($purchase->total_amount, 2) }}</td>
                </tr>
            </table>
        </div>

        {{-- Print Footer --}}
        <div class="footer">
            <p>This is a system generated legal print invoice for transaction {{ $purchase->invoice_no ?? '#PUR' . str_pad($purchase->id, 5, '0', STR_PAD_LEFT) }}. No signature is required.</p>
        </div>

    </div>
</body>
</html>
