@props([
    'title',
    'subtitle' => null,
])

<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between animate-fade-in">
    <div class="min-w-0">
        <h1 class="font-cabinet text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $title }}</h1>
        @if($subtitle)
            <p class="mt-1 font-outfit text-sm text-zinc-500 dark:text-zinc-400">{{ $subtitle }}</p>
        @endif
    </div>
    @if(isset($actions))
        <div class="flex w-full flex-wrap items-center gap-2 md:w-auto md:justify-end [&>*]:max-sm:flex-1 [&>*]:max-sm:justify-center">
            {{ $actions }}
        </div>
    @endif
</div>
