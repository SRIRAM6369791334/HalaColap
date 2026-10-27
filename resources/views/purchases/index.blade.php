@extends('layouts.app')
@section('title', 'Purchase Entry')

@section('content')
<div class="space-y-6" x-data="{
    editId: 0,
    editFormAction: '',
    editVendorName: '',
    editInvoiceNo: '',
    editDate: '',
    editPaymentMode: 'Cash',
    editGstPercentage: 18,
    editItems: [],
}">

    <x-page-header title="Purchase Entry & Refills" subtitle="Record incoming inventory supply and map it to specific batches & locations">
        <x-button href="{{ route('purchases.invoices') }}" variant="secondary" icon="receipt_long">
            Invoice Archive
        </x-button>
        <x-button href="{{ route('purchases.export') }}" variant="secondary" icon="download">
            Export
        </x-button>
    </x-page-header>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <x-stat-card label="Purchase Date" value="{{ \Carbon\Carbon::parse($date)->format('d M Y') }}" icon="calendar_today" color="blue" />
        <x-stat-card label="Day" value="{{ \Carbon\Carbon::parse($date)->format('l') }}" icon="event" color="emerald" />
        <x-stat-card label="Total Amount" value="Rs {{ number_format($dailyTotalAmount, 0) }}" icon="payments" color="indigo" />
        <x-stat-card label="Total GST" value="Rs {{ number_format($dailyTotalGST, 0) }}" icon="receipt" color="amber" />
        <x-stat-card label="Items" value="{{ number_format($dailyItemCount, 0) }}" icon="inventory_2" color="violet" />
    </div>

    <!-- @can('create purchases')
    {{-- Inline Form Block --}}
    <x-card class="transition-all duration-300" x-data="{ showForm: false }" x-bind:class="showForm ? 'ring-4 ring-emerald-50 dark:ring-emerald-900/30 border-emerald-100 dark:border-emerald-800' : 'hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)]'">
        <div class="flex justify-between items-center cursor-pointer" @click="showForm = !showForm">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-[14px] bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-lg shadow-emerald-500/20">
                    <span class="material-symbols-rounded text-[22px]">add_circle</span>
                </div>
                <div>
                    <h2 class="text-[1.1rem] font-extrabold text-zinc-800 dark:text-zinc-100 tracking-tight">Record Purchase</h2>
                    <p class="text-[0.75rem] font-semibold text-zinc-400 dark:text-zinc-500 mt-0.5 tracking-wide uppercase">Click to expand and fill details</p>
                </div>
            </div>
            <button type="button" class="flex items-center justify-center h-10 px-4 gap-2 rounded-xl text-sm transition-all duration-300 font-bold" :class="showForm ? 'bg-zinc-800 dark:bg-zinc-100 text-white dark:text-zinc-900' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'">
                <span class="material-symbols-rounded" x-text="showForm ? 'expand_less' : 'add'"></span>
                <span x-text="showForm ? 'Close Panel' : 'New Entry'"></span>
            </button>
        </div>

        @if ($errors->any())
            <div class="mt-4 mb-4 mx-6 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl relative">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li class="text-sm font-medium">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <div x-show="showForm" x-cloak class="pt-6 mt-4 border-t border-zinc-100 dark:border-zinc-800">
            <form action="{{ route('purchases.store') }}" method="POST" id="purchase-form" x-data="{ paymentMode: 'Cash' }">
                @csrf
                
                {{-- 1. Header Information Row --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">1. Vendor / Partner <span class="text-rose-500">*</span></label>
                        <x-form.select name="vendor_name" required onchange="updateVendorOutstanding()">
                            <option value="">Select supply partner...</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->firm_name }}" data-outstanding="{{ $vendor->outstanding_balance }}" {{ old('vendor_name') === $vendor->firm_name ? 'selected' : '' }}>
                                    {{ $vendor->firm_name }} @if($vendor->outstanding_balance > 0) (Outstanding: ₹{{ number_format($vendor->outstanding_balance, 0) }}) @endif
                                </option>
                            @endforeach
                        </x-form.select>
                        
                        <div id="vendor-outstanding-info" class="mt-2 text-xs font-bold text-amber-600 dark:text-amber-400 hidden items-center gap-1.5 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1.5 rounded-lg">
                            <span class="material-symbols-rounded text-[16px]">account_balance_wallet</span>
                            <span>Current Dues:</span>
                            <span id="vendor-outstanding-amount" class="font-mono text-amber-700 dark:text-amber-300 font-extrabold">₹0.00</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">2. Invoice Number / Bill ID <span class="text-xs text-zinc-400 font-normal">(Auto)</span></label>
                        <x-form.input type="text" name="invoice_no" value="{{ old('invoice_no', $autoInvoiceNo) }}" readonly class="bg-zinc-100 dark:bg-zinc-800 text-zinc-500 cursor-not-allowed font-mono" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">3. Billing Date <span class="text-rose-500">*</span></label>
                        <x-form.input type="date" name="date" required value="{{ old('date', $date) }}" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">4. Payment Mode <span class="text-rose-500">*</span></label>
                        <x-form.select name="payment_mode" required x-model="paymentMode">
                            <option value="Cash" {{ old('payment_mode', 'Cash') === 'Cash' ? 'selected' : '' }}>Cash</option>
                            <option value="Pay later(EMI)" {{ old('payment_mode') === 'Pay later(EMI)' ? 'selected' : '' }}>Pay later(EMI)</option>
                            <option value="UPI" {{ old('payment_mode') === 'UPI' ? 'selected' : '' }}>UPI</option>
                            <option value="NEFT" {{ old('payment_mode') === 'NEFT' ? 'selected' : '' }}>NEFT</option>
                            <option value="Cheque(Bank Transfer)" {{ old('payment_mode') === 'Cheque(Bank Transfer)' ? 'selected' : '' }}>Cheque(Bank Transfer)</option>
                        </x-form.select>
                    </div>
                </div>

                <x-emi-schedule-generator totalAmountId="display-total" />

                {{-- 2. Dynamic Refill Rows Table --}}
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2 text-sm font-bold text-zinc-700 dark:text-zinc-300">
                            <span class="material-symbols-rounded text-zinc-500">list_alt</span>
                            <span>Procured Products</span>
                        </div>
                        <x-button type="button" variant="secondary" onclick="addRow()" size="sm" icon="add">
                            Add Item Row
                        </x-button>
                    </div>

                    <div class="border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
                        <table class="w-full text-left text-sm" id="items-table">
                            <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-zinc-500 dark:text-zinc-400 text-xs uppercase font-bold">
                                <tr>
                                    <th class="p-3 w-[30%]">Product / Item <span class="text-rose-500">*</span></th>
                                    <th class="p-3 w-[15%]">Qty <span class="text-rose-500">*</span></th>
                                    <th class="p-3 w-[15%]">Unit</th>
                                    <th class="p-3 w-[15%]">Rate (₹) <span class="text-rose-500">*</span></th>
                                    <th class="p-3 w-[15%] text-right">Total Amount</th>
                                    <th class="p-3 w-[10%]"></th>
                                </tr>
                            </thead>
                            <tbody id="items-body" class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                <tr class="item-row hover:bg-zinc-50/50 dark:hover:bg-zinc-800/20 transition-colors">
                                    <td class="p-3">
                                        <input list="items-list" name="items[0][name]" required onchange="updateUnit(this)" class="w-full px-3 py-2 border border-zinc-200 dark:border-zinc-700 rounded-lg text-sm bg-white dark:bg-zinc-900 item-selector focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-shadow" placeholder="Select or type product...">
                                        <datalist id="items-list">
                                            @foreach($items as $item)
                                                <option value="{{ $item->name }}" data-unit="{{ $item->base_unit }}"></option>
                                            @endforeach
                                        </datalist>
                                    </td>
                                    <td class="p-3">
                                        <x-form.input type="number" name="items[0][qty]" step="0.01" required placeholder="0.00" class="row-qty" oninput="recalculate()" />
                                    </td>
                                    <td class="p-3">
                                        <x-form.input type="text" name="items[0][unit]" value="kg" class="row-unit" />
                                    </td>
                                    <td class="p-3">
                                        <x-form.input type="number" name="items[0][rate]" step="0.01" required placeholder="0.00" class="row-rate" oninput="recalculate()" />
                                    </td>
                                    <td class="p-3 text-right font-bold text-zinc-800 dark:text-zinc-200 text-base">
                                        <span class="row-total">₹0.00</span>
                                    </td>
                                    <td class="p-3 text-center"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 3. Billing Summaries Block --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-end">
                    <div class="bg-zinc-50 dark:bg-zinc-800/30 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5">
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-4">Tax Configuration</label>
                        <div class="flex items-center gap-4">
                            <div class="w-24">
                                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">GST %</label>
                                <x-form.input type="number" name="gst_percentage" id="gst-percentage" value="18" step="0.1" class="font-bold text-center" oninput="recalculate()" />
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Computed GST Value</label>
                                <x-form.input type="text" name="display_tax" id="display-tax" readonly value="₹0.00" class="bg-zinc-100 dark:bg-zinc-800 font-mono" tabindex="-1" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-xl p-6 shadow-xl shadow-emerald-500/20 text-white flex flex-col sm:flex-row items-center justify-between gap-6">
                        <div>
                            <span class="text-emerald-100 text-xs font-bold uppercase tracking-wider block mb-1">Final Grand Net Total</span>
                            <span id="display-total" class="text-3xl font-black font-mono tracking-tight">₹0.00</span>
                        </div>
                        <button type="submit" class="w-full sm:w-auto bg-white text-emerald-700 hover:bg-emerald-50 px-6 py-3 rounded-lg font-bold flex items-center justify-center gap-2 transition-transform active:scale-95 shadow-md">
                            <span class="material-symbols-rounded">check_circle</span>
                            Save Purchase
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </x-card>
    @endcan -->

    {{-- Date Filter + Daily Purchases --}}
    <!-- <x-card>
        <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <h2 class="font-cabinet text-lg font-bold text-zinc-900 dark:text-zinc-50">Daily Purchases</h2>
            <form method="GET" class="flex flex-col sm:flex-row gap-3">
                <input type="date" name="date" value="{{ $date }}" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search vendor or invoice" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm">
                <x-button type="submit" variant="outline" icon="filter_alt">Filter</x-button>
            </form>
        </div>

        <x-data-table :headers="['Date', 'Vendor & Invoice', 'Items', ['label' => 'Amount', 'align' => 'right'], ['label' => 'GST', 'align' => 'right'], ['label' => 'Total', 'align' => 'right'], ['label' => 'Mode', 'align' => 'center'], 'Actions']">
            @forelse($dailyPurchases as $p)
                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                    <td class="px-4 py-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">
                        {{ $p->date->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <x-avatar name="{{ $p->vendor_name }}" size="sm" />
                            <div>
                                <span class="block font-bold text-zinc-900 dark:text-zinc-100 text-sm">{{ $p->vendor_name }}</span>
                                <span class="block text-[10px] text-zinc-500 font-mono">#{{ $p->invoice_no ?: 'N/A' }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-1">
                            @foreach($p->items as $item)
                                <span class="px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-800 text-[11px] font-medium text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700">
                                    {{ $item->item_name }} <b>({{ number_format($item->quantity) }} {{ $item->unit }})</b>
                                </span>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right font-jetbrains text-sm text-zinc-600">{{ number_format($p->total_amount - $p->gst_amount, 0) }}</td>
                    <td class="px-4 py-3 text-right font-jetbrains text-sm text-amber-600">{{ number_format($p->gst_amount, 0) }}</td>
                    <td class="px-4 py-3 text-right font-jetbrains font-bold text-sm">{{ number_format($p->total_amount, 0) }}</td>
                    <td class="px-4 py-3 text-center">
                        @php
                            $pc = match(strtolower($p->payment_mode)) {
                                'cash' => 'emerald', 'upi' => 'indigo', 'neft' => 'sky', default => 'slate',
                            };
                        @endphp
                        <x-badge :color="$pc">{{ $p->payment_mode }}</x-badge>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <button
                                type="button"
                                x-on:click="
                                    $dispatch('open-modal', 'edit-purchase-modal');
                                    $nextTick(() => {
                                        editId = {{ $p->id }};
                                        editFormAction = '{{ route('purchases.update', $p->id) }}';
                                        editVendorName = '{{ addslashes($p->vendor_name) }}';
                                        editInvoiceNo = '{{ addslashes($p->invoice_no ?? '') }}';
                                        editDate = '{{ $p->date->format('Y-m-d') }}';
                                        editPaymentMode = '{{ $p->payment_mode }}';
                                        editGstPercentage = {{ $p->gst_percentage }};
                                        editItems = [
                                            @foreach($p->items as $item)
                                                { name: '{{ addslashes($item->item_name) }}', qty: {{ $item->quantity }}, unit: '{{ addslashes($item->unit) }}', rate: {{ $item->rate }} },
                                            @endforeach
                                        ];
                                    });
                                "
                                class="inline-flex items-center gap-1 text-xs font-medium text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300 transition-colors"
                            >
                                <span class="material-symbols-rounded text-sm">edit</span>
                                Edit
                            </button>
                            <form action="{{ route('purchases.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Delete this purchase?')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-1 text-xs font-medium text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 transition-colors">
                                    <span class="material-symbols-rounded text-sm">delete</span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center py-8 text-zinc-500">No purchases found for this date.</td></tr>
            @endforelse
            @if($dailyPurchases->hasPages())
                <x-slot:pagination>
                    {{ $dailyPurchases->withQueryString()->links() }}
                </x-slot:pagination>
            @endif
        </x-data-table>
    </x-card> -->

    {{-- Edit Purchase Modal --}}
    <x-modal name="edit-purchase-modal" title="Edit Purchase" subtitle="Update vendor, items, or payment details" icon="edit" maxWidth="2xl">
        <form id="edit-purchase-form" :action="editFormAction" method="POST">
            @csrf
            <input type="hidden" name="_method" value="PUT">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Vendor</label>
                    <select name="vendor_name" required x-model="editVendorName" class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm">
                        <option value="">Select vendor...</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->firm_name }}">{{ $vendor->firm_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Invoice No</label>
                    <input type="text" name="invoice_no" x-model="editInvoiceNo" class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Date</label>
                    <input type="date" name="date" required x-model="editDate" class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Payment Mode</label>
                    <select name="payment_mode" required x-model="editPaymentMode" class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm">
                        <option value="Cash">Cash</option>
                        <option value="UPI">UPI</option>
                        <option value="NEFT">NEFT</option>
                        <option value="Cheque(Bank Transfer)">Cheque(Bank Transfer)</option>
                        <option value="Pay later(EMI)">Pay later(EMI)</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">GST %</label>
                <input type="number" name="gst_percentage" step="0.1" min="0" max="28" x-model.number="editGstPercentage" class="w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm font-jetbrains w-24">
            </div>

            <div class="mb-4">
                <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Items</label>
                <p class="text-xs text-zinc-500 mb-2">Edit items below (qty, rate changes are applied on save)</p>
                <template x-for="(item, idx) in editItems" :key="idx">
                    <div class="flex gap-2 mb-2 items-start">
                        <input type="text" x-model="item.name" :name="'items[' + idx + '][name]'" required class="flex-1 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm">
                        <input type="number" step="0.01" x-model="item.qty" :name="'items[' + idx + '][qty]'" required placeholder="Qty" class="w-24 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-center font-jetbrains">
                        <input type="text" x-model="item.unit" :name="'items[' + idx + '][unit]'" class="w-16 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-center">
                        <input type="number" step="0.01" x-model="item.rate" :name="'items[' + idx + '][rate]'" required placeholder="Rate" class="w-24 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-center font-jetbrains">
                    </div>
                </template>
                <input type="hidden" name="vendor_id" value="">
            </div>

            <x-slot:footer>
                <x-button type="button" variant="outline" x-on:click="$dispatch('close-modal', 'edit-purchase-modal')">Cancel</x-button>
                <x-button type="submit" form="edit-purchase-form" variant="primary" icon="save">Save Changes</x-button>
            </x-slot:footer>
        </form>
    </x-modal>

    {{-- 4. Vendor Bird Supply via Day-Load --}}
    <x-card>
        <div class="flex flex-col sm:flex-row gap-4 mb-6 justify-between items-center">
            <div>
                <h2 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">Vendor Bird Supply</h2>
                <p class="text-xs text-zinc-500 mt-1">Day-load birds supplied by vendors</p>
            </div>
            <div class="flex gap-3 text-xs font-bold">
                <span class="px-3 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                    <span class="material-symbols-rounded text-[14px] align-text-bottom">inventory_2</span>
                    {{ number_format($vendorDayLoadTotalBoxes) }} Boxes
                </span>
                <span class="px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                    <span class="material-symbols-rounded text-[14px] align-text-bottom">scale</span>
                    {{ number_format($vendorDayLoadTotalBird, 1) }} kg Bird
                </span>
                <span class="px-3 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                    <span class="material-symbols-rounded text-[14px] align-text-bottom">agriculture</span>
                    {{ number_format($vendorDayLoadTotalFarm, 1) }} kg Farm
                </span>
            </div>
        </div>

        {{-- Filter Form --}}
        <div class="mb-6 p-4 bg-zinc-50/50 dark:bg-zinc-800/30 rounded-2xl border border-zinc-200/50 dark:border-zinc-700/50"
             x-data="{
                setDates(period) {
                    const now = new Date();
                    const to = now.toISOString().split('T')[0];
                    let from;
                    if (period === 'today') {
                        from = to;
                    } else if (period === '7d') {
                        from = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                    } else if (period === '30d') {
                        from = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                    } else if (period === 'month') {
                        from = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
                    }
                    $refs.dateFrom.value = from;
                    $refs.dateTo.value = to;
                    $refs.filterForm.submit();
                }
             }">
            <form method="GET" action="{{ route('purchases.entry') }}" x-ref="filterForm" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Vendor</label>
                    <select name="vendor_filter" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm min-w-[160px]">
                        <option value="">All Vendors</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}" {{ $vendorFilter == $v->id ? 'selected' : '' }}>{{ $v->firm_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">From</label>
                    <input type="date" name="date_from" x-ref="dateFrom" value="{{ $dateFrom }}" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">To</label>
                    <input type="date" name="date_to" x-ref="dateTo" value="{{ $dateTo }}" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm">
                </div>
                <x-button type="submit" variant="primary" icon="filter_alt" size="sm">Filter</x-button>
                @if($vendorFilter || $dateFrom || $dateTo)
                    <a href="{{ route('purchases.entry') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-rose-50 hover:border-rose-200 hover:text-rose-600 dark:hover:bg-rose-900/20 dark:hover:border-rose-800 dark:hover:text-rose-400 transition-all">
                        <span class="material-symbols-rounded text-[16px]">close</span>
                        Clear Filters
                    </a>
                @endif
                <div class="flex gap-1.5 ml-auto">
                    <button type="button" @click="setDates('today')" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 dark:hover:bg-emerald-900/20 dark:hover:border-emerald-800 dark:hover:text-emerald-400 transition-all">Today</button>
                    <button type="button" @click="setDates('7d')" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 dark:hover:bg-emerald-900/20 dark:hover:border-emerald-800 dark:hover:text-emerald-400 transition-all">7 Days</button>
                    <button type="button" @click="setDates('30d')" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 dark:hover:bg-emerald-900/20 dark:hover:border-emerald-800 dark:hover:text-emerald-400 transition-all">30 Days</button>
                    <button type="button" @click="setDates('month')" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 dark:hover:bg-emerald-900/20 dark:hover:border-emerald-800 dark:hover:text-emerald-400 transition-all">This Month</button>
                </div>
            </form>
        </div>

        <div class="bg-white/80 dark:bg-zinc-900/80 backdrop-blur-2xl rounded-[2rem] border border-white/60 dark:border-zinc-800/50 shadow-xl shadow-zinc-200/50 overflow-hidden transition-all duration-300">
            <div class="overflow-x-auto [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                <table class="w-full text-sm text-left text-zinc-600 dark:text-zinc-400 font-outfit">
                    <thead class="text-xs text-zinc-500 dark:text-zinc-400 uppercase bg-transparent border-b border-zinc-200/50 dark:border-zinc-700/50 font-cabinet [&_th]:px-6 [&_th]:py-4 [&_th]:font-semibold [&_th]:tracking-wider [&_th]:whitespace-nowrap">
                        <tr>
                            <th>Date</th>
                            <th>Vendor</th>
                            <th class="text-right">Box</th>
                            <th class="text-right">Bird Weight</th>
                            <th class="text-right">Paper Rate</th>
                            <th class="text-right">Vendor Rate</th>
                            <th class="text-right">Farm Weight</th>
                            <th class="text-right">Total Price</th>
                            <th class="text-center" style="width:80px"></th>
                        </tr>
                    </thead>
                    @forelse($vendorDayLoads as $idx => $entry)
                        <tbody x-data="{ open: false }">
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors border-b border-zinc-100 dark:border-zinc-800"
                                :class="{ 'cursor-pointer': {{ $entry->entry_count }} > 1 }"
                                @click="if({{ $entry->entry_count }} > 1) open = !open">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                    {{ $entry->date ? $entry->date->format('d M Y') : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <x-avatar name="{{ $entry->vendor_name }}" size="sm" />
                                        <div>
                                            <span class="font-bold text-zinc-900 dark:text-zinc-100 text-sm">{{ $entry->vendor_name }}</span>
                                            @if($entry->entry_count > 1)
                                                <span class="ml-1.5 px-1.5 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-900/20 text-[10px] font-bold text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">{{ $entry->entry_count }} entries</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-jetbrains text-sm">{{ $entry->total_boxes }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-jetbrains text-sm">{{ number_format($entry->total_bird_weight, 1) }} kg</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-jetbrains text-sm">Rs {{ number_format($entry->paper_rate, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-jetbrains text-sm">
                                    @if($entry->vendor_rate > 0)
                                        Rs {{ number_format($entry->vendor_rate, 2) }}
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-jetbrains text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($entry->total_farm_weight, 1) }} kg</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    @if($entry->has_vendor_rate)
                                        <span class="font-jetbrains text-sm font-bold text-zinc-900 dark:text-zinc-100">Rs {{ number_format($entry->total_price, 2) }}</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-amber-50 dark:bg-amber-900/20 text-[10px] font-bold text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                            <span class="material-symbols-rounded text-[12px]">warning</span>
                                            Enter vendor price
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($entry->entry_count > 1)
                                        <button type="button" @click.stop="open = !open"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition-all duration-200"
                                            :class="open ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 dark:hover:bg-indigo-900/20 dark:hover:text-indigo-400 dark:hover:border-indigo-800'">
                                            <span class="material-symbols-rounded text-[14px] transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
                                            <span x-text="open ? 'Hide' : 'View'"></span>
                                        </button>
                                    @else
                                        <span class="text-zinc-300 dark:text-zinc-700 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                            {{-- Expandable detail row --}}
                            @if($entry->entry_count > 1)
                            <tr x-show="open" x-cloak
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="bg-zinc-50/80 dark:bg-zinc-800/40">
                                <td colspan="9" class="px-6 py-3">
                                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden">
                                        <table class="w-full text-left text-sm">
                                            <thead class="bg-zinc-100 dark:bg-zinc-700/50">
                                                <tr>
                                                    <th class="px-3 py-2 text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase">#</th>
                                                    <th class="px-3 py-2 text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase">Dealer</th>
                                                    <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase">Box</th>
                                                    <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase">Bird Wt</th>
                                                    <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase">Paper Rate</th>
                                                    <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase">Vendor Rate</th>
                                                    <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase">Farm Wt</th>
                                                    <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase">Total Amount</th>
                                                    <th class="px-3 py-2 text-center text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                                                @foreach($entry->entries as $i => $sub)
                                                    <tr class="hover:bg-white dark:hover:bg-zinc-800/60 transition-colors">
                                                        <td class="px-3 py-2 text-xs font-mono">
                                                            @if(isset($sub->sort_order) && $sub->sort_order)
                                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-black bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300 dark:border-amber-700">#{{ $sub->sort_order }}</span>
                                                            @else
                                                                <span class="text-zinc-400 font-jetbrains">{{ $i + 1 }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-2 text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $sub->dealer_name }}</td>
                                                        <td class="px-3 py-2 text-right text-xs font-jetbrains text-zinc-600 dark:text-zinc-400">{{ $sub->boxes }}</td>
                                                        <td class="px-3 py-2 text-right text-xs font-jetbrains text-zinc-600 dark:text-zinc-400">{{ number_format($sub->bird_weight, 1) }} kg</td>
                                                        <td class="px-3 py-2 text-right text-xs font-jetbrains text-zinc-600 dark:text-zinc-400">Rs {{ number_format($sub->paper_rate, 2) }}</td>
                                                        <td class="px-3 py-2 text-right text-xs font-jetbrains text-zinc-600 dark:text-zinc-400">
                                                            @if($sub->vendor_rate > 0)
                                                                Rs {{ number_format($sub->vendor_rate, 2) }}
                                                            @else
                                                                <span class="text-zinc-400">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-2 text-right text-xs font-jetbrains text-zinc-600 dark:text-zinc-400">{{ number_format($sub->farm_weight, 1) }} kg</td>
                                                        <td class="px-3 py-2 text-right text-xs font-jetbrains font-bold text-zinc-700 dark:text-zinc-300">
                                                            @if($sub->vendor_cost > 0)
                                                                Rs {{ number_format($sub->vendor_cost, 2) }}
                                                            @else
                                                                <span class="text-amber-500 text-[10px] font-bold">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-2 text-center">
                                                            @php
                                                                $sc = match($sub->status) {
                                                                    'Active' => 'emerald', 'Adjusted' => 'amber', 'Split' => 'sky', 'Cancelled' => 'rose', default => 'zinc',
                                                                };
                                                            @endphp
                                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-{{ $sc }}-50 dark:bg-{{ $sc }}-900/20 text-{{ $sc }}-700 dark:text-{{ $sc }}-400 border border-{{ $sc }}-200 dark:border-{{ $sc }}-800">{{ $sub->status }}</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    @empty
                        <tbody>
                            <tr><td colspan="9" class="px-6 py-8 text-center text-zinc-500">No vendor day-load entries found.</td></tr>
                        </tbody>
                    @endforelse
                </table>
            </div>
            @if($vendorDayLoads->hasPages())
                <div class="p-4 border-t border-zinc-200/50 dark:border-zinc-800/50">
                    {{ $vendorDayLoads->links() }}
                </div>
            @endif
        </div>
    </x-card>

    <!-- {{-- 5. All Purchase History --}}
    <x-card>
        <div class="flex flex-col sm:flex-row gap-4 mb-6 justify-between items-center">
            <h2 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">All Purchase History</h2>
            <form method="GET" class="flex w-full sm:w-80">
                <x-search name="search" value="{{ $search }}" placeholder="Search vendor or product name..." class="w-full" />
            </form>
        </div>

        <x-data-table>
            <x-slot name="header">
                <tr>
                    <th class="w-[15%]">Date</th>
                    <th class="w-[25%]">Vendor & Invoice ID</th>
                    <th class="w-[30%]">Refilled Products</th>
                    <th class="w-[15%] text-right">Net Amount</th>
                    <th class="w-[10%] text-center">Payment Mode</th>
                    <th class="w-[5%] text-center">Action</th>
                </tr>
            </x-slot>
            @forelse($purchases as $p)
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                    <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400 font-medium">
                        {{ $p->date->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <x-avatar name="{{ $p->vendor_name }}" class="bg-indigo-100 text-indigo-700" />
                            <div>
                                <span class="block font-bold text-zinc-900 dark:text-zinc-100">{{ $p->vendor_name }}</span>
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400 font-mono mt-0.5">Bill ID: {{ $p->invoice_no ?: 'N/A' }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($p->items as $item)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-[11px] font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $item->item_name }} <b class="ml-1">({{ number_format($item->quantity) }} {{ $item->unit }})</b>
                                </span>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right font-black text-zinc-900 dark:text-zinc-100 text-base">
                        <x-currency :amount="$p->total_amount" />
                    </td>
                    <td class="px-4 py-3 text-center">
                        @php
                            $paymentColor = match(strtolower($p->payment_mode)) {
                                'cash' => 'emerald',
                                'upi' => 'indigo',
                                'neft' => 'sky',
                                'credit' => 'rose',
                                default => 'slate',
                            };
                        @endphp
                        <x-badge :color="$paymentColor">{{ $p->payment_mode }}</x-badge>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <x-button variant="ghost" size="sm" href="{{ route('purchases.show', $p->id) }}" icon="visibility" class="text-sky-600" />
                            @can('edit purchases')
                                <x-button variant="ghost" size="sm" href="{{ route('purchases.edit', $p->id) }}" icon="edit" class="text-amber-600" />
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-12">
                        <x-empty-state icon="receipt" title="No purchases matched" description="Adjust your filter query or record a new purchase above" />
                    </td>
                </tr>
            @endforelse
        </x-data-table>
        
        @if($purchases->hasPages())
            <div class="mt-4">
                {{ $purchases->withQueryString()->links() }}
            </div>
        @endif
    </x-card> -->

</div>
@endsection

@push('scripts')
<script>

let rowCount = 1;

const ITEM_OPTIONS = `@foreach($items as $item)<option value="{{ $item->name }}" data-unit="{{ $item->base_unit }}"></option>@endforeach`;

function addRow() {
    const body = document.getElementById('items-body');
    const newRow = document.createElement('tr');
    newRow.className = 'item-row hover:bg-zinc-50/50 dark:hover:bg-zinc-800/20 transition-colors border-t border-zinc-100 dark:border-zinc-800';
    newRow.innerHTML = `
        <td class="p-3">
            <input list="items-list" name="items[${rowCount}][name]" required onchange="updateUnit(this)" class="w-full px-3 py-2 border border-zinc-200 dark:border-zinc-700 rounded-lg text-sm bg-white dark:bg-zinc-900 item-selector focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-shadow" placeholder="Select or type product...">
        </td>
        <td class="p-3">
            <input type="number" name="items[${rowCount}][qty]" step="0.01" required placeholder="0.00" class="w-full px-3 py-2 border border-zinc-200 dark:border-zinc-700 rounded-lg text-sm bg-white dark:bg-zinc-900 row-qty focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-shadow" oninput="recalculate()">
        </td>
        <td class="p-3">
            <input type="text" name="items[${rowCount}][unit]" value="kg" class="w-full px-3 py-2 border border-zinc-200 dark:border-zinc-700 rounded-lg text-sm bg-white dark:bg-zinc-900 row-unit focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-shadow">
        </td>
        <td class="p-3">
            <input type="number" name="items[${rowCount}][rate]" step="0.01" required placeholder="0.00" class="w-full px-3 py-2 border border-zinc-200 dark:border-zinc-700 rounded-lg text-sm bg-white dark:bg-zinc-900 row-rate focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-shadow" oninput="recalculate()">
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

function updateUnit(input) {
    const list = document.getElementById('items-list');
    if (!list) return;
    const options = list.options;
    for (let i = 0; i < options.length; i++) {
        if (options[i].value === input.value) {
            const unit = options[i].getAttribute('data-unit');
            const row = input.closest('tr');
            if (unit && row) {
                row.querySelector('.row-unit').value = unit;
            }
            return;
        }
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
    
    const gstInput = document.getElementById('gst_percentage') || document.getElementById('gst-percentage');
    const gstPercentage = parseFloat(gstInput ? gstInput.value : 0) || 0;
    const gstAmt = subtotal * gstPercentage / 100;
    const finalTotal = subtotal + gstAmt;
    
    const displayTax = document.getElementById('display_tax') || document.getElementById('display-tax');
    if (displayTax) {
        displayTax.value = '₹' + gstAmt.toFixed(2);
    }
    
    const displayTotal = document.getElementById('display-total');
    if (displayTotal) {
        displayTotal.textContent = '₹' + finalTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    }
}


function updateVendorOutstanding() {
    const vendorSelect = document.querySelector('select[name="vendor_name"]');
    const outstandingInfo = document.getElementById('vendor-outstanding-info');
    const outstandingAmount = document.getElementById('vendor-outstanding-amount');
    
    if (!vendorSelect || !outstandingInfo || !outstandingAmount) return;
    
    const selectedOption = vendorSelect.options[vendorSelect.selectedIndex];
    if (!selectedOption || !selectedOption.value) {
        outstandingInfo.classList.add('hidden');
        outstandingInfo.classList.remove('flex');
        return;
    }
    
    const outstanding = parseFloat(selectedOption.getAttribute('data-outstanding')) || 0;
    
    if (outstanding > 0) {
        outstandingAmount.textContent = '₹' + outstanding.toLocaleString('en-IN', { minimumFractionDigits: 2 });
        outstandingInfo.classList.remove('hidden');
        outstandingInfo.classList.add('flex');
    } else {
        outstandingInfo.classList.add('hidden');
        outstandingInfo.classList.remove('flex');
    }
}

window.addEventListener('DOMContentLoaded', () => {
    const selector = document.querySelector('.item-selector');
    if (selector && selector.value) updateUnit(selector);
    
    updateVendorOutstanding();
});
</script>
@endpush

