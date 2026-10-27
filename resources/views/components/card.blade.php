@props([
    'title' => null,
    'subtitle' => null,
    'padding' => 'p-6',
    'hover' => false,
    'glass' => true,
])

<div {{ $attributes->merge(['class' => 'relative z-10 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 transition-shadow duration-200 ' . ($hover ? 'hover:shadow-lg' : 'shadow-sm')]) }}>
    @if($title || isset($actions))
        <div class="flex flex-col gap-3 border-b border-zinc-200 dark:border-zinc-800 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="min-w-0">
                @if($title)
                    <h3 class="font-cabinet font-semibold text-lg text-zinc-900 dark:text-zinc-50">{{ $title }}</h3>
                @endif
                @if($subtitle)
                    <p class="font-outfit text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ $subtitle }}</p>
                @endif
            </div>
            @if(isset($actions))
                <div class="flex flex-wrap items-center gap-2 [&>*]:max-sm:flex-1 [&>*]:max-sm:justify-center">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>
</div>
