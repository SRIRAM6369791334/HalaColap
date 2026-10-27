@props([
    'value',
    'positive' => null,
])

@php
    // If positive is not explicitly passed, determine from value
    if ($positive === null) {
        $positive = (float)$value >= 0;
    }
    
    $colorClass = $positive ? 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200/50 dark:border-emerald-500/20' : 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-500/10 border-rose-200/50 dark:border-rose-500/20';
    $icon = $positive ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7V3';
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2 py-1 rounded-md border text-sm font-medium font-jetbrains-mono transition-all duration-300 ' . $colorClass]) }}>
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
    </svg>
    <span>{{ abs((float)$value) }}%</span>
</div>
