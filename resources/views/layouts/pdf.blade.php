<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Report')</title>
    <style>
        @page {
            margin: 40px;
            font-family: 'Helvetica Neue', 'Helvetica', Arial, sans-serif;
        }
        body {
            font-family: 'Helvetica Neue', 'Helvetica', Arial, sans-serif;
            color: #333;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        /* Header */
        .header {
            width: 100%;
            border-bottom: 2px solid #10b981; /* Emerald 500 */
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        .header td {
            vertical-align: middle;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #064e3b; /* Emerald 900 */
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }
        .company-details {
            font-size: 10px;
            color: #6b7280; /* Gray 500 */
            margin: 0;
            line-height: 1.4;
        }
        .report-title-container {
            text-align: right;
        }
        .report-title {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937; /* Gray 800 */
            text-transform: uppercase;
            margin: 0 0 5px 0;
        }
        .report-meta {
            font-size: 10px;
            color: #6b7280;
            margin: 0;
        }

        /* Common Elements */
        h2 {
            color: #064e3b;
            font-size: 14px;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }
        
        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th, .data-table td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            text-align: left;
            font-size: 11px;
        }
        .data-table th {
            background-color: #f3f4f6; /* Gray 100 */
            color: #374151; /* Gray 700 */
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.05em;
        }
        .data-table tr:nth-child(even) {
            background-color: #f9fafb; /* Gray 50 */
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }
        .font-bold {
            font-weight: bold !important;
        }
        
        /* Colors */
        .text-emerald { color: #10b981; }
        .text-rose { color: #f43f5e; }
        .text-amber { color: #f59e0b; }
        
        /* Footer */
        .footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 30px;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
        }
        .page-number:after {
            content: counter(page);
        }

        /* Utilities */
        .mb-2 { margin-bottom: 10px; }
        .mt-2 { margin-top: 10px; }
        .w-100 { width: 100%; }
        .w-50 { width: 50%; }
        
        /* Summary Cards */
        .summary-grid {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: separate;
            border-spacing: 10px 0;
        }
        .summary-card {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 10px;
            border-radius: 4px;
            vertical-align: top;
        }
        .summary-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
        }
    </style>
    @stack('styles')
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td style="width: 50%;">
                    <h1 class="company-name">{{ config('app.name', 'MSR BROILERS ') }}</h1>
                    <p class="company-details">
                        123 MSR BROILERS Farm Road<br>
                        Industrial Area, Tech City - 600001<br>
                        Phone: +91 98765 43210<br>
                        Email: info@MSR BROILERS.local
                    </p>
                </td>
                <td style="width: 50%;" class="report-title-container">
                    <h2 class="report-title">@yield('title')</h2>
                    <p class="report-meta">
                        Generated On: {{ now()->format('d M Y, h:i A') }}<br>
                        @yield('meta')
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        @yield('content')
    </div>

    <div class="footer">
        {{ config('app.name', 'MSR BROILERS ') }} - Generated System Report &bull; Page <span class="page-number"></span>
    </div>

</body>
</html>
