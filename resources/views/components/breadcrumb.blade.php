@props([
    'items' => []
])

<nav class="flex text-sm text-zinc-500 dark:text-zinc-400 font-outfit mb-6" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-2">
        @foreach($items as $index => $item)
            <li class="inline-flex items-center">
                @if($index > 0)
                    <span class="material-symbols-rounded text-sm mx-1 text-zinc-400">chevron_right</span>
                @endif
                
                @if(isset($item['url']) && !$loop->last)
                    <a href="{{ $item['url'] }}" class="inline-flex items-center hover:text-emerald-500 dark:hover:text-emerald-400 transition-colors font-medium">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="text-zinc-900 dark:text-zinc-100 font-semibold" aria-current="page">
                        {{ $item['label'] }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
