@props(['tabs' => [], 'active' => null])

<div class="border-b border-zinc-200 dark:border-zinc-800 w-full">
    <nav class="-mb-px flex space-x-8 overflow-x-auto no-scrollbar" aria-label="Tabs">
        @foreach($tabs as $tab)
            @php
                $isActive = ($active === ($tab['name'] ?? $tab['id']));
            @endphp
            <a href="{{ $tab['href'] ?? '#' }}"
               class="
                   whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all duration-300 min-h-[44px] min-w-[44px] flex items-center justify-center
                   {{ $isActive
                       ? 'border-emerald-500 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400'
                       : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300 dark:text-zinc-400 dark:hover:text-zinc-300 dark:hover:border-zinc-700'
                   }}
               "
               {{ $isActive ? 'aria-current="page"' : '' }}>
                
                @if(isset($tab['icon']))
                    <span class="material-symbols-rounded mr-2 text-[20px]">{{ $tab['icon'] }}</span>
                @endif
                
                {{ $tab['label'] }}
                
                @if(isset($tab['count']))
                    <span class="ml-3 rounded-full py-0.5 px-2.5 text-xs font-medium font-['JetBrains_Mono']
                        {{ $isActive 
                            ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' 
                            : 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-200' }}">
                        {{ $tab['count'] }}
                    </span>
                @endif
            </a>
        @endforeach
    </nav>
</div>
