@props([
    'placeholder' => 'Search customers, dealers, vendors...',
])

<div
    x-data="{
        query: '',
        results: [],
        loading: false,
        open: false,
        searchUrl: '{{ route('global.search') }}',
        debounceTimer: null,

        onInput() {
            clearTimeout(this.debounceTimer);
            if (this.query.trim().length < 2) {
                this.results = [];
                this.open = false;
                return;
            }
            this.loading = true;
            this.debounceTimer = setTimeout(() => this.fetch(), 300);
        },

        async fetch() {
            try {
                const res = await fetch(this.searchUrl + '?q=' + encodeURIComponent(this.query));
                this.results = await res.json();
                this.open = this.results.length > 0;
            } catch(e) {
                this.results = [];
            } finally {
                this.loading = false;
            }
        },

        go(url) {
            this.open = false;
            this.query = '';
            window.location.href = url;
        },

        clear() {
            this.query = '';
            this.results = [];
            this.open = false;
            this.$refs.searchInput.focus();
        }
    }"
    @keydown.escape="open = false"
    @click.outside="open = false"
    class="relative flex items-center w-full max-w-md"
>
    {{-- Search Icon --}}
    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-zinc-500 dark:text-zinc-400 z-10">
        <span x-show="!loading" class="material-symbols-rounded text-[20px]">search</span>
        <svg x-show="loading" class="animate-spin w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </span>

    {{-- Input --}}
    <input
        type="text"
        x-model="query"
        x-ref="searchInput"
        @input="onInput()"
        @keydown.window.meta.k.prevent="$refs.searchInput.focus()"
        @keydown.window.ctrl.k.prevent="$refs.searchInput.focus()"
        @keydown.arrow-down.prevent="$refs.resultsList?.querySelector('a')?.focus()"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        class="block min-h-11 w-full rounded-xl border border-zinc-300 bg-white py-2.5 pl-10 pr-20 text-sm text-zinc-900 shadow-sm transition-colors placeholder:text-zinc-500 focus:border-emerald-500 focus:ring-emerald-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
    >

    {{-- Right side: clear button + kbd hint --}}
    <div class="absolute inset-y-0 right-0 flex items-center pr-3 gap-1">
        <button
            type="button"
            x-show="query.length > 0"
            @click="clear()"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-90"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90"
            style="display:none;"
            class="flex min-h-10 min-w-10 items-center justify-center rounded-lg text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
            aria-label="Clear search"
        >
            <span class="material-symbols-rounded text-[18px]">close</span>
        </button>
        <div x-show="query.length === 0" class="hidden sm:flex items-center justify-center px-2 py-1 text-xs text-zinc-400 bg-zinc-100/50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700 pointer-events-none">
            <span class="text-[10px] mr-0.5">⌘</span>K
        </div>
    </div>

    {{-- Dropdown Results --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
        x-ref="resultsList"
        class="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
    >
        {{-- Grouped Results --}}
        <template x-if="results.length > 0">
            <div>
                {{-- Group by type --}}
                <template x-for="type in ['Customer', 'Dealer', 'Vendor', 'Purchase Invoice', 'Daily Invoice', 'Weekly Invoice']" :key="type">
                    <template x-if="results.filter(r => r.type === type).length > 0">
                        <div>
                            {{-- Group Header --}}
                            <div class="px-4 py-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500 bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-100 dark:border-zinc-800">
                                <span x-text="type + 's'"></span>
                            </div>
                            {{-- Items --}}
                            <template x-for="item in results.filter(r => r.type === type)" :key="item.id + item.type">
                                <a
                                    :href="item.url"
                                    @click.prevent="go(item.url)"
                                    class="flex items-center gap-3 px-4 py-3 hover:bg-emerald-50 dark:hover:bg-zinc-800 transition-colors cursor-pointer focus:outline-none focus:bg-emerald-50 dark:focus:bg-zinc-800 group"
                                >
                                    {{-- Icon --}}
                                    <span
                                        class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 text-white text-[18px] material-symbols-rounded"
                                        :class="{
                                            'bg-emerald-500': item.color === 'emerald',
                                            'bg-blue-500': item.color === 'blue',
                                            'bg-amber-500': item.color === 'amber',
                                            'bg-orange-500': item.color === 'orange',
                                            'bg-violet-500': item.color === 'violet',
                                            'bg-purple-500': item.color === 'purple'
                                        }"
                                        x-text="item.icon"
                                    ></span>
                                    {{-- Label --}}
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100 truncate" x-text="item.label"></p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate" x-text="item.sub"></p>
                                    </div>
                                    {{-- Arrow --}}
                                    <span class="material-symbols-rounded text-[16px] text-zinc-300 group-hover:text-emerald-500 transition-colors">arrow_forward</span>
                                </a>
                            </template>
                        </div>
                    </template>
                </template>
            </div>
        </template>

        {{-- No Results --}}
        <template x-if="results.length === 0 && !loading && query.length >= 2">
            <div class="flex flex-col items-center justify-center py-8 px-4 text-center">
                <span class="material-symbols-rounded text-4xl text-zinc-300 mb-2">search_off</span>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">No results for "<span x-text="query" class="font-medium text-zinc-700 dark:text-zinc-200"></span>"</p>
                <p class="text-xs text-zinc-400 mt-1">Try searching by phone number or name</p>
            </div>
        </template>
    </div>
</div>

