@props([
    'amount',
    'showSign' => true,
    'colorCode' => false,
    'currency' => '₹',
])

@php
    $isNegative = $amount < 0;
    $absAmount = abs($amount);
    $formatted = number_format($absAmount, 2);
    
    $colorClass = '';
    if ($colorCode) {
        $colorClass = $isNegative ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400';
    }
@endphp

<span class="font-jetbrains-mono tracking-tight {{ $colorClass }} {{ $attributes->get('class') }}">
    @if($showSign && $isNegative)-@endif{{ $currency }}{{ $formatted }}
</span>
