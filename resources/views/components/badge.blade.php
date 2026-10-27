@props([
    'variant' => 'neutral',
    'size' => 'md',
    'dot' => false,
    'color' => null,
])

@php
    $variants = [
        'success' => 'bg-emerald-500/15 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 border border-emerald-500/30 shadow-[0_0_15px_-3px_rgba(16,185,129,0.3)]',
        'warning' => 'bg-amber-500/15 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300 border border-amber-500/30 shadow-[0_0_15px_-3px_rgba(245,158,11,0.3)]',
        'danger' => 'bg-rose-500/15 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300 border border-rose-500/30 shadow-[0_0_15px_-3px_rgba(244,63,94,0.3)]',
        'info' => 'bg-blue-500/15 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300 border border-blue-500/30 shadow-[0_0_15px_-3px_rgba(59,130,246,0.3)]',
        'neutral' => 'bg-zinc-500/15 text-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-300 border border-zinc-500/30 shadow-[0_0_15px_-3px_rgba(113,113,122,0.3)]',
    ];
    
    $dotColors = [
        'success' => 'bg-emerald-500',
        'warning' => 'bg-amber-500',
        'danger' => 'bg-rose-500',
        'info' => 'bg-blue-500',
        'neutral' => 'bg-zinc-500',
    ];
    
    $sizes = [
        'sm' => 'px-2 py-0.5 text-[10px]',
        'md' => 'px-2.5 py-1 text-xs',
        'lg' => 'px-3 py-1.5 text-sm',
    ];
    
    $variantClass = $variants[$variant] ?? $variants['neutral'];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $classes = 'inline-flex items-center justify-center font-outfit font-semibold uppercase tracking-wider rounded-full backdrop-blur-md ' . $variantClass . ' ' . $sizeClass;
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if($dot)
        <span class="mr-1.5 flex h-1.5 w-1.5 rounded-full {{ $dotColors[$variant] ?? $dotColors['neutral'] }}"></span>
    @endif
    {{ $slot }}
</span>
