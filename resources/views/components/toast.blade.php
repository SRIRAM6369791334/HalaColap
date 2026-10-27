<div
    x-data="{
        show: false,
        message: '',
        type: 'success',
        timeout: null,
        init() {
            @if(session()->has('success'))
                this.message = '{{ session()->get('success') }}';
                this.type = 'success';
                this.show = true;
            @elseif(session()->has('error'))
                this.message = '{{ session()->get('error') }}';
                this.type = 'error';
                this.show = true;
            @elseif(session()->has('status'))
                this.message = '{{ session()->get('status') }}';
                this.type = 'info';
                this.show = true;
            @endif

            if(this.show) {
                this.startTimer();
            }

            window.addEventListener('toast', (e) => {
                this.message = e.detail.message;
                this.type = e.detail.type || 'success';
                this.show = true;
                this.startTimer();
            });
        },
        startTimer() {
            if(this.timeout) clearTimeout(this.timeout);
            this.timeout = setTimeout(() => this.close(), 4000);
        },
        close() {
            this.show = false;
        }
    }"
    x-show="show"
    x-transition:enter="transition ease-[cubic-bezier(0.32,0.72,0,1)] duration-500"
    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:translate-x-4 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0 scale-100"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    class="fixed bottom-4 right-4 sm:top-4 sm:bottom-auto z-50 flex items-center w-full max-w-sm p-4 space-x-3 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-xl border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-lg"
    style="display: none;"
    role="alert"
>
    <div
        class="inline-flex items-center justify-center flex-shrink-0 w-10 h-10 rounded-full min-h-[44px] min-w-[44px]"
        :class="{
            'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400': type === 'success',
            'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400': type === 'error',
            'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400': type === 'info'
        }"
    >
        <span class="material-symbols-rounded" x-show="type === 'success'">check_circle</span>
        <span class="material-symbols-rounded" x-show="type === 'error'">error</span>
        <span class="material-symbols-rounded" x-show="type === 'info'">info</span>
    </div>
    
    <div class="ml-3 text-sm font-medium text-zinc-900 dark:text-zinc-100 flex-1" x-text="message"></div>
    
    <button
        @click="close()"
        type="button"
        class="ml-auto -mx-1.5 -my-1.5 bg-transparent text-zinc-400 hover:text-zinc-900 rounded-full focus:ring-2 focus:ring-zinc-300 p-1.5 hover:bg-zinc-100 inline-flex items-center justify-center h-10 w-10 dark:text-zinc-500 dark:hover:text-white dark:hover:bg-zinc-800 transition-colors min-h-[44px] min-w-[44px]"
        aria-label="Close"
    >
        <span class="material-symbols-rounded text-[20px]">close</span>
    </button>
</div>
