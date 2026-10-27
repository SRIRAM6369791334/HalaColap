@props([
    'name',
    'title' => '',
    'subtitle' => '',
    'maxWidth' => '2xl',
    'icon' => null,
    'iconColor' => 'emerald', // Deprecated: Always uses emerald for consistency
    'show' => false,
])

@php
$maxWidthClass = match($maxWidth) {
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
    '3xl' => 'sm:max-w-3xl',
    '4xl' => 'sm:max-w-4xl',
    '5xl' => 'sm:max-w-5xl',
    '6xl' => 'sm:max-w-6xl',
    '7xl' => 'sm:max-w-7xl',
    '720' => 'sm:max-w-[720px]',
    default => 'sm:max-w-lg',
};
@endphp

<div
    x-data="{
        show: {{ $show ? 'true' : 'false' }},
        previousFocus: null,
        focusables() {
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)]
                .filter(el => ! el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length || 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) -1 },
    }"
    x-init="$watch('show', value => {
        if (value) {
            previousFocus = document.activeElement;
            document.body.classList.add('overflow-y-hidden');
            setTimeout(() => firstFocusable()?.focus(), 100);
        } else {
            document.body.classList.remove('overflow-y-hidden');
            if (previousFocus) setTimeout(() => previousFocus.focus(), 50);
        }
    })"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-on:keydown.tab.prevent="$event.shiftKey || nextFocusable().focus()"
    x-on:keydown.shift.tab.prevent="prevFocusable().focus()"
    x-show="show"
    x-cloak
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-title-{{ $name }}"
    class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-8 font-outfit"
>
    <!-- Backdrop -->
    <div
        x-show="show"
        class="fixed inset-0"
        x-on:click="show = false"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="absolute inset-0 bg-[rgba(15,23,42,0.45)] backdrop-blur-[6px]"></div>
    </div>

    <!-- Modal Card -->
    <div
        x-show="show"
        class="relative w-full bg-white dark:bg-zinc-900 rounded-[20px] shadow-[0_25px_80px_rgba(15,23,42,0.2),0_8px_32px_rgba(15,23,42,0.08)] dark:shadow-[0_25px_80px_rgba(0,0,0,0.5)] max-w-[95vw] {{ $maxWidthClass }} border border-zinc-200/50 dark:border-zinc-800/50 flex flex-col max-h-[calc(100vh-64px)]"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-[0.96]"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-[0.96]"
    >
        <!-- Header -->
        <div class="flex-shrink-0 px-6 sm:px-8 pt-6 sm:pt-8 pb-0">
            <div class="flex items-start justify-between mb-7">
                <div class="flex items-center gap-4">
                    @if($icon)
                    <div class="flex-shrink-0 w-11 h-11 rounded-full bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-500 ring-1 ring-emerald-100 dark:ring-emerald-500/20">
                        <span class="material-symbols-rounded text-2xl">{{ $icon }}</span>
                    </div>
                    @endif
                    <div>
                        @if($title)
                            <h3 id="modal-title-{{ $name }}" class="text-lg font-bold text-zinc-900 dark:text-white font-cabinet">{{ $title }}</h3>
                        @endif
                        @if($subtitle)
                            <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>
                <button type="button" x-on:click="show = false" class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white" aria-label="Close dialog">
                    <span class="material-symbols-rounded text-xl" aria-hidden="true">close</span>
                </button>
            </div>
        </div>

        <!-- Body (Scrollable) -->
        <div class="flex-1 overflow-y-auto px-6 sm:px-8 pb-6 sm:pb-8 text-sm text-zinc-600 dark:text-zinc-400">
            {{ $slot }}
        </div>

        <!-- Footer (Always Visible) -->
        @if(isset($footer))
        <div class="flex flex-shrink-0 flex-col-reverse items-stretch justify-end gap-2 rounded-b-[20px] border-t border-zinc-200 bg-zinc-50 px-6 py-4 dark:border-zinc-800 dark:bg-zinc-800/30 sm:flex-row sm:items-center sm:px-8 [&>*]:max-sm:w-full [&>*]:max-sm:justify-center">
            {{ $footer }}
        </div>
        @endif
    </div>
</div>
