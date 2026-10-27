<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; margin: 0; padding: 0; }
        .header-accent { height: 8px; background: #4f46e5; }
        .container { padding: 30px; }
        .header { margin-bottom: 30px; }
        .company-name { font-size: 24px; font-weight: bold; color: #4f46e5; margin: 0; letter-spacing: -1px; }
        .report-title { font-size: 14px; font-weight: bold; text-transform: uppercase; color: #111; margin-top: 5px; }
        .meta { color: #999; font-size: 9px; margin-top: 5px; }
        
        .summary-grid { width: 100%; margin-bottom: 30px; border-collapse: separate; border-spacing: 10px 0; margin-left: -10px; }
        .summary-card { background: #f9fafb; padding: 15px; border-radius: 10px; border: 1px solid #f3f4f6; }
        .summary-label { font-size: 8px; font-weight: bold; color: #6b7280; text-transform: uppercase; margin-bottom: 5px; }
        .summary-value { font-size: 16px; font-weight: bold; color: #111827; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #111; color: #fff; padding: 10px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 12px 10px; border-bottom: 1px solid #f3f4f6; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        .footer { position: fixed; bottom: 30px; left: 30px; right: 30px; border-top: 1px solid #f3f4f6; padding-top: 15px; text-align: center; font-size: 9px; color: #999; }
    </style>
</head>
<body>
    <div class="header-accent"></div>
    <div class="container">
        <div class="header">
            <h1 class="company-name">MSR BROILERS Management</h1>
            <div class="report-title">{{ $title }}</div>
            <div class="meta">
                Procurement Audit Report &bull; Generated on {{ now()->format('d M Y, h:i A') }} &bull; Admin: {{ auth()->user()->name ?? 'System' }}
            </div>
        </div>

        <table class="summary-grid">
            <tr>
                <td style="width: 33.33%;">
                    <div class="summary-card">
                        <div class="summary-label">Total Procurements</div>
                        <div class="summary-value">{{ $data->count() }}</div>
                    </div>
                </td>
                <td style="width: 33.33%;">
                    <div class="summary-card">
                        <div class="summary-label">Total Tax Paid</div>
                        <div class="summary-value">Rs {{ number_format($data->sum('gst_amount'), 2) }}</div>
                    </div>
                </td>
                <td style="width: 33.33%;">
                    <div class="summary-card" style="border-left: 4px solid #4f46e5;">
                        <div class="summary-label">Total Investment</div>
                        <div class="summary-value" style="color: #4f46e5;">Rs {{ number_format($data->sum('total_amount'), 2) }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th style="border-radius: 8px 0 0 0;">Vendor / Supplier</th>
                    <th>Ref Date</th>
                    <th>Item Category</th>
                    <th class="text-right">Quantity</th>
                    <th class="text-right" style="border-radius: 0 8px 0 0;">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $row)
                <tr>
                    <td>
                        <div class="font-bold">{{ $row->vendor->firm_name ?? 'Direct Purchase' }}</div>
                        <div style="font-size: 8px; color: #999; margin-top: 2px;">VND: {{ $row->vendor->vendor_code ?? 'N/A' }}</div>
                    </td>
                    <td>
                        <div>{{ $row->date->format('d M Y') }}</div>
                        <div style="font-size: 8px; color: #999; margin-top: 2px;">REF: #{{ $row->id }}</div>
                    </td>
                    <td>
                        <div style="text-transform: capitalize;">{{ $row->item }}</div>
                    </td>
                    <td class="text-right">
                        {{ number_format($row->quantity, 2) }} {{ $row->unit ?? 'units' }}
                    </td>
                    <td class="text-right font-bold">Rs {{ number_format($row->total_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer">
            MSR BROILERS Management ERP &bull; PROCUREMENT RECORD &bull; INTERNAL AUDIT ONLY
        </div>
    </div>
</body>
</html>
