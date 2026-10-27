<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Customer Directory - MSR BROILERS Management</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #333; margin: 0; padding: 0; }
        .header-accent { height: 8px; background: #059669; }
        .container { padding: 30px; }
        .company-header { margin-bottom: 25px; }
        .company-name { font-size: 20px; font-weight: bold; color: #059669; margin: 0; }
        .report-title { font-size: 12px; font-weight: bold; text-transform: uppercase; color: #111; margin-top: 5px; }
        
        .summary-boxes { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .summary-box { padding: 12px; background: #f9fafb; border-radius: 8px; border: 1px solid #f3f4f6; }
        .label { font-size: 8px; font-weight: bold; color: #6b7280; text-transform: uppercase; margin-bottom: 3px; }
        .value { font-size: 14px; font-weight: bold; color: #111; }
        
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th { background-color: #111; color: #fff; padding: 8px 10px; text-align: left; font-size: 8px; text-transform: uppercase; }
        table.data-table td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-wholesale { background-color: #e0f2fe; color: #0369a1; }
        .badge-retail { background-color: #d1fae5; color: #065f46; }
        
        .balance-due { color: #dc2626; font-weight: bold; }
        .balance-zero { color: #999; }
        
        .footer { position: fixed; bottom: 30px; left: 30px; right: 30px; border-top: 1px solid #f3f4f6; padding-top: 15px; text-align: center; font-size: 8px; color: #999; }
    </style>
</head>
<body>
    <div class="header-accent"></div>
    <div class="container">
        <div class="company-header">
            <h1 class="company-name">MSR BROILERS Management</h1>
            <div class="report-title">Customer Directory</div>
            <div style="font-size: 8px; color: #999; margin-top: 5px;">
                Generated on: {{ now()->format('d M Y, h:i A') }}
            </div>
        </div>

        <table class="summary-boxes">
            <tr>
                <td style="width: 50%; padding: 0 10px 0 0;">
                    <div class="summary-box">
                        <div class="label">Total Registered Customers</div>
                        <div class="value">{{ count($customers) }}</div>
                    </div>
                </td>
                <td style="width: 50%; padding: 0 0 0 10px;">
                    <div class="summary-box" style="border-left: 4px solid #dc2626;">
                        <div class="label">Total Outstanding Balance</div>
                        <div class="value" style="color: #dc2626;">Rs {{ number_format($customers->sum('balance'), 2) }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 6%; border-radius: 6px 0 0 0;" class="text-center">S.No</th>
                    <th style="width: 28%;">Customer Name</th>
                    <th style="width: 15%;">Contact No</th>
                    <th style="width: 23%;">Address</th>
                    <th style="width: 15%;">Route</th>
                    <th style="width: 13%; border-radius: 0 6px 0 0;" class="text-right">Outstanding</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $index => $customer)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <span class="font-bold">{{ $customer->name }}</span>
                            @if($customer->type)
                                <br>
                                <span class="badge {{ $customer->type === 'Wholesale' ? 'badge-wholesale' : 'badge-retail' }}">
                                    {{ $customer->type }}
                                </span>
                            @endif
                        </td>
                        <td>{{ $customer->phone ?: '—' }}</td>
                        <td>{{ $customer->address ?: '—' }}</td>
                        <td>{{ $customer->routeRelation ? $customer->routeRelation->route_name : ($customer->route ?: '—') }}</td>
                        <td class="text-right font-bold">
                            @if($customer->balance > 0)
                                <span class="balance-due">Rs {{ number_format($customer->balance, 2) }}</span>
                            @else
                                <span class="balance-zero">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer">
            MSR BROILERS Management ERP &bull; CUSTOMER DIRECTORY &bull; COMPUTER GENERATED REPORT
        </div>
    </div>
</body>
</html>
