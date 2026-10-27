@props([
    'label' => null,
    'title' => null,
    'value',
    'icon' => null,
    'trend' => null,
    'trendUp' => true,
    'color' => 'emerald',
    'prefix' => '',
    'subtitle' => null,
])

@php
    $iconColors = [
        'emerald' => 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-900',
        'blue' => 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-900',
        'amber' => 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-900',
        'rose' => 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-900',
        'zinc' => 'bg-zinc-100 text-zinc-700 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700',
        'indigo' => 'bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-900',
        'violet' => 'bg-violet-50 text-violet-700 border border-violet-200 dark:bg-violet-950/60 dark:text-violet-300 dark:border-violet-900',
        'teal' => 'bg-teal-50 text-teal-700 border border-teal-200 dark:bg-teal-950/60 dark:text-teal-300 dark:border-teal-900',
    ];
    $iconClass = $iconColors[$color] ?? $iconColors['emerald'];
@endphp

<div class="group relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm transition-shadow duration-200 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
    <div class="relative z-10 flex items-start justify-between gap-2">
        <div class="min-w-0 flex-1">
            <p class="font-outfit text-xs sm:text-sm font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ $label ?? $title }}</p>
            <p class="font-outfit mt-1.5 text-base sm:text-lg lg:text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50 drop-shadow-sm flex items-center gap-1">
                @if($prefix)
                    <span class="text-[10px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ $prefix }}</span>
                @endif
                <span class="truncate">{{ $value }}</span>
            </p>
            @if($subtitle)
                <p class="text-[10px] sm:text-xs font-semibold text-zinc-400 dark:text-zinc-500 mt-1 truncate">{{ $subtitle }}</p>
            @endif
        </div>
        @if($icon)
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl sm:h-12 sm:w-12 {{ $iconClass }}">
                @if(Str::startsWith($icon, 'ph-'))
                    <i class="ph {{ $icon }} text-xl sm:text-2xl leading-none flex items-center justify-center"></i>
                @else
                    <span class="material-symbols-rounded text-xl sm:text-2xl leading-none flex items-center justify-center">{{ $icon }}</span>
                @endif
            </div>
        @endif
    </div>
    
    @if($trend)
        <div class="mt-4 flex items-center gap-2 text-sm">
            @if($trendUp)
                <span class="flex items-center text-emerald-600 dark:text-emerald-400 font-medium">
                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    {{ $trend }}
                </span>
            @else
                <span class="flex items-center text-rose-600 dark:text-rose-400 font-medium">
                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                    {{ $trend }}
                </span>
            @endif
            <span class="text-zinc-500 dark:text-zinc-400 font-outfit">vs last month</span>
        </div>
    @endif
</div>
