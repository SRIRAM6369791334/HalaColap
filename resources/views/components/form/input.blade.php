@props([
    'name' => '',
    'id' => null,
    'label' => '',
    'type' => 'text',
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'error' => null,
    'icon' => null,
    'hint' => null,
])

@php($fieldId = $id ?? $name)

<div class="w-full font-outfit">
    @if($label)
        <label for="{{ $fieldId }}" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300 font-outfit">
            {{ $label }}
            @if($required)
                <span class="text-emerald-500 font-bold ml-0.5 drop-shadow-[0_0_8px_rgba(16,185,129,0.5)]">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        @if($icon)
            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-zinc-600 dark:text-zinc-400 z-10">
                <span class="material-symbols-rounded text-[22px]">{{ $icon }}</span>
            </div>
        @endif

        <input 
            type="{{ $type }}" 
            name="{{ $name }}" 
            id="{{ $fieldId }}" 
            value="{{ old($name, $value) }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge(['class' => 'block min-h-11 w-full rounded-xl border bg-white p-3 text-sm text-zinc-900 shadow-sm transition-colors dark:bg-zinc-900 dark:text-zinc-100 ' . ($icon ? 'pl-11' : 'pl-4') . ' ' . ((isset($errors) && $errors->has($name)) || $error ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : 'border-zinc-300 hover:border-zinc-400 focus:border-emerald-500 focus:ring-emerald-500/20 dark:border-zinc-700 dark:hover:border-zinc-600 dark:focus:border-emerald-400')]) }}
            placeholder="{{ $placeholder }}"
        >
    </div>

    @if((isset($errors) && $errors->has($name)) || $error)
        <p class="mt-2 flex items-center gap-1 text-sm font-medium text-red-600 dark:text-red-400" role="alert">
            <span class="material-symbols-rounded text-sm" aria-hidden="true">error</span>
            {{ $error ?? (isset($errors) ? $errors->first($name) : '') }}
        </p>
    @elseif($hint)
        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
    @endif
</div>
