@extends('layouts.app')
@section('title', 'Records for ' . \Carbon\Carbon::parse($date)->format('d M Y'))

@section('content')
<div class="animate-fade-in" x-data="{
    editId: 0,
    editFormAction: '',
    editVendorName: '',
    editInvoiceNo: '',
    editDate: '',
    editPaymentMode: 'Cash',
    editGstPercentage: 18,
    editItems: [],
}">
    <x-page-header title="{{ \Carbon\Carbon::parse($date)->format('d M Y') }}" subtitle="{{ \Carbon\Carbon::parse($date)->format('l') }}">
        <x-slot:actions>
            <x-button variant="outline" href="{{ route('purchases.invoices.export', ['date' => $date]) }}" icon="download">
                Export CSV
            </x-button>
            <x-button variant="outline" href="{{ route('purchases.invoices.print', $date) }}" icon="print" target="_blank">
                Print Invoice
            </x-button>
            <x-button variant="outline" href="{{ route('purchases.invoices.pdf', $date) }}" icon="picture_as_pdf">
                Download PDF
            </x-button>
            <x-button variant="outline" href="{{ route('purchases.invoices') }}" icon="arrow_back">
                Back to All Dates
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Day Stats --}}
    @php
        $batchFarmWt = (float)(isset($dayLoadBatches) ? $dayLoadBatches->sum('total_farm_weight') : ($dayLoadBatch?->total_farm_weight ?? $dayLoadEntries->sum('farm_weight') ?? 0));
        $batchBirdWt = (float)(isset($dayLoadBatches) ? $dayLoadBatches->sum('total_bird_weight') : ($dayLoadBatch?->total_bird_weight ?? $dayLoadEntries->sum('bird_weight') ?? 0));
        $batchLossWt = (float)(isset($dayLoadBatches) ? $dayLoadBatches->sum('total_loss_weight') : ($dayLoadBatch?->total_loss_weight ?? 0));
        if ($batchLossWt == 0 && $batchFarmWt > $batchBirdWt) {
            $batchLossWt = round($batchFarmWt - $batchBirdWt, 2);
        }
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-3 gap-4 mb-8">
        <x-stat-card
            label="Day-Load Batches"
            value="{{ $dayStats['dayload_count'] }}"
            icon="local_shipping"
            color="blue" />
        <x-stat-card
            label="Boxes Loaded"
            value="{{ number_format($dayStats['dayload_boxes']) }} boxes"
            icon="inventory_2"
            color="indigo" />
        <x-stat-card
            label="Net Bird Weight"
            value="{{ number_format($batchBirdWt, 2) }} kg"
            icon="scale"
            color="emerald" />
        @if($batchFarmWt > 0 || $batchLossWt > 0)
        <x-stat-card
            label="Farm Weight"
            value="{{ number_format($batchFarmWt, 2) }} kg"
            icon="agriculture"
            color="amber" />
        <x-stat-card
            label="Loss Weight"
            value="{{ number_format($batchLossWt, 2) }} kg"
            icon="trending_down"
            color="rose" />
        @endif
        <x-stat-card
            label="Day Total"
            value="Rs {{ number_format($dayStats['purchase_total'], 2) }}"
            icon="payments"
            color="emerald" />
    </div>

    {{-- Day-Load Entries Section --}}
    @if(isset($dayLoadEntries) && $dayLoadEntries->count() > 0)
    <x-card class="mb-8">
        <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-rounded text-blue-600 dark:text-blue-400">local_shipping</span>
                <h2 class="font-cabinet text-lg font-bold text-zinc-900 dark:text-zinc-50">Day-Load Entries</h2>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                    {{ $dayLoadEntries->count() }} entries
                </span>
                @if(isset($dayLoadBatches) && $dayLoadBatches->count() > 1)
                    <x-badge variant="zinc">{{ $dayLoadBatches->count() }} Batches</x-badge>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 border-b border-zinc-200/50 dark:border-zinc-800/50">
                        <th class="px-4 py-3 text-center" style="width: 50px;">#</th>
                        <th class="px-6 py-3 text-left">Batch / Vendor</th>
                        <th class="px-6 py-3 text-left">Dealer</th>
                        <th class="px-6 py-3 text-center">Boxes</th>
                        <th class="px-6 py-3 text-center">Box Wt</th>
                        <th class="px-6 py-3 text-center">Empty Wt</th>
                        <th class="px-6 py-3 text-center">Bird Wt</th>
                        <th class="px-6 py-3 text-center">Customer Rate</th>
                        <th class="px-6 py-3 text-center">Total Rate</th>
                        <th class="px-6 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($dayLoadEntries as $entry)
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-4 py-4 text-center font-mono text-xs">
                                @if($entry->sort_order)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-black bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300 dark:border-amber-700">#{{ $entry->sort_order }}</span>
                                @else
                                    <span class="text-zinc-400 dark:text-zinc-500 font-jetbrains">{{ $loop->iteration }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-zinc-900 dark:text-zinc-100 text-xs">{{ $entry->vendor->firm_name ?? '-' }}</span>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-500 font-jetbrains">#{{ $entry->batch_id }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-zinc-600 dark:text-zinc-400 text-xs font-semibold">{{ $entry->dealer->firm_name ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4 text-center font-jetbrains font-bold text-xs">{{ $entry->no_of_boxes }}</td>
                            <td class="px-6 py-4 text-center font-jetbrains text-xs">{{ number_format((float) ($entry->box_weight ?? 0), 2) }} kg</td>
                            <td class="px-6 py-4 text-center font-jetbrains text-xs">{{ number_format((float) ($entry->empty_weight ?? 0), 2) }} kg</td>
                            <td class="px-6 py-4 text-center font-jetbrains text-xs">{{ number_format((float) $entry->bird_weight, 2) }} kg</td>
                            <td class="px-6 py-4 text-center font-jetbrains text-xs">Rs {{ number_format((float) $entry->customer_rate, 2) }}</td>
                            <td class="px-6 py-4 text-center font-jetbrains font-bold text-xs text-emerald-600 dark:text-emerald-400">Rs {{ number_format((float) $entry->amount, 2) }}</td>
                            <td class="px-6 py-4 text-center">
                                <x-badge variant="success">{{ $entry->status }}</x-badge>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/30">
                    <tr class="font-bold text-xs">
                        <td class="px-6 py-3 text-zinc-500" colspan="3">Totals (All Batches)</td>
                        <td class="px-6 py-3 text-center font-jetbrains text-blue-600 dark:text-blue-400">{{ $dayLoadEntries->sum('no_of_boxes') }}</td>
                        <td class="px-6 py-3 text-center font-jetbrains">{{ number_format((float) $dayLoadEntries->sum('box_weight'), 2) }} kg</td>
                        <td class="px-6 py-3 text-center font-jetbrains">{{ number_format((float) $dayLoadEntries->sum('empty_weight'), 2) }} kg</td>
                        <td class="px-6 py-3 text-center font-jetbrains">{{ number_format((float) $dayLoadEntries->sum('bird_weight'), 2) }} kg</td>
                        <td class="px-6 py-3 text-center font-jetbrains">—</td>
                        <td class="px-6 py-3 text-center font-jetbrains text-emerald-600">Rs {{ number_format((float) $dayLoadEntries->sum('amount'), 2) }}</td>
                        <td></td>
                    </tr>
                    @php
                        $farmWt = (float)(isset($dayLoadBatches) ? $dayLoadBatches->sum('total_farm_weight') : ($dayLoadBatch?->total_farm_weight ?? $dayLoadEntries->sum('farm_weight') ?? 0));
                        $birdWt = (float)(isset($dayLoadBatches) ? $dayLoadBatches->sum('total_bird_weight') : ($dayLoadBatch?->total_bird_weight ?? $dayLoadEntries->sum('bird_weight') ?? 0));
                        $lossWt = (float)(isset($dayLoadBatches) ? $dayLoadBatches->sum('total_loss_weight') : ($dayLoadBatch?->total_loss_weight ?? 0));
                        if ($lossWt == 0 && $farmWt > $birdWt) {
                            $lossWt = round($farmWt - $birdWt, 2);
                        }
                        $lossPct = $farmWt > 0 ? round(($lossWt / $farmWt) * 100, 2) : 0;
                    @endphp
                    @if($farmWt > 0 || $lossWt > 0)
                    <tr class="font-bold text-xs border-t border-zinc-200 dark:border-zinc-700 bg-amber-50/40 dark:bg-amber-950/20">
                        <td class="px-6 py-3 text-amber-800 dark:text-amber-300 font-bold" colspan="3">
                            <span class="flex items-center gap-1.5 font-bold">
                                <span class="material-symbols-rounded text-base">agriculture</span> Farm & Weight Loss:
                            </span>
                        </td>
                        <td class="px-6 py-3 text-center font-jetbrains" colspan="2">
                            <span class="text-[10px] text-zinc-500 dark:text-zinc-400 block uppercase font-medium">Farm Weight</span>
                            <span class="font-mono font-black text-zinc-900 dark:text-zinc-100 text-sm">{{ number_format($farmWt, 2) }} kg</span>
                        </td>
                        <td class="px-6 py-3 text-center font-jetbrains" colspan="2">
                            <span class="text-[10px] text-rose-500 block uppercase font-medium">Loss Weight</span>
                            <span class="font-mono font-black text-rose-600 dark:text-rose-400 text-sm">- {{ number_format($lossWt, 2) }} kg @if($lossPct > 0)<span class="text-xs font-normal">({{ $lossPct }}%)</span>@endif</span>
                        </td>
                        <td class="px-6 py-3 text-center font-jetbrains text-emerald-700 dark:text-emerald-400" colspan="2">
                            <span class="text-[10px] text-emerald-600 block uppercase font-medium">Net Billed Weight</span>
                            <span class="font-mono font-black text-sm">{{ number_format($birdWt, 2) }} kg</span>
                        </td>
                    </tr>
                    @endif
                </tfoot>
            </table>
        </div>
    </x-card>
    @endif

    <!-- {{-- Purchases Section --}}
    <x-card>
        <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50">
            <div class="flex items-center gap-2">
                <span class="material-symbols-rounded text-emerald-600 dark:text-emerald-400">receipt_long</span>
                <h2 class="font-cabinet text-lg font-bold text-zinc-900 dark:text-zinc-50">Purchase Entries</h2>
                @if($purchases->count() > 0)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                    {{ $purchases->count() }} invoices
                </span>
                @endif
            </div>
        </div>

        @if($purchases->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 border-b border-zinc-200/50 dark:border-zinc-800/50">
                        <th class="px-6 py-3 text-left">Vendor</th>
                        <th class="px-6 py-3 text-left">Items</th>
                        <th class="px-6 py-3 text-left">Invoice No</th>
                        <th class="px-6 py-3 text-left">Payment</th>
                        <th class="px-6 py-3 text-right">GST</th>
                        <th class="px-6 py-3 text-right">Total</th>
                        <th class="px-6 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($purchases as $p)
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <x-avatar name="{{ $p->vendor_name }}" size="sm" />
                                    <div class="font-bold text-zinc-900 dark:text-zinc-100 text-xs">{{ $p->vendor_name }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $firstItem = $p->items->first();
                                    $othersCount = $p->items->count() - 1;
                                @endphp
                                @if($firstItem)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-semibold">
                                        {{ $firstItem->item_name }} ({{ number_format($firstItem->quantity) }} {{ $firstItem->unit }})
                                    </span>
                                    @if($othersCount > 0)
                                        <span class="text-[10px] text-zinc-500 ml-1">+{{ $othersCount }}</span>
                                    @endif
                                @else
                                    <span class="text-zinc-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs font-jetbrains text-zinc-500">{{ $p->invoice_no ?: '—' }}</td>
                            <td class="px-6 py-4 text-xs text-zinc-500">{{ $p->payment_mode }}</td>
                            <td class="px-6 py-4 text-right font-jetbrains text-xs text-zinc-500">Rs {{ number_format((float) $p->gst_amount, 2) }}</td>
                            <td class="px-6 py-4 text-right font-jetbrains font-bold text-xs text-zinc-900 dark:text-zinc-100">Rs {{ number_format((float) $p->total_amount, 2) }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('purchases.show', $p->id) }}" class="text-zinc-400 hover:text-blue-600 transition-colors" title="View">
                                        <span class="material-symbols-rounded text-lg">visibility</span>
                                    </a>
                                    <a href="{{ route('purchases.print', $p->id) }}" target="_blank" class="text-zinc-400 hover:text-emerald-600 transition-colors" title="Print Invoice">
                                        <span class="material-symbols-rounded text-lg">print</span>
                                    </a>
                                    @can('edit purchases')
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
                                        class="text-zinc-400 hover:text-emerald-600 transition-colors" title="Edit">
                                        <span class="material-symbols-rounded text-lg">edit</span>
                                    </button>
                                    @endcan
                                    @can('delete purchases')
                                    <form action="{{ route('purchases.destroy', $p->id) }}" method="POST" class="delete-form inline">
                                        @csrf @method('DELETE')
                                        <button type="button" onclick="confirmDelete(this)" class="text-zinc-400 hover:text-rose-600 transition-colors" title="Delete">
                                            <span class="material-symbols-rounded text-lg">delete</span>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center">
            <x-empty-state
                icon="receipt_long"
                title="No purchases on this date"
                description="No purchase invoices were recorded for this day." />
        </div>
        @endif
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
                        @foreach($vendors ?? [] as $vendor)
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
                <template x-for="(item, idx) in editItems" :key="idx">
                    <div class="flex gap-2 mb-2 items-start">
                        <input type="text" x-model="item.name" :name="'items[' + idx + '][name]'" required class="flex-1 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm">
                        <input type="number" step="0.01" x-model="item.qty" :name="'items[' + idx + '][qty]'" required placeholder="Qty" class="w-24 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-center font-jetbrains">
                        <input type="text" x-model="item.unit" :name="'items[' + idx + '][unit]'" class="w-16 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-center">
                        <input type="number" step="0.01" x-model="item.rate" :name="'items[' + idx + '][rate]'" required placeholder="Rate" class="w-24 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-center font-jetbrains">
                    </div>
                </template>
            </div>

            <x-slot:footer>
                <x-button type="button" variant="outline" x-on:click="$dispatch('close-modal', 'edit-purchase-modal')">Cancel</x-button>
                <x-button type="submit" form="edit-purchase-form" variant="primary" icon="save">Save Changes</x-button>
            </x-slot:footer>
        </form>
    </x-modal>
</div>
@endsection

@push('scripts')
<script>
    function confirmDelete(button) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete this purchase invoice and revert its stock movements!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0d9488',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            background: document.documentElement.dataset.theme === 'dark' ? '#111827' : '#ffffff',
            color: document.documentElement.dataset.theme === 'dark' ? '#f3f4f6' : '#0f172a'
        }).then((result) => {
            if (result.isConfirmed) {
                button.closest('.delete-form').submit();
            }
        });
    }
</script>
@endpush
