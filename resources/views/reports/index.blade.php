@extends('layouts.app')
@section('title', 'Reports')

@section('content')
<x-page-header 
    title="Reports" 
    subtitle="Business summary and analytics" />

<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
    @php
        $tiles = [
            ['label'=>'Total Customers',  'value'=>$summary['total_customers'],   'icon'=>'ph-users', 'color' => 'sky'],
            ['label'=>'Total Dealers',    'value'=>$summary['total_dealers'],     'icon'=>'ph-buildings', 'color' => 'amber'],
            ['label'=>'Revenue (Month)',  'value'=>'Rs '.number_format($summary['total_revenue_month'],0,'.', ','),   'icon'=>'ph-trend-up', 'color' => 'emerald'],
            ['label'=>'Purchases (Month)','value'=>'Rs '.number_format($summary['total_purchases_month'],0,'.', ','),'icon'=>'ph-shopping-cart', 'color' => 'rose'],
            ['label'=>'Expenses (Month)', 'value'=>'Rs '.number_format($summary['total_expenses_month'],0,'.', ','), 'icon'=>'ph-money', 'color' => 'orange'],
            ['label'=>'Pending Receivables','value'=>'Rs '.number_format($summary['pending_receivables'],0,'.', ','),'icon'=>'ph-hourglass-high', 'color' => 'indigo'],
            ['label'=>'Pending Payables',  'value'=>'Rs '.number_format($summary['pending_payables'],0,'.', ','),   'icon'=>'ph-credit-card', 'color' => 'violet'],
        ];
    @endphp
    @foreach($tiles as $tile)
        <x-stat-card 
            title="{{ $tile['label'] }}" 
            value="{{ $tile['value'] }}" 
            icon="{{ $tile['icon'] }}" 
            color="{{ $tile['color'] }}" 
        />
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Top Customers by Balance --}}
    <x-card title="Top Customers by Outstanding Balance">
        <x-data-table>
            <x-slot name="head">
                <tr>
                    <th>Customer Name</th>
                    <th class="text-right">Balance</th>
                </tr>
            </x-slot>
            @forelse($topCustomers as $c)
                <tr>
                    <td class="font-medium text-zinc-950">{{ $c->name }}</td>
                    <td class="text-right font-mono font-bold text-red-600"><x-currency :amount="$c->balance" /></td>
                </tr>
            @empty
                <tr><td colspan="2" class="text-center text-zinc-500 py-6">No outstanding balances</td></tr>
            @endforelse
        </x-data-table>
    </x-card>

    {{-- Top Dealers by Pending Amount --}}
    <x-card title="Top Dealers by Pending Payment">
        <x-data-table>
            <x-slot name="head">
                <tr>
                    <th>Dealer Name</th>
                    <th class="text-right">Pending Amount</th>
                </tr>
            </x-slot>
            @forelse($topDealers as $d)
                <tr>
                    <td class="font-medium text-zinc-950">{{ $d->firm_name }}</td>
                    <td class="text-right font-mono font-bold text-amber-600"><x-currency :amount="$d->displayed_outstanding" /></td>
                </tr>
            @empty
                <tr><td colspan="2" class="text-center text-zinc-500 py-6">No pending payments</td></tr>
            @endforelse
        </x-data-table>
    </x-card>
</div>
@endsection
