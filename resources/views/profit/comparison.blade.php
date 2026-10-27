@extends('layouts.app')
@section('title', 'Financial Comparison')
@section('content')
<div class="mb-2">
    <a href="{{ route('profit.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 uppercase tracking-wider inline-block">← Back to Overview</a>
</div>
<x-page-header 
    title="Year-on-Year Comparison" 
    subtitle="Comparative financial metrics against previous fiscal periods" />

<x-card class="!bg-emerald-50 !border-emerald-200 text-center py-12">
    <i class="ph ph-chart-line-up text-4xl text-emerald-500 mb-4 inline-block"></i>
    <h3 class="text-lg font-bold text-emerald-900 mb-2">Comparative Analytics Ready Soon</h3>
    <p class="text-emerald-700 max-w-md mx-auto">Historical data synchronization is in progress. This view will soon provide multi-period growth insights.</p>
</x-card>
@endsection
