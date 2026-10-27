@props(['paginator'])

<nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between mt-6">
    {{-- Mobile view --}}
    <div class="flex justify-between flex-1 sm:hidden">
        @if ($paginator->onFirstPage())
            <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-zinc-500 bg-white/50 backdrop-blur-md border border-white/60 cursor-default rounded-full dark:bg-zinc-900/50 dark:border-zinc-800 dark:text-zinc-400 min-h-[44px] min-w-[44px] justify-center shadow-sm">
                {!! __('pagination.previous') !!}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-zinc-700 bg-white/50 backdrop-blur-md border border-white/60 rounded-full hover:bg-white/80 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-emerald-500 active:bg-zinc-100 transition duration-300 dark:bg-zinc-900/50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800 min-h-[44px] min-w-[44px] justify-center shadow-sm">
                {!! __('pagination.previous') !!}
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-zinc-700 bg-white/50 backdrop-blur-md border border-white/60 rounded-full hover:bg-white/80 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-emerald-500 active:bg-zinc-100 transition duration-300 dark:bg-zinc-900/50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800 min-h-[44px] min-w-[44px] justify-center shadow-sm">
                {!! __('pagination.next') !!}
            </a>
        @else
            <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-zinc-500 bg-white/50 backdrop-blur-md border border-white/60 cursor-default rounded-full dark:bg-zinc-900/50 dark:border-zinc-800 dark:text-zinc-400 min-h-[44px] min-w-[44px] justify-center shadow-sm">
                {!! __('pagination.next') !!}
            </span>
        @endif
    </div>

    {{-- Desktop view --}}
    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-zinc-700 dark:text-zinc-400 leading-5">
                {!! __('Showing') !!}
                <span class="font-medium font-['JetBrains_Mono']">{{ $paginator->firstItem() ?? 0 }}</span>
                {!! __('to') !!}
                <span class="font-medium font-['JetBrains_Mono']">{{ $paginator->lastItem() ?? 0 }}</span>
                {!! __('of') !!}
                <span class="font-medium font-['JetBrains_Mono']">{{ $paginator->total() }}</span>
                {!! __('results') !!}
            </p>
        </div>

        <div>
            <span class="relative z-0 inline-flex shadow-sm rounded-full bg-white/50 dark:bg-zinc-900/50 backdrop-blur-md border border-white/60 dark:border-zinc-800 p-1">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                        <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-zinc-400 cursor-default rounded-l-full dark:text-zinc-600 min-h-[44px] min-w-[44px] justify-center" aria-hidden="true">
                            <span class="material-symbols-rounded">chevron_left</span>
                        </span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-zinc-500 hover:text-zinc-700 hover:bg-white/80 rounded-l-full focus:z-10 focus:outline-none transition-all duration-300 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-800/50 min-h-[44px] min-w-[44px] justify-center" aria-label="{{ __('pagination.previous') }}">
                        <span class="material-symbols-rounded">chevron_left</span>
                    </a>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($paginator->elements() as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span aria-disabled="true">
                            <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-zinc-700 cursor-default dark:text-zinc-400 min-h-[44px] min-w-[44px] justify-center font-['JetBrains_Mono']">{{ $element }}</span>
                        </span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page">
                                    <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-white bg-emerald-500 rounded-full cursor-default shadow-md shadow-emerald-500/20 min-h-[44px] min-w-[44px] justify-center font-['JetBrains_Mono']">{{ $page }}</span>
                                </span>
                            @else
                                <a href="{{ $url }}" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-zinc-500 hover:text-zinc-700 hover:bg-white/80 rounded-full focus:z-10 focus:outline-none transition-all duration-300 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-800/50 min-h-[44px] min-w-[44px] justify-center font-['JetBrains_Mono']">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-zinc-500 hover:text-zinc-700 hover:bg-white/80 rounded-r-full focus:z-10 focus:outline-none transition-all duration-300 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-800/50 min-h-[44px] min-w-[44px] justify-center" aria-label="{{ __('pagination.next') }}">
                        <span class="material-symbols-rounded">chevron_right</span>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                        <span class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-zinc-400 cursor-default rounded-r-full dark:text-zinc-600 min-h-[44px] min-w-[44px] justify-center" aria-hidden="true">
                            <span class="material-symbols-rounded">chevron_right</span>
                        </span>
                    </span>
                @endif
            </span>
        </div>
    </div>
</nav>
