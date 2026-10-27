@props([
    'headers' => [],
    'searchable' => false,
    'searchPlaceholder' => 'Search...',
])

<div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900" x-data="{ searchQuery: '' }">
    @if($searchable)
        <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-zinc-400">
                    <span class="material-symbols-rounded text-xl">search</span>
                </div>
                <input type="search" x-model="searchQuery" class="block min-h-11 w-full rounded-xl border border-zinc-300 bg-white p-2.5 pl-10 text-sm text-zinc-900 transition-colors focus:border-emerald-500 focus:ring-emerald-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 font-outfit" placeholder="{{ $searchPlaceholder }}">
            </div>
        </div>
    @endif

    <div class="custom-scrollbar overflow-x-auto">
        <table class="min-w-full text-left text-sm text-zinc-700 dark:text-zinc-300 font-outfit">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-400 font-cabinet [&_th]:px-4 [&_th]:py-3 [&_th]:font-semibold [&_th]:tracking-wider [&_th]:whitespace-nowrap sm:[&_th]:px-6 sm:[&_th]:py-4">
                @if(isset($head))
                    {{ $head }}
                @else
                    <tr>
                        @foreach($headers as $header)
                            <th scope="col" @class($header['class'] ?? null)>
                                @if(is_array($header))
                                    {{ $header['label'] ?? '' }}
                                @else
                                    {{ $header }}
                                @endif
                            </th>
                        @endforeach
                    </tr>
                @endif
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800 [&_tr]:transition-colors hover:[&_tr]:bg-zinc-50/80 dark:hover:[&_tr]:bg-zinc-800/50 [&_td]:px-4 [&_td]:py-3 [&_td]:align-middle sm:[&_td]:px-6 sm:[&_td]:py-4">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if(isset($empty))
        <div class="p-8 text-center" x-show="!$el.previousElementSibling.querySelector('tbody tr')">
            {{ $empty }}
        </div>
    @endif

    @if(isset($pagination))
        <div class="p-4 border-t border-zinc-200/50 dark:border-zinc-800/50">
            {{ $pagination }}
        </div>
    @endif
</div>
