@props([
    'name',
    'title' => 'Are you sure?',
    'description' => 'This action cannot be undone.',
    'confirmText' => 'Confirm',
    'cancelText' => 'Cancel',
    'variant' => 'danger' // danger, primary
])

<div
    x-data="{
        show: false,
        init() {
            window.addEventListener('open-confirm-dialog', (e) => {
                if (e.detail === '{{ $name }}') {
                    this.show = true;
                }
            });
        },
        close() {
            this.show = false;
        }
    }"
    @keydown.escape.window="close()"
    x-show="show"
    class="relative z-50"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
    style="display: none;"
>
    {{-- Backdrop --}}
    <div
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-zinc-900/40 backdrop-blur-sm transition-opacity"
    ></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            {{-- Dialog Panel --}}
            <div
                x-show="show"
                @click.outside="close()"
                x-transition:enter="transition ease-[cubic-bezier(0.32,0.72,0,1)] duration-500"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-3xl bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200 dark:border-zinc-800 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg"
            >
                <div class="px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full min-h-[44px] min-w-[44px] sm:mx-0 sm:h-10 sm:w-10
                            {{ $variant === 'danger' ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' : 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' }}">
                            <span class="material-symbols-rounded">
                                {{ $variant === 'danger' ? 'warning' : 'help' }}
                            </span>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-lg font-['Cabinet_Grotesk'] font-bold leading-6 text-zinc-900 dark:text-zinc-100" id="modal-title">
                                {{ $title }}
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-zinc-50/50 dark:bg-zinc-800/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 rounded-b-3xl">
                    @if($slot->isEmpty())
                        <button
                            type="button"
                            @click="$dispatch('confirm-' + '{{ $name }}'); close()"
                            class="inline-flex w-full justify-center rounded-xl px-3 py-2 text-sm font-medium text-white shadow-sm sm:ml-3 sm:w-auto min-h-[44px] items-center transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-zinc-900
                            {{ $variant === 'danger' ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500' : 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500' }}"
                        >
                            {{ $confirmText }}
                        </button>
                    @else
                        {{ $slot }}
                    @endif
                    
                    <button
                        type="button"
                        @click="close()"
                        class="mt-3 inline-flex w-full justify-center rounded-xl bg-white dark:bg-zinc-800 px-3 py-2 text-sm font-medium text-zinc-900 dark:text-zinc-100 shadow-sm ring-1 ring-inset ring-zinc-300 dark:ring-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700 sm:mt-0 sm:w-auto min-h-[44px] items-center transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900"
                    >
                        {{ $cancelText }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
