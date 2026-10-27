@extends('layouts.app')
@section('title', 'Edit Purchase Refill')

@section('content')
<div class="space-y-6">

    <x-page-header title="Edit Purchase Entry" subtitle="Update supplier information, dynamic transaction items, or warehouse placements">
        <x-button variant="ghost" href="{{ route('purchases.show', $purchase->id) }}" icon="arrow_back">
            Back to Invoice
        </x-button>
        <div class="ml-auto">
            <span class="text-xs font-mono px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-bold border border-emerald-100 dark:border-emerald-900/50">
                Invoice ID: #{{ str_pad($purchase->id, 5, '0', STR_PAD_LEFT) }}
            </span>
        </div>
    </x-page-header>

    <x-card>
        <form action="{{ route('purchases.update', $purchase->id) }}" method="POST" id="purchase-form">
            @csrf
            @method('PUT')
            
            <div class="flex items-center gap-2 mb-6">
                <span class="material-symbols-rounded text-emerald-600 dark:text-emerald-400">edit_document</span>
                <h2 class="text-lg font-bold text-zinc-800 dark:text-white">Update Purchase Transaction Details</h2>
            </div>

            {{-- 1. Header Information Row --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8 pb-8 border-b border-zinc-200 dark:border-zinc-700">
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">1. Vendor / Partner <span class="text-rose-500">*</span></label>
                    <x-form.select name="vendor_name" required>
                        <option value="">Select supply partner...</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->firm_name }}" {{ $purchase->vendor_name === $vendor->firm_name ? 'selected' : '' }}>
                                {{ $vendor->firm_name }}
                            </option>
                        @endforeach
                    </x-form.select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">2. Invoice Number / Bill ID <span class="text-xs text-zinc-400 font-normal">(Auto)</span></label>
                    <x-form.input type="text" name="invoice_no" value="{{ old('invoice_no', $autoInvoiceNo) }}" readonly class="bg-zinc-100 dark:bg-zinc-800 text-zinc-500 cursor-not-allowed font-mono" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">3. Billing Date <span class="text-rose-500">*</span></label>
                    <x-form.input type="date" name="date" required value="{{ old('date', $purchase->date->format('Y-m-d')) }}" class="font-semibold" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">4. Payment Mode <span class="text-rose-500">*</span></label>
                    <x-form.select name="payment_mode" required onchange="toggleDueDateField()">
                        <option value="Cash" {{ $purchase->payment_mode === 'Cash' ? 'selected' : '' }}>Cash</option>
                        <option value="UPI" {{ $purchase->payment_mode === 'UPI' ? 'selected' : '' }}>UPI</option>
                        <option value="NEFT" {{ $purchase->payment_mode === 'NEFT' ? 'selected' : '' }}>NEFT</option>
                        <option value="Cheque" {{ $purchase->payment_mode === 'Cheque' ? 'selected' : '' }}>Cheque</option>
                        <option value="Credit" {{ $purchase->payment_mode === 'Credit' ? 'selected' : '' }}>Credit</option>
                    </x-form.select>
                </div>
                
                {{-- Conditional Payment Due Date Field --}}
                <div id="due-date-group" class="hidden">
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">5. Payment Due Date <span class="text-rose-500">*</span></label>
                    <x-form.input type="date" name="due_date" id="due_date_input" value="{{ old('due_date', $purchase->due_date ? $purchase->due_date->format('Y-m-d') : '') }}" class="font-semibold" />
                </div>
            </div>

            {{-- 2. Dynamic Refill Rows Table --}}
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2 text-sm font-bold text-zinc-700 dark:text-zinc-300">
                        <span class="material-symbols-rounded text-zinc-500">list_alt</span>
                        <span>Procured Products & Warehouse Placement</span>
                    </div>
                    <x-button type="button" variant="secondary" onclick="addRow()" size="sm" icon="add">
                        Add Item Row
                    </x-button>
                </div>

                <div class="border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
                    <table class="w-full text-left text-sm" id="items-table">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-zinc-500 text-xs uppercase font-bold">
                            <tr>
                                <th class="p-3 w-[30%]">Product / Item Master <span class="text-rose-500">*</span></th>
                                <th class="p-3 w-[15%]">Qty <span class="text-rose-500">*</span></th>
                                <th class="p-3 w-[10%]">Unit</th>
                                <th class="p-3 w-[15%]">Rate (₹) <span class="text-rose-500">*</span></th>
                                <th class="p-3 w-[20%] text-right">Total Amount</th>
                                <th class="p-3 w-[10%]"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body" class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($purchase->items as $index => $item)
                            <tr class="item-row hover:bg-zinc-50/50 dark:hover:bg-zinc-800/20 transition-colors">
                                <td class="p-3">
                                    <x-form.select name="items[{{ $index }}][item_id]" required onchange="updateUnit(this)" class="item-selector">
                                        <option value="">Select Product...</option>
                                        @foreach($items as $masterItem)
                                            <option value="{{ $masterItem->id }}" data-unit="{{ $masterItem->base_unit }}" {{ $item->item_id == $masterItem->id ? 'selected' : '' }}>
                                                {{ $masterItem->name }} ({{ $masterItem->code }})
                                            </option>
                                        @endforeach
                                    </x-form.select>
                                </td>
                                <td class="p-3">
                                    <x-form.input type="number" name="items[{{ $index }}][qty]" value="{{ $item->quantity }}" step="0.01" required placeholder="0.00" class="row-qty font-bold" oninput="recalculate()" />
                                </td>
                                <td class="p-3">
                                    <x-form.input type="text" name="items[{{ $index }}][unit]" value="{{ $item->unit }}" class="row-unit bg-zinc-50 dark:bg-zinc-800/50" readonly tabindex="-1" />
                                </td>
                                <td class="p-3">
                                    <x-form.input type="number" name="items[{{ $index }}][rate]" value="{{ $item->rate }}" step="0.01" required placeholder="0.00" class="row-rate font-bold" oninput="recalculate()" />
                                </td>
                                <td class="p-3 text-right font-bold text-zinc-800 dark:text-zinc-200 text-base">
                                    <span class="row-total">₹{{ number_format($item->quantity * $item->rate, 2) }}</span>
                                </td>
                                <td class="p-3 text-center">
                                    @if($index > 0)
                                    <button type="button" onclick="this.closest('tr').remove(); recalculate();" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors" title="Remove row">
                                        <span class="material-symbols-rounded text-[18px]">delete</span>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 3. Billing Summaries Block --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-end">
                <div class="bg-zinc-50 dark:bg-zinc-800/30 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5">
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-4">Tax & Configuration</label>
                    <div class="flex items-center gap-4">
                        <div class="w-24">
                            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">GST %</label>
                            <x-form.input type="number" name="gst_percentage" id="gst-percentage" value="{{ $purchase->gst_percentage }}" step="0.1" class="font-bold text-center" oninput="recalculate()" />
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Computed GST Value</label>
                            <x-form.input type="text" name="display_tax" id="display-tax" readonly value="₹{{ number_format($purchase->gst_amount, 2) }}" class="bg-zinc-100 dark:bg-zinc-800 font-mono text-zinc-500 font-semibold" tabindex="-1" />
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-xl p-6 shadow-xl shadow-emerald-500/20 text-white flex flex-col sm:flex-row items-center justify-between gap-6">
                    <div>
                        <span class="text-emerald-100 text-xs font-bold uppercase tracking-wider block mb-1">Final Grand Net Total</span>
                        <span id="display-total" class="text-3xl font-black font-mono tracking-tight">₹{{ number_format($purchase->total_amount, 2) }}</span>
                    </div>
                    <button type="submit" class="w-full sm:w-auto bg-white text-emerald-700 hover:bg-emerald-50 px-6 py-3 rounded-lg font-bold flex items-center justify-center gap-2 transition-transform active:scale-95 shadow-md">
                        <span class="material-symbols-rounded">check_circle</span>
                        Update Purchase
                    </button>
                </div>
            </div>
        </form>
    </x-card>

</div>
@endsection

@push('scripts')
<script>
let rowCount = {{ $purchase->items->count() }};

const ITEM_OPTIONS = `@foreach($items as $item)<option value="{{ $item->id }}" data-unit="{{ $item->base_unit }}">{{ $item->name }} ({{ $item->code }})</option>@endforeach`;

function addRow() {
    const body = document.getElementById('items-body');
    const newRow = document.createElement('tr');
    newRow.className = 'item-row hover:bg-zinc-50/50 dark:hover:bg-zinc-800/20 transition-colors border-t border-zinc-100 dark:border-zinc-800';
    newRow.innerHTML = `
        <td class="p-3">
            <select name="items[${rowCount}][item_id]" required onchange="updateUnit(this)" class="w-full px-3 py-2 border border-zinc-200 dark:border-zinc-700 rounded-lg text-sm bg-white dark:bg-zinc-900 item-selector focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-shadow">
                <option value="">Select Product...</option>
                ${ITEM_OPTIONS}
            </select>
        </td>
        <td class="p-3">
            <input type="number" name="items[${rowCount}][qty]" step="0.01" required placeholder="0.00" class="w-full px-3 py-2 border border-zinc-200 dark:border-zinc-700 rounded-lg text-sm bg-white dark:bg-zinc-900 row-qty font-bold focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-shadow" oninput="recalculate()">
        </td>
        <td class="p-3">
            <input type="text" name="items[${rowCount}][unit]" value="kg" class="w-full px-3 py-2 border border-zinc-200 dark:border-zinc-700 rounded-lg text-sm bg-zinc-50 dark:bg-zinc-800/50 row-unit" readonly tabindex="-1">
        </td>
        <td class="p-3">
            <input type="number" name="items[${rowCount}][rate]" step="0.01" required placeholder="0.00" class="w-full px-3 py-2 border border-zinc-200 dark:border-zinc-700 rounded-lg text-sm bg-white dark:bg-zinc-900 row-rate font-bold focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-shadow" oninput="recalculate()">
        </td>
        <td class="p-3 text-right font-bold text-zinc-800 dark:text-zinc-200 text-base">
            <span class="row-total">₹0.00</span>
        </td>
        <td class="p-3 text-center">
            <button type="button" onclick="this.closest('tr').remove(); recalculate();" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors" title="Remove row">
                <span class="material-symbols-rounded text-[18px]">delete</span>
            </button>
        </td>
    `;
    body.appendChild(newRow);
    rowCount++;
}

function updateUnit(select) {
    const unit = select.options[select.selectedIndex].getAttribute('data-unit');
    const row = select.closest('tr');
    if (unit) {
        row.querySelector('.row-unit').value = unit;
    }
}

function recalculate() {
    let subtotal = 0;
    const rows = document.querySelectorAll('.item-row');
    
    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('.row-qty').value) || 0;
        const rate = parseFloat(row.querySelector('.row-rate').value) || 0;
        const total = qty * rate;
        
        row.querySelector('.row-total').textContent = '₹' + total.toFixed(2);
        subtotal += total;
    });
    
    const gstPercentage = parseFloat(document.getElementById('gst-percentage').value) || 0;
    const gstAmt = subtotal * gstPercentage / 100;
    const finalTotal = subtotal + gstAmt;
    
    document.getElementById('display-tax').value = '₹' + gstAmt.toFixed(2);
    document.getElementById('display-total').textContent = '₹' + finalTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 });
}

function toggleDueDateField() {
    const paymentModeSelect = document.querySelector('select[name="payment_mode"]');
    const dueDateGroup = document.getElementById('due-date-group');
    const dueDateInput = document.getElementById('due_date_input');
    
    if (!paymentModeSelect || !dueDateGroup) return;

    if (paymentModeSelect.value === 'Credit') {
        dueDateGroup.classList.remove('hidden');
        dueDateInput.required = true;
        
        if (!dueDateInput.value) {
            const today = new Date();
            today.setDate(today.getDate() + 15);
            dueDateInput.value = today.toISOString().split('T')[0];
        }
    } else {
        dueDateGroup.classList.add('hidden');
        dueDateInput.required = false;
        dueDateInput.value = '';
    }
}

window.addEventListener('DOMContentLoaded', () => {
    recalculate();
    toggleDueDateField();
});
</script>
@endpush
