@extends('layouts.app')
@section('title', 'Record Purchase Refill')

@section('content')
<div class="flex justify-center">
    <div class="w-full max-w-2xl space-y-6">
        
        {{-- Back & Header --}}
        <div class="flex items-center justify-between">
            <x-button variant="ghost" href="{{ route('purchases.entry') }}" icon="arrow_back" size="sm">
                Back to Dashboard
            </x-button>
            <span class="text-[10px] font-mono px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-bold border border-emerald-100 dark:border-emerald-900/50 uppercase tracking-wider">
                Single Refill Mode
            </span>
        </div>

        @if ($errors->any())
            <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl relative">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li class="text-sm font-medium">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-card>
            <form action="{{ route('purchases.store') }}" method="POST" id="purchase-create-form" x-data="{ paymentMode: 'Cash' }">
                @csrf
                
                {{-- Form Section Title --}}
                <div class="flex items-center gap-2 mb-6 pb-4 border-b border-zinc-100 dark:border-zinc-800">
                    <span class="material-symbols-rounded text-emerald-600 dark:text-emerald-400">add_circle</span>
                    <div>
                        <h2 class="text-lg font-bold text-zinc-800 dark:text-white">Record Single Refill Inward</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium mt-0.5">Quickly entry stock refills directly into specific batch or warehouse placements</p>
                    </div>
                </div>

                {{-- 1. Supplier & Invoice Info Group --}}
                <div class="space-y-4 mb-8">
                    <h3 class="text-xs font-bold text-zinc-500 uppercase tracking-wider">1. Supply Partner & Bill Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Vendor Name <span class="text-rose-500">*</span></label>
                            <x-form.select name="vendor_name" required>
                                <option value="">Select supply partner...</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->firm_name }}" {{ ($vendor_name == $vendor->firm_name) ? 'selected' : '' }}>
                                        {{ $vendor->firm_name }}
                                    </option>
                                @endforeach
                            </x-form.select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Invoice / Bill Number <span class="text-xs text-zinc-400 font-normal">(Auto)</span></label>
                            <x-form.input type="text" name="invoice_no" value="{{ $autoInvoiceNo }}" readonly class="bg-zinc-100 dark:bg-zinc-800 text-zinc-500 cursor-not-allowed font-mono" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Billing Date <span class="text-rose-500">*</span></label>
                            <x-form.input type="date" name="date" required value="{{ date('Y-m-d') }}" class="font-semibold" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Payment Mode <span class="text-rose-500">*</span></label>
                            <x-form.select name="payment_mode" required x-model="paymentMode" :options="['Cash' => 'Cash', 'UPI' => 'UPI', 'NEFT' => 'NEFT', 'Cheque(Bank Transfer)' => 'Cheque(Bank Transfer)', 'Pay later(EMI)' => 'Pay later(EMI)']" />
                        </div>
                    </div>
                    
                    <x-emi-schedule-generator totalAmountId="display-total" />
                </div>

                {{-- 2. Item details --}}
                <div class="space-y-4 mb-8 pt-6 border-t border-zinc-100 dark:border-zinc-800">
                    <h3 class="text-xs font-bold text-zinc-500 uppercase tracking-wider">2. Product Details & Stock Placement</h3>
                    
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Product / Item Master <span class="text-rose-500">*</span></label>
                        <x-form.select name="items[0][item_id]" required onchange="updateUnit(this)" class="item-selector">
                            <option value="">Select product item...</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" data-unit="{{ $item->base_unit }}">
                                    {{ $item->name }} ({{ $item->code }})
                                </option>
                            @endforeach
                        </x-form.select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Quantity <span class="text-rose-500">*</span></label>
                            <x-form.input type="number" name="items[0][qty]" id="qty" step="0.01" required placeholder="0.00" oninput="recalculate()" class="font-bold" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Unit of Measure</label>
                            <x-form.input type="text" name="items[0][unit]" id="unit" value="kg" readonly tabindex="-1" class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 font-semibold" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Rate per Unit (₹) <span class="text-rose-500">*</span></label>
                            <x-form.input type="number" name="items[0][rate]" id="rate" step="0.01" required placeholder="0.00" oninput="recalculate()" class="font-bold" />
                        </div>
                    </div>
                </div>

                {{-- 3. Billing Total and Tax details --}}
                <div class="rounded-xl p-5 border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/30">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">GST Tax Percentage (%)</label>
                                <x-form.input type="number" name="gst_percentage" id="gst-percentage" value="18" step="0.1" oninput="recalculate()" class="font-bold text-center" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Computed GST Value</label>
                                <x-form.input type="text" name="display_tax" id="display-tax" readonly value="₹0.00" class="bg-zinc-100 dark:bg-zinc-800 font-mono text-zinc-500 font-semibold text-center" tabindex="-1" />
                            </div>
                        </div>
                        
                        <div class="flex flex-col gap-3">
                            <div>
                                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest block mb-1">Net Grand Total</span>
                                <span id="display-total" class="text-3xl font-black text-zinc-900 dark:text-white font-mono">₹0.00</span>
                            </div>
                            <x-button type="submit" icon="task_alt" class="w-full justify-center">
                                Save Purchase
                            </x-button>
                        </div>
                    </div>
                </div>

            </form>
        </x-card>

    </div>
</div>
@endsection

@push('scripts')
<script>
    function updateUnit(selectEl) {
        const option = selectEl.options[selectEl.selectedIndex];
        const unit = option ? option.getAttribute('data-unit') : 'kg';
        document.getElementById('unit').value = unit || 'kg';
    }

    function recalculate() {
        const qty = parseFloat(document.getElementById('qty').value) || 0;
        const rate = parseFloat(document.getElementById('rate').value) || 0;
        const subtotal = qty * rate;

        const gstPercentage = parseFloat(document.getElementById('gst-percentage').value) || 0;
        const gstAmount = subtotal * (gstPercentage / 100);
        const grandTotal = subtotal + gstAmount;

        document.getElementById('display-tax').value = '₹' + gstAmount.toFixed(2);
        document.getElementById('display-total').textContent = '₹' + grandTotal.toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Set initial calculations on load
    document.addEventListener('DOMContentLoaded', () => {
        recalculate();
    });
</script>
@endpush

