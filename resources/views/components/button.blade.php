@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'type' => 'button',
    'href' => null,
])

@php
    $isIconOnly = $icon && trim(strip_tags($slot->toHtml())) === '';
    $baseClasses = 'inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap font-outfit font-semibold transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 dark:focus-visible:ring-emerald-400 dark:focus-visible:ring-offset-zinc-950 disabled:pointer-events-none disabled:opacity-50 relative';
    
    $variants = [
        'primary' => 'bg-emerald-600 text-white shadow-sm border border-emerald-700/30 hover:bg-emerald-700 dark:bg-emerald-500 dark:text-emerald-950 dark:hover:bg-emerald-400',
        'secondary' => 'bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-200 border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 shadow-sm',
        'ghost' => 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-950 dark:hover:text-white border border-transparent',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-700 shadow-sm border border-rose-700/30',
        'outline' => 'bg-white dark:bg-zinc-900 text-emerald-700 dark:text-emerald-300 border border-emerald-600 dark:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 shadow-sm',
    ];
    
    $sizes = [
        'sm' => 'px-3 py-2 text-xs rounded-xl min-h-[40px]',
        'md' => 'px-4 py-2 text-sm rounded-xl min-h-[44px]',
        'lg' => 'px-6 py-3 text-base rounded-xl min-h-[52px]',
    ];
    
    $iconOnlyClasses = $isIconOnly ? ' aspect-square !px-0' : '';
    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']) . $iconOnlyClasses;
    $accessibleLabel = $isIconOnly
        ? ($attributes->get('aria-label') ?? $attributes->get('title') ?? \Illuminate\Support\Str::of((string) $icon)->replace(['ph-', '_', '-'], ['', ' ', ' '])->headline())
        : null;
    $mergedAttributes = ['class' => $classes];
    if ($accessibleLabel) {
        $mergedAttributes['aria-label'] = $accessibleLabel;
    }
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge($mergedAttributes) }}>
        @if($icon)
            @if(Str::startsWith($icon, 'ph-'))
                <i class="ph {{ $icon }} relative z-10 text-[1.15em]" aria-hidden="true"></i>
            @else
                <span class="material-symbols-rounded relative z-10" style="font-size: 1.15em" aria-hidden="true">{{ $icon }}</span>
            @endif
        @endif
        @unless($isIconOnly)<span class="relative z-10">{{ $slot }}</span>@endunless
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge($mergedAttributes) }}>
        @if($icon)
            @if(Str::startsWith($icon, 'ph-'))
                <i class="ph {{ $icon }} relative z-10 text-[1.15em]" aria-hidden="true"></i>
            @else
                <span class="material-symbols-rounded relative z-10" style="font-size: 1.15em" aria-hidden="true">{{ $icon }}</span>
            @endif
        @endif
        @unless($isIconOnly)<span class="relative z-10">{{ $slot }}</span>@endunless
    </button>
@endif
