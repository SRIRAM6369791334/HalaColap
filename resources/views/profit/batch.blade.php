@extends('layouts.app')
@section('title', 'Batch-wise Profit Analysis')
@section('content')
<div class="mb-2">
    <a href="{{ route('profit.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 uppercase tracking-wider inline-block">← Back to Overview</a>
</div>
<x-page-header 
    title="Batch Performance Analysis" 
    subtitle="Detailed profitability tracking per MSR BROILERS batch" />

<x-card class="!bg-amber-50 !border-amber-200 text-center py-12">
    <i class="ph ph-egg text-4xl text-amber-500 mb-4 inline-block"></i>
    <h3 class="text-lg font-bold text-amber-900 mb-2">Module Under Construction</h3>
    <p class="text-amber-700 max-w-md mx-auto">This specialized analytical view is currently being optimized for batch-level data integration. Please check back later.</p>
</x-card>
@endsection
