@props([
    'name' => '',
    'id' => null,
    'label' => false,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'required' => false,
    'error' => null,
])

@php($fieldId = $id ?? $name)

<div class="space-y-2">
    @if($label)
        <label for="{{ $fieldId }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 font-outfit">
            {{ $label }} @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif

    <div class="relative">
        <select 
            id="{{ $fieldId }}" 
            name="{{ $name }}" 
            @if($required) required @endif
            {{ $attributes->merge([
                'class' => 'block min-h-11 w-full appearance-none rounded-xl border border-zinc-300 bg-white py-2.5 pl-3 pr-10 text-base text-zinc-900 shadow-sm transition-colors hover:border-zinc-400 focus:border-emerald-500 focus:ring-emerald-500/20 disabled:cursor-not-allowed disabled:bg-zinc-100 disabled:opacity-70 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:border-zinc-600 dark:disabled:bg-zinc-800 sm:text-sm' . ($error ? ' border-red-500 focus:ring-red-500/20 focus:border-red-500' : '')
            ]) }}
        >
            @if($placeholder)
                <option value="" disabled {{ is_null($selected) ? 'selected' : '' }}>{{ $placeholder }}</option>
            @endif

            @foreach($options as $value => $text)
                <option value="{{ $value }}" {{ (string)$selected === (string)$value ? 'selected' : '' }}>
                    {{ $text }}
                </option>
            @endforeach
            
            {{ $slot }}
        </select>
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-500 dark:text-zinc-400">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
    </div>

    @if($error)
        <p class="mt-1 text-sm font-medium text-red-600 dark:text-red-400 font-outfit" role="alert">{{ $error }}</p>
    @endif
</div>
