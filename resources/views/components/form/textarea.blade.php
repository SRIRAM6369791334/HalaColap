@props([
    'name' => '',
    'label' => false,
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'rows' => 4,
    'error' => null,
])

<div class="space-y-2">
    @if($label)
        <label for="{{ $name }}" class="block mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 font-outfit">
            {{ $label }} @if($required) <span class="text-emerald-500 font-bold ml-0.5 drop-shadow-[0_0_8px_rgba(16,185,129,0.5)]">*</span> @endif
        </label>
    @endif

    <div class="relative">
        <textarea
            id="{{ $name }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            placeholder="{{ $placeholder }}"
            @if($required) required @endif
            {{ $attributes->merge([
                'class' => 'block min-h-24 w-full resize-y rounded-xl border border-zinc-300 bg-white px-4 py-3 text-base text-zinc-900 shadow-sm transition-colors hover:border-zinc-400 focus:border-emerald-500 focus:ring-emerald-500/20 disabled:cursor-not-allowed disabled:bg-zinc-100 disabled:opacity-70 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:border-zinc-600 dark:disabled:bg-zinc-800 sm:text-sm font-outfit' . ($error ? ' border-red-500 focus:ring-red-500/20 focus:border-red-500' : '')
            ]) }}
        >{{ old($name, $value) }}</textarea>
    </div>

    @if($error)
        <p class="mt-1 text-sm font-medium text-red-600 dark:text-red-400 font-outfit" role="alert">{{ $error }}</p>
    @endif
</div>
