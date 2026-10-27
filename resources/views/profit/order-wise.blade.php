@extends('layouts.app')
@section('title', 'Order-wise Profitability')
@section('content')
<div class="mb-2">
    <a href="{{ route('profit.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 uppercase tracking-wider inline-block">← Back to Overview</a>
</div>
<x-page-header 
    title="Order-wise Profit Tracking" 
    subtitle="Individual profitability metrics for every customer order" />

<x-card class="!bg-blue-50 !border-blue-200 text-center py-12">
    <i class="ph ph-receipt text-4xl text-blue-500 mb-4 inline-block"></i>
    <h3 class="text-lg font-bold text-blue-900 mb-2">Coming Soon: Transactional Deep-dive</h3>
    <p class="text-blue-700 max-w-md mx-auto">We are refining the order-level ledger integration to provide precise margin calculations for every sale.</p>
</x-card>
@endsection
