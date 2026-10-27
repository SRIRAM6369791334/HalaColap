@extends('layouts.app')
@section('title', 'Add Item')

@section('content')

<div class="space-y-6">

    <x-page-header title="Add New Item" subtitle="Register a new MSR BROILERS resource in the master record">
        <x-button variant="ghost" href="{{ route('inventory.items.index') }}" icon="arrow_back">
            Back to Items
        </x-button>
    </x-page-header>

    <x-card class="max-w-4xl mx-auto">
        <form action="{{ route('inventory.items.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                {{-- Basic Info --}}
                <div class="space-y-4">
                    <h3 class="text-xs font-bold text-zinc-500 uppercase border-b border-zinc-200 dark:border-zinc-700 pb-2 mb-4">1. Item Information</h3>
                    
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Item Name <span class="text-rose-500">*</span></label>
                        <x-form.input type="text" name="name" required value="{{ old('name') }}" placeholder="Ex: Broiler Feed Starter" />
                        @error('name') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Item Code (Optional)</label>
                        <x-form.input type="text" name="code" value="{{ old('code') }}" placeholder="Ex: FEED-001" class="font-mono" />
                        @error('code') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Type <span class="text-rose-500">*</span></label>
                            <x-form.select name="type" id="item-type" required :options="['' => 'Select...', 'Feed' => 'Feed', 'Chick' => 'Chick', 'Medicine' => 'Medicine', 'Vaccine' => 'Vaccine', 'Equipment' => 'Equipment', 'Other' => 'Other']" value="{{ old('type') }}" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Category</label>
                            <x-form.input type="text" name="category" value="{{ old('category') }}" id="category-input" list="category-options" placeholder="Ex: Starter" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Brand Name</label>
                        <x-form.input type="text" name="brand" value="{{ old('brand') }}" placeholder="Ex: Suguna" />
                    </div>

                    <div id="chick-breed-container" class="hidden">
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Breed Name</label>
                        <x-form.input type="text" name="breed" value="{{ old('breed') }}" placeholder="Ex: Cobb 500" />
                    </div>
                </div>

                {{-- Unit Info --}}
                <div class="space-y-4">
                    <h3 class="text-xs font-bold text-zinc-500 uppercase border-b border-zinc-200 dark:border-zinc-700 pb-2 mb-4">2. Unit Logic</h3>

                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Base Unit <span class="text-rose-500">*</span></label>
                        <x-form.select name="base_unit" id="base-unit" required :options="['kg' => 'Kilograms (kg)', 'nos' => 'Numbers (nos)', 'ml' => 'Milliliters (ml)', 'ltr' => 'Liters (ltr)', 'vial' => 'Vial']" value="{{ old('base_unit', 'kg') }}" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Conversion Factor</label>
                        <div class="flex items-center gap-3">
                            <div class="flex-1">
                                <x-form.input type="number" name="conversion_rate" id="conversion-rate" step="0.01" value="{{ old('conversion_rate', 1.00) }}" />
                            </div>
                            <span class="text-xs font-bold text-zinc-500" id="conversion-label">per Bag</span>
                        </div>
                        <p class="text-xs text-zinc-400 mt-1 italic">If 1 Bag = 50kg, enter 50.00</p>
                    </div>

                    <div class="mt-6 p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold text-zinc-900 dark:text-white">Is Item Active?</span>
                            <input type="checkbox" name="is_active" value="1" checked class="w-5 h-5 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <x-button variant="ghost" href="{{ route('inventory.items.index') }}">Cancel</x-button>
                <x-button type="submit" icon="save">Save New Item</x-button>
            </div>
        </form>
    </x-card>
</div>

<datalist id="category-options">
    <option value="Starter">
    <option value="Grower">
    <option value="Finisher">
    <option value="Pre-Starter">
</datalist>

@endsection

@push('scripts')
<script>
document.getElementById('item-type').addEventListener('change', function() {
    const type = this.value;
    const catInput = document.getElementById('category-input');
    const breedContainer = document.getElementById('chick-breed-container');
    const baseUnit = document.getElementById('base-unit');
    
    if (type === 'Chick') {
        breedContainer.classList.remove('hidden');
        baseUnit.value = 'nos';
    } else {
        breedContainer.classList.add('hidden');
    }

    if (type === 'Feed') {
        baseUnit.value = 'kg';
    } else if (type === 'Medicine' || type === 'Vaccine') {
        baseUnit.value = 'ml';
    }
});

document.getElementById('base-unit').addEventListener('change', function() {
    const unit = this.value;
    document.getElementById('conversion-label').textContent = `per Bag (in ${unit})`;
});

// Trigger change event to set initial state
document.getElementById('item-type').dispatchEvent(new Event('change'));
</script>
@endpush
