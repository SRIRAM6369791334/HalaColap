@extends('layouts.app')
@section('title', 'Create Customer Daily Invoice')

@section('content')
<div class="mb-6 animate-fade-in max-w-4xl mx-auto">
    <a href="{{ route('billing.daily.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 hover:text-emerald-700 uppercase tracking-wider mb-2">
        <span class="material-symbols-rounded text-sm">arrow_back</span>
        Back to Daily Billing
    </a>
    <h1 class="font-cabinet text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">Create Customer Invoice</h1>
    <p class="text-sm text-zinc-500 mt-1">Generate standalone customer daily bill for print and record purpose.</p>
</div>

<div class="max-w-4xl mx-auto animate-fade-in" x-data="invoiceForm()">
    <x-card>
        <div class="p-6">
            <form action="{{ route('billing.daily.store') }}" method="POST">
                @csrf
                
                @if (isset($errors) && $errors->any())
                    <div class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200">
                        <ul class="list-disc pl-5 text-sm text-red-600">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Customer & Date Header --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                            Customer (Billed To) <span class="text-rose-500">*</span>
                        </label>
                        <select name="customer_id" required class="w-full px-4 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-emerald-500 outline-none">
                            <option value="">Select Customer</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} ({{ $customer->route ?: 'No Route' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                            Billing Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required class="w-full px-4 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-emerald-500 outline-none">
                    </div>
                </div>

                {{-- Items Section --}}
                <div class="mb-6 border-t border-zinc-100 dark:border-zinc-800 pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                <span class="material-symbols-rounded text-emerald-600 text-lg">restaurant</span>
                                Items to Bill (Chicken Cuts & Products)
                            </h3>
                            <p class="text-xs text-zinc-500">Choose preset chicken cut or type a custom product name.</p>
                        </div>
                        <button type="button" @click="addItem()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 transition-colors border border-emerald-200 dark:border-emerald-800">
                            <span class="material-symbols-rounded text-sm">add</span> Add Another Item
                        </button>
                    </div>

                    <div class="space-y-4">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="p-4 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/40 relative">
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                                    {{-- Item Name / Preset Selection --}}
                                    <div class="md:col-span-5">
                                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1.5">
                                            Item Type <span class="text-rose-500">*</span>
                                        </label>
                                        <div class="space-y-2">
                                            <select x-model="item.preset" @change="onPresetChange(item)" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-emerald-500 outline-none font-medium">
                                                <option value="Skin Chicken">🍗 Skin Chicken</option>
                                                <option value="Skinless Chicken">🍗 Skinless Chicken</option>
                                                <option value="Boneless Chicken">🍗 Boneless Chicken</option>
                                                <option value="Custom">✍️ Custom Item</option>
                                            </select>

                                            {{-- Custom Input Box if Custom selected --}}
                                            <div x-show="item.preset === 'Custom'" x-transition>
                                                <input type="text" x-model="item.custom_name" @input="updateFinalName(item)" :name="`items[${index}][name]`" placeholder="Type custom item name..." class="w-full px-3 py-2 text-sm rounded-xl border border-emerald-300 dark:border-emerald-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder:text-zinc-400 focus:ring-2 focus:ring-emerald-500 outline-none">
                                            </div>
                                            <input type="hidden" x-show="item.preset !== 'Custom'" :name="`items[${index}][name]`" :value="item.name">
                                        </div>
                                    </div>

                                    {{-- Quantity --}}
                                    <div class="md:col-span-2">
                                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1.5">
                                            Weight (kg) <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="number" step="0.01" min="0.01" x-model.number="item.qty" :name="`items[${index}][qty]`" placeholder="0.00" required class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 font-jetbrains focus:ring-2 focus:ring-emerald-500 outline-none">
                                    </div>

                                    {{-- Rate --}}
                                    <div class="md:col-span-2">
                                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1.5">
                                            Rate / kg (₹) <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="number" step="0.01" min="0.01" x-model.number="item.rate" :name="`items[${index}][rate]`" placeholder="0.00" required class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 font-jetbrains focus:ring-2 focus:ring-emerald-500 outline-none">
                                    </div>

                                    {{-- Line Total --}}
                                    <div class="md:col-span-2">
                                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1.5">
                                            Total (₹)
                                        </label>
                                        <div class="px-3 py-2 text-sm font-bold font-jetbrains text-emerald-600 dark:text-emerald-400 bg-emerald-50/60 dark:bg-emerald-950/30 rounded-xl border border-emerald-200/60 dark:border-emerald-900/40 text-right" x-text="'₹ ' + ((item.qty || 0) * (item.rate || 0)).toFixed(2)">
                                            ₹ 0.00
                                        </div>
                                    </div>

                                    {{-- Delete Button --}}
                                    <div class="md:col-span-1 flex items-center justify-center pt-6">
                                        <button type="button" @click="removeItem(index)" x-show="items.length > 1" class="text-rose-400 hover:text-rose-600 transition-colors p-1" title="Remove Item">
                                            <span class="material-symbols-rounded text-xl">delete</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Grand Total Card --}}
                    <div class="mt-4 p-4 rounded-2xl bg-gradient-to-r from-emerald-500/10 via-teal-500/5 to-transparent border border-emerald-500/20 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-rounded text-emerald-600 text-xl">calculate</span>
                            <span class="text-sm font-bold text-zinc-700 dark:text-zinc-300">Grand Total Amount</span>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-bold font-jetbrains text-emerald-600 dark:text-emerald-400" x-text="'₹ ' + grandTotal().toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                            <input type="hidden" name="amount" :value="grandTotal()">
                        </div>
                    </div>
                </div>

                {{-- Payment Details --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6 border-t border-zinc-100 dark:border-zinc-800 pt-6">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-2">
                            Payment Status <span class="text-rose-500">*</span>
                        </label>
                        <div class="flex gap-5 mt-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="status" value="Pending" checked class="w-4 h-4 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Pending</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="status" value="Paid" class="w-4 h-4 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Paid</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5">
                            Payment Mode <span class="text-rose-500">*</span>
                        </label>
                        <select name="payment_mode" required class="w-full px-4 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-emerald-500 outline-none">
                            <option value="Cash">Cash</option>
                            <option value="UPI">UPI (GooglePay / PhonePe)</option>
                            <option value="NEFT">NEFT / Bank</option>
                            <option value="Cheque(Bank Transfer)">Cheque (Bank Transfer)</option>
                            <option value="Pay later(EMI)">Pay later (EMI)</option>
                        </select>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="pt-5 border-t border-zinc-200/50 dark:border-zinc-800/50 flex justify-end gap-3">
                    <x-button type="submit" variant="primary" icon="receipt_long" size="lg">
                        Generate Invoice
                    </x-button>
                </div>
            </form>
        </div>
    </x-card>
</div>

@push('scripts')
<script>
    function invoiceForm() {
        return {
            items: [
                {
                    preset: 'Skin Chicken',
                    name: 'Skin Chicken',
                    custom_name: '',
                    qty: '',
                    rate: '',
                }
            ],
            addItem() {
                this.items.push({
                    preset: 'Skinless Chicken',
                    name: 'Skinless Chicken',
                    custom_name: '',
                    qty: '',
                    rate: '',
                });
            },
            removeItem(index) {
                if (this.items.length > 1) {
                    this.items.splice(index, 1);
                }
            },
            onPresetChange(item) {
                if (item.preset === 'Custom') {
                    item.name = item.custom_name || 'Chicken';
                } else {
                    item.name = item.preset;
                }
            },
            updateFinalName(item) {
                if (item.preset === 'Custom') {
                    item.name = item.custom_name;
                }
            },
            grandTotal() {
                return this.items.reduce((sum, item) => {
                    const q = parseFloat(item.qty) || 0;
                    const r = parseFloat(item.rate) || 0;
                    return sum + (q * r);
                }, 0);
            }
        };
    }
</script>
@endpush
@endsection
