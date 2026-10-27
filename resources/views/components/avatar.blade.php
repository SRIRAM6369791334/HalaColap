@props([
    'name',
    'size' => 'md',
    'color' => null,
])

@php
    $sizes = [
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-11 h-11 text-sm',
        'lg' => 'w-14 h-14 text-base',
        'xl' => 'w-20 h-20 text-xl',
    ];
    
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    
    $initial = strtoupper(substr($name, 0, 1));
    
    // Generate dynamic color if not provided
    if (!$color) {
        $colors = [
            'bg-emerald-500', 'bg-blue-500', 'bg-indigo-500', 
            'bg-purple-500', 'bg-pink-500', 'bg-rose-500', 
            'bg-orange-500', 'bg-amber-500', 'bg-teal-500'
        ];
        $colorIndex = ord($initial) % count($colors);
        $color = $colors[$colorIndex] ?? 'bg-zinc-500';
    }
@endphp

<div {{ $attributes->merge(['class' => "relative inline-flex items-center justify-center rounded-full font-cabinet text-white shadow-sm shrink-0 overflow-hidden ring-2 ring-white/20 dark:ring-zinc-900/50 transition-all duration-300 ease-[cubic-bezier(0.32,0.72,0,1)] {$sizeClass} {$color}"]) }}>
    <span class="font-bold tracking-wider leading-none select-none">{{ $initial }}</span>
</div>
