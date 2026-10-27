@extends('layouts.pdf')
@section('title', 'Profit & Loss Statement')
@section('meta', 'Period: ' . \Carbon\Carbon::parse($startDate)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($endDate)->format('d M Y'))

@section('content')

<table class="summary-grid">
    <tr>
        <td>
            <div class="summary-card">
                <div class="summary-label">Total Revenue</div>
                <div class="summary-value text-emerald">Rs {{ number_format($summary['revenue'] ?? 0, 2) }}</div>
            </div>
        </td>
        <td>
            <div class="summary-card">
                <div class="summary-label">Procurement Cost</div>
                <div class="summary-value text-rose">Rs {{ number_format($summary['purchase'] ?? 0, 2) }}</div>
            </div>
        </td>
        <td>
            <div class="summary-card">
                <div class="summary-label">Operating Expenses</div>
                <div class="summary-value text-rose">Rs {{ number_format($summary['expenses'] ?? 0, 2) }}</div>
            </div>
        </td>
        <td>
            <div class="summary-card" style="border-left: 3px solid #10b981;">
                <div class="summary-label">Net Profit</div>
                <div class="summary-value text-emerald" style="font-size: 16px;">Rs {{ number_format($summary['profit'] ?? 0, 2) }}</div>
            </div>
        </td>
    </tr>
</table>

<h2>Weekly Performance Breakdown</h2>
<table class="data-table">
    <thead>
        <tr>
            <th>Week Period</th>
            <th class="text-right">Revenue</th>
            <th class="text-right">Procurement</th>
            <th class="text-right">Expenses</th>
            <th class="text-right">Weekly Profit</th>
        </tr>
    </thead>
    <tbody>
        @foreach($weeklyData as $week)
        <tr>
            <td class="font-bold">{{ $week['week'] }}</td>
            <td class="text-right text-emerald font-bold">Rs {{ number_format($week['revenue'], 2) }}</td>
            <td class="text-right text-rose">Rs {{ number_format($week['purchase'], 2) }}</td>
            <td class="text-right text-rose">Rs {{ number_format($week['expenses'], 2) }}</td>
            <td class="text-right font-bold {{ $week['profit'] >= 0 ? 'text-emerald' : 'text-rose' }}">
                Rs {{ number_format($week['profit'], 2) }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="mt-2 w-50">
    <h2>Category Wise Distribution</h2>
    <table class="data-table">
        <tr>
            <td>Total Sales Volume</td>
            <td class="text-right font-bold">{{ number_format($breakdown['sales_qty'] ?? 0, 2) }} KG</td>
        </tr>
        <tr>
            <td>Avg Rate Realized</td>
            <td class="text-right font-bold">Rs {{ number_format($breakdown['avg_rate'] ?? 0, 2) }} / KG</td>
        </tr>
        <tr>
            <td>Mortality Loss Valuation</td>
            <td class="text-right text-rose font-bold">Rs {{ number_format($breakdown['mortality_valuation'] ?? 0, 2) }}</td>
        </tr>
    </table>
</div>

@endsection
