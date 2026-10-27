@props(['align' => 'right', 'width' => '48'])

@php
$alignmentClasses = match ($align) {
    'left' => 'origin-top-left left-0',
    'top' => 'origin-top',
    'right' => 'origin-top-right right-0',
    default => 'origin-top-right right-0',
};

$widthClasses = match ($width) {
    '48' => 'w-48',
    '64' => 'w-64',
    default => $width,
};
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <div @click="open = ! open" class="cursor-pointer min-h-[44px] flex items-center justify-center">
        {{ $trigger }}
    </div>

    <div x-show="open"
         x-transition:enter="transition ease-[cubic-bezier(0.32,0.72,0,1)] duration-300"
         x-transition:enter-start="opacity-0 scale-95 translate-y-[-10px]"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-[-10px]"
         class="absolute z-50 mt-2 {{ $widthClasses }} {{ $alignmentClasses }} rounded-2xl shadow-lg bg-white/80 dark:bg-zinc-900/80 backdrop-blur-xl border border-zinc-200 dark:border-zinc-800 focus:outline-none"
         style="display: none;"
         @click="open = false">
        <div class="rounded-2xl ring-1 ring-black ring-opacity-5 py-1">
            {{ $slot }}
        </div>
    </div>
</div>
