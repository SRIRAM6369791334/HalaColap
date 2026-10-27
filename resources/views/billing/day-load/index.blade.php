@extends('layouts.app')
@section('title', 'Daily Load Billing')

@push('styles')
<style>
    .swal2-container {
        z-index: 100000 !important;
    }
</style>
@endpush

@section('content')
<div class="animate-fade-in" x-data="dayLoadBillingData()">
    <x-page-header title="Daily Load Billing" subtitle="Track vendor loads, dealer rates, box weights, and paper-rate variance">
        <x-slot:actions>
            <x-button variant="outline" href="{{ route('billing.day-load.export', ['date' => $date]) }}" icon="download">
                Export CSV
            </x-button>
            <x-button variant="outline" href="{{ route('billing.day-load.invoice', $date) }}" icon="print" target="_blank">
                Print Invoice
            </x-button>
            <x-button variant="outline" href="{{ route('billing.day-load.pdf', $date) }}" icon="picture_as_pdf">
                Download PDF
            </x-button>
            <x-button variant="outline" href="{{ route('billing.day-load.vendor-rates', ['vendor_id' => '', 'date' => $date]) }}" icon="price_change">
                Set Vendor Rates
            </x-button>
            <x-button variant="outline" href="{{ route('billing.weekly.index') }}" icon="receipt_long">
                Weekly Billing
            </x-button>
            @can('create bills')
            <!-- <form id="delete-day-form-{{ \Carbon\Carbon::parse($date)->format('Ymd') }}" action="{{ route('billing.day-load.destroy-date', $date) }}" method="POST" class="inline-block">
                @csrf
                @method('DELETE')
                <x-button type="button" onclick="confirmDeleteDay('{{ \Carbon\Carbon::parse($date)->format('d M Y') }}', 'delete-day-form-{{ \Carbon\Carbon::parse($date)->format('Ymd') }}')" variant="outline" icon="delete_forever" class="!border-rose-200 !text-rose-600 hover:!bg-rose-50 hover:!border-rose-300 dark:!border-rose-900/50 dark:!text-rose-400 dark:hover:!bg-rose-950/30 ">
                    Delete Day's Data
                </x-button>
            </form> -->
            <script>
            function confirmDeleteDay(date, formId) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: `You are about to delete all entries for ${date}. If there are any associated Payments or Bills, they will ALSO be automatically deleted.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#71717a',
                    confirmButtonText: 'Yes, delete everything!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById(formId).submit();
                    }
                });
            }
            function confirmDeleteEntry(entryId) {
                Swal.fire({
                    title: 'Delete this entry?',
                    text: `This will permanently delete this Day Load Entry. Any Payments or Bills associated with this specific entry will ALSO be automatically deleted!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#71717a',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('delete-entry-form-' + entryId).submit();
                    }
                });
            }
            </script>
            <x-button variant="primary" icon="playlist_add" @click="openBulkLoadModal(false)">
                New Load Entry
            </x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    {{-- Collapsible Stats Panel --}}
    <x-card class="mb-6 transition-all duration-300 hover:shadow-md" x-data="{ showStats: false }">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v16h16M8 16v-4m5 4V8m5 8V5" /></svg>
                </div>
                <div>
                    <h2 class="text-sm font-extrabold text-zinc-800 dark:text-zinc-100 tracking-tight">Billing & Financial Summary</h2>
                    <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 mt-0.5 tracking-wide uppercase">Click to view day totals and margins</p>
                </div>
            </div>
            <button type="button" @click="showStats = !showStats" :aria-expanded="showStats.toString()" aria-controls="billing-summary" class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-700 shadow-sm hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">
                <svg class="h-4 w-4 transition-transform" :class="showStats && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" /></svg>
                <span x-text="showStats ? 'Hide Summary' : 'Show Summary'">Show Summary</span>
            </button>
        </div>

        <div id="billing-summary" x-show="showStats" x-cloak class="pt-6 mt-4 border-t border-zinc-100 dark:border-zinc-800/80">
            {{-- Combined Metrics Row --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <x-stat-card label="Billing Date" value="{{ \Carbon\Carbon::parse($date)->format('d M Y') }}" subtitle="{{ \Carbon\Carbon::parse($date)->format('l') }}" icon="calendar_today" color="blue" />
                <x-stat-card label="Total Boxes" value="{{ number_format((float) ($batches->sum('total_boxes') ?? 0), 0) }}" icon="inventory_2" color="amber" />
                <x-stat-card label="Bird Weight" value="{{ number_format((float) ($batches->sum('total_bird_weight') ?? 0), 2) }} kg" icon="scale" color="indigo" />
                <x-stat-card label="Gross Margin" value="Rs {{ number_format($grossMargin, 0) }}" icon="trending_up" color="{{ $grossMargin >= 0 ? 'emerald' : 'rose' }}" />
            </div>

            {{-- Dealer Panel (Full Width) --}}
            <div class="rounded-2xl border border-zinc-200/60 dark:border-zinc-800/60 bg-white/40 dark:bg-zinc-900/20 p-4 mb-6">
                <div class="flex items-center gap-2 mb-3 px-1">
                    <span class="material-symbols-rounded text-teal-500 text-[16px]">storefront</span>
                    <h3 class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Dealer Summary (Receivables)</h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-stat-card label="Dealer Income" value="Rs {{ number_format($totalDealerIncome, 0) }}" icon="payments" color="teal" />
                    <x-stat-card label="Dealer Collected" value="Rs {{ number_format($totalDealerCollected, 0) }}" subtitle="{{ $collectionPct }}% Collected" icon="account_balance" color="emerald" />
                    <x-stat-card label="Dealer Due" value="Rs {{ number_format($totalDealerDue, 0) }}" icon="pending" color="{{ $totalDealerDue > 0 ? 'amber' : 'emerald' }}" />
                </div>
            </div>

            {{-- Vendor Panel (Full Width) --}}
            <div class="rounded-2xl border border-zinc-200/60 dark:border-zinc-800/60 bg-white/40 dark:bg-zinc-900/20 p-4">
                <div class="flex items-center gap-2 mb-3 px-1">
                    <span class="material-symbols-rounded text-rose-500 text-[16px]">local_shipping</span>
                    <h3 class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Vendor Summary (Payables)</h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-stat-card label="Vendor Cost" value="Rs {{ number_format($totalVendorCost, 0) }}" icon="shopping_cart" color="rose" />
                    <x-stat-card label="Vendor Paid" value="Rs {{ number_format($totalVendorPaid, 0) }}" icon="payments" color="violet" />
                    <x-stat-card label="Vendor Due" value="Rs {{ number_format($totalVendorDue, 0) }}" icon="pending_actions" color="{{ $totalVendorDue > 0 ? 'amber' : 'emerald' }}" />
                </div>
            </div>
        </div>
    </x-card>

    @can('create bills')
    <x-card class="mb-8 overflow-hidden border-2 border-emerald-500/20 dark:border-emerald-500/10 shadow-lg shadow-emerald-500/5">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-zinc-200/80 dark:border-zinc-800 pb-5 mb-6">
            <div class="flex items-center gap-3.5">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h12M4 11h8M4 16h8m6-5v10m-5-5h10" /></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-cabinet text-lg font-bold text-zinc-900 dark:text-zinc-50 tracking-tight">New Load Entry</h2>
                        <span class="px-2 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 text-[10px] font-extrabold uppercase tracking-wider">Multi-Dealer</span>
                    </div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mt-0.5">Select a vendor to record box weights & rates across multiple dealers in one single batch</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <div class="inline-flex rounded-xl bg-zinc-100 dark:bg-zinc-800 p-1 text-xs font-semibold text-zinc-600 dark:text-zinc-300">
                    <button type="button" @click="entryMode = 'bulk'" :class="entryMode === 'bulk' ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100'" class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5 font-bold">
                        <span class="material-symbols-rounded text-sm">table_rows</span>
                        Multi-Dealer Sheet
                    </button>
                    <button type="button" @click="entryMode = 'single'" :class="entryMode === 'single' ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100'" class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5 font-bold">
                        <span class="material-symbols-rounded text-sm">playlist_add</span>
                        Single / Multi-Load Form
                    </button>
                </div>
            </div>
        </div>

        {{-- Multi-Dealer Quick Launcher --}}
        <div x-show="entryMode === 'bulk'">
            <div class="p-5 rounded-2xl bg-gradient-to-br from-zinc-50/80 via-emerald-50/30 to-teal-50/20 dark:from-zinc-900/60 dark:via-emerald-950/10 dark:to-teal-950/10 border border-zinc-200/60 dark:border-zinc-800/80 mb-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5 font-outfit">
                            Vendor / Company <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="bulkVendorId" :class="{'!border-rose-400 !ring-2 !ring-rose-400/25 bg-rose-50/20': bulkValidationAttempted && !bulkVendorId}" class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm px-3.5 py-2.5 font-semibold text-zinc-900 dark:text-zinc-100 shadow-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                            <option value="">-- Choose Vendor --</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->firm_name }}{{ $vendor->is_shop ? ' (Shop)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5 font-outfit">
                            Billing Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" x-model="bulkBillingDate" :class="{'!border-rose-400 !ring-2 !ring-rose-400/25 bg-rose-50/20': bulkValidationAttempted && !bulkBillingDate}" class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm px-3.5 py-2.5 font-semibold text-zinc-900 dark:text-zinc-100 shadow-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5 font-outfit">
                            Target Batch
                        </label>
                        <select x-model="bulkBatchId" class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm px-3.5 py-2.5 font-semibold text-zinc-900 dark:text-zinc-100 shadow-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                            <option value="">+ New Batch (Auto Create)</option>
                            @foreach($batches as $b)
                                <option value="{{ $b->id }}">Batch #{{ $loop->iteration }} ({{ $b->entries->first()?->vendor?->firm_name ?? 'Batch' }} - {{ $b->entries->count() }} entries)</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5 font-outfit">
                            Paper Rate (Rs) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.01" min="0" x-model.number="bulkPaperRate" placeholder="e.g. 120.00" :class="{'!border-rose-400 !ring-2 !ring-rose-400/25 bg-rose-50/20': bulkValidationAttempted && (!bulkPaperRate || parseFloat(bulkPaperRate) <= 0)}" class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm px-3.5 py-2.5 font-jetbrains font-bold text-zinc-900 dark:text-zinc-100 shadow-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5 font-outfit">
                            Vendor Rate Final (Rs)
                        </label>
                        <input type="number" step="0.01" min="0" x-model.number="bulkBillingRate" placeholder="Optional final rate" class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm px-3.5 py-2.5 font-jetbrains font-bold text-zinc-900 dark:text-zinc-100 shadow-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t border-zinc-200/60 dark:border-zinc-800/60 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-200 text-xs font-bold">
                            <span class="material-symbols-rounded text-sm">groups</span>
                            {{ $dealers->count() }} Registered Dealers Available
                        </span>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400 hidden sm:inline">Fill only the dealers who received birds; empty dealers are skipped automatically.</span>
                    </div>

                    <x-button type="button" variant="primary" size="md" icon="open_in_new" @click="openBulkLoadModal(true)" class="w-full sm:w-auto shadow-md shadow-emerald-500/20 font-bold px-6">
                        Open Multi-Dealer Entry Sheet
                    </x-button>
                </div>
            </div>
        </div>

        {{-- Single & Multi-Load Entry Form --}}
        <div x-show="entryMode === 'single'" x-cloak>
            <form action="{{ route('billing.day-load.bulk-store') }}" method="POST" @submit="submitSingleForm($event)" x-data="{ 
                paperRate: '', 
                billingRate: '', 
                selectedVendorId: '',
                selectedBatchId: '',
                billingDate: '{{ $date }}',
                advancesByVendor: {{ json_encode($activeAdvancesByVendor ?? []) }},
                existingRatesByVendor: {{ json_encode($existingRatesByVendor ?? []) }},
                dealerSortOrders: {{ json_encode($dealers->pluck('sort_order', 'id')->filter()->all()) }},
                cachedDealerRates: {},
                selectedAdvanceId: '',
                applyAdvanceAmount: '',
                loads: [
                    {
                        id: 1,
                        sort_order: 1,
                        dealer_id: '',
                        customer_rate: '',
                        no_of_boxes: '',
                        box_weight: '',
                        empty_weight: '',
                        remarks: ''
                    }
                ],
                fetchSingleRates() {
                    if (!this.selectedVendorId || !this.billingDate) {
                        this.paperRate = '';
                        this.billingRate = '';
                        this.cachedDealerRates = {};
                        return;
                    }
                    fetch(`{{ route('billing.day-load.get-rates') }}?vendor_id=${this.selectedVendorId}&date=${this.billingDate}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data && data.found) {
                                this.paperRate = data.paper_rate || '';
                                this.billingRate = data.billing_rate || '';
                                this.cachedDealerRates = data.dealer_rates || {};
                                this.loads.forEach(load => {
                                    if (load.dealer_id && this.cachedDealerRates[load.dealer_id] && !load.customer_rate) {
                                        load.customer_rate = this.cachedDealerRates[load.dealer_id];
                                    }
                                });
                            } else {
                                this.paperRate = '';
                                this.billingRate = '';
                                this.cachedDealerRates = {};
                            }
                        })
                        .catch(() => {
                            this.paperRate = '';
                            this.billingRate = '';
                            this.cachedDealerRates = {};
                        });
                },
                init() {
                    this.$watch('selectedVendorId', () => this.fetchSingleRates());
                    this.$watch('billingDate', () => this.fetchSingleRates());
                },
                onDealerSelect(load) {
                    if (load.dealer_id && this.cachedDealerRates && this.cachedDealerRates[load.dealer_id]) {
                        load.customer_rate = this.cachedDealerRates[load.dealer_id];
                    }
                    if (load.dealer_id && this.dealerSortOrders && this.dealerSortOrders[load.dealer_id]) {
                        load.sort_order = this.dealerSortOrders[load.dealer_id];
                    }
                },
                addLoad() {
                    let last = this.loads[this.loads.length - 1];
                    let nextSort = this.loads.length + 1;
                    let defDealer = last ? last.dealer_id : '';
                    let defRate = last ? last.customer_rate : (defDealer && this.cachedDealerRates[defDealer] ? this.cachedDealerRates[defDealer] : '');
                    this.loads.push({
                        id: Date.now() + Math.floor(Math.random() * 1000),
                        sort_order: nextSort,
                        dealer_id: defDealer,
                        customer_rate: defRate,
                        no_of_boxes: '',
                        box_weight: '',
                        empty_weight: '',
                        remarks: ''
                    });
                },
                removeLoad(index) {
                    if (this.loads.length > 1) {
                        this.loads.splice(index, 1);
                    }
                },
                get activeVendorRate() { 
                    return (parseFloat(this.billingRate) > 0) ? parseFloat(this.billingRate) : (parseFloat(this.paperRate) || 0); 
                },
                get dayOfWeek() {
                    if (!this.billingDate) return '';
                    const parts = this.billingDate.split('-');
                    if (parts.length === 3) {
                        const d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
                        return ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][d.getDay()] || '';
                    }
                    return '';
                },
                get availableAdvance() {
                    if (!this.selectedVendorId || !this.advancesByVendor[this.selectedVendorId]) return null;
                    return this.advancesByVendor[this.selectedVendorId];
                },
                getBirdWeight(load) {
                    let bw = parseFloat(load.box_weight) || 0;
                    let ew = parseFloat(load.empty_weight) || 0;
                    return Math.max(0, bw - ew);
                },
                getLoadAmount(load) {
                    let bw = this.getBirdWeight(load);
                    let cr = parseFloat(load.customer_rate) || 0;
                    return bw * cr;
                },
                get totalBoxes() {
                    return this.loads.reduce((sum, l) => sum + (parseInt(l.no_of_boxes) || 0), 0);
                },
                get totalBirdWeight() {
                    return this.loads.reduce((sum, l) => sum + this.getBirdWeight(l), 0);
                },
                get totalDealerAmount() {
                    return this.loads.reduce((sum, l) => sum + this.getLoadAmount(l), 0);
                },
                get estimatedVendorCost() {
                    return this.totalBirdWeight * this.activeVendorRate;
                },
                fillMaxAdvance() {
                    if (!this.availableAdvance) return;
                    let maxAdv = this.availableAdvance.total_remaining || 0;
                    let cost = this.estimatedVendorCost;
                    let fillAmount = (cost > 0 && cost < maxAdv) ? cost : maxAdv;
                    this.applyAdvanceAmount = fillAmount > 0 ? fillAmount.toFixed(2) : '';
                    if (this.availableAdvance.advances && this.availableAdvance.advances.length) {
                        this.selectedAdvanceId = this.availableAdvance.advances[0].id;
                    }
                },
                submitSingleForm(e) {
                    if (!this.selectedVendorId) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'warning', title: 'Vendor Required', text: 'Please select a Vendor / Company.', confirmButtonColor: '#059669' });
                        } else {
                            alert('Please select a Vendor / Company.');
                        }
                        e.preventDefault();
                        return false;
                    }
                    if (!this.paperRate || parseFloat(this.paperRate) <= 0) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'warning', title: 'Paper Rate Required', text: 'Please enter a valid Paper Rate (Rs).', confirmButtonColor: '#059669' });
                        } else {
                            alert('Please enter a valid Paper Rate (Rs).');
                        }
                        e.preventDefault();
                        return false;
                    }
                    for (let i = 0; i < this.loads.length; i++) {
                        let l = this.loads[i];
                        let num = i + 1;
                        if (!l.dealer_id) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'warning', title: `Load #${num} Incomplete`, text: `Please select a Dealer for Load #${num}.`, confirmButtonColor: '#059669' });
                            } else {
                                alert(`Load #${num}: Please select a Dealer.`);
                            }
                            e.preventDefault();
                            return false;
                        }
                        if (!l.customer_rate || parseFloat(l.customer_rate) <= 0) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'warning', title: `Load #${num} Incomplete`, text: `Please enter Customer Rate for Load #${num}.`, confirmButtonColor: '#059669' });
                            } else {
                                alert(`Load #${num}: Please enter Customer Rate.`);
                            }
                            e.preventDefault();
                            return false;
                        }
                        if (!l.no_of_boxes || parseInt(l.no_of_boxes) <= 0) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'warning', title: `Load #${num} Incomplete`, text: `Please enter Boxes (min 1) for Load #${num}.`, confirmButtonColor: '#059669' });
                            } else {
                                alert(`Load #${num}: Please enter Boxes.`);
                            }
                            e.preventDefault();
                            return false;
                        }
                        if (!l.box_weight || parseFloat(l.box_weight) <= 0) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'warning', title: `Load #${num} Incomplete`, text: `Please enter Box Weight for Load #${num}.`, confirmButtonColor: '#059669' });
                            } else {
                                alert(`Load #${num}: Please enter Box Weight.`);
                            }
                            e.preventDefault();
                            return false;
                        }
                        if (l.empty_weight === '' || l.empty_weight === null || isNaN(parseFloat(l.empty_weight))) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'warning', title: `Load #${num} Incomplete`, text: `Please enter Empty Weight for Load #${num}.`, confirmButtonColor: '#059669' });
                            } else {
                                alert(`Load #${num}: Please enter Empty Weight.`);
                            }
                            e.preventDefault();
                            return false;
                        }
                        if (parseFloat(l.empty_weight) >= parseFloat(l.box_weight)) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'warning', title: `Load #${num} Weight Error`, text: `Empty Weight must be less than Box Weight for Load #${num}.`, confirmButtonColor: '#059669' });
                            } else {
                                alert(`Load #${num}: Empty Weight must be less than Box Weight.`);
                            }
                            e.preventDefault();
                            return false;
                        }
                    }
                }
            }">
                @csrf
                {{-- Master Batch Parameters --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 mb-4 items-start">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5 font-outfit">
                            Vendor / Company <span class="text-rose-500">*</span>
                        </label>
                        <select name="vendor_id" required x-model="selectedVendorId" class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm px-3.5 py-2.5 font-semibold text-zinc-900 dark:text-zinc-100 shadow-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                            <option value="">Select vendor...</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->firm_name }}{{ $vendor->is_shop ? ' (Shop)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5 font-outfit">
                            Target Batch
                        </label>
                        <select name="batch_id" x-model="selectedBatchId" class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm px-3.5 py-2.5 font-semibold text-zinc-900 dark:text-zinc-100 shadow-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                            <option value="">+ New Batch (Auto Create)</option>
                            @foreach($batches as $b)
                                <option value="{{ $b->id }}">Batch #{{ $loop->iteration }} ({{ $b->entries->first()?->vendor?->firm_name ?? 'Batch' }} - {{ $b->entries->count() }} entries)</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5 font-outfit">
                            Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="billing_date" required x-model="billingDate" class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm px-3.5 py-2.5 font-semibold text-zinc-900 dark:text-zinc-100 shadow-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5 font-outfit">
                            Day
                        </label>
                        <input type="text" :value="dayOfWeek" readonly class="w-full rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-100/70 dark:bg-zinc-800/50 text-sm px-3.5 py-2.5 font-semibold text-zinc-600 dark:text-zinc-400 shadow-xs">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5 font-outfit">
                            Paper Rate (Rs) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.01" min="0" name="paper_rate" required x-model.number="paperRate" placeholder="e.g. 120.00" class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm px-3.5 py-2.5 font-jetbrains font-bold text-zinc-900 dark:text-zinc-100 shadow-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-5 items-end">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5 font-outfit">
                            Vendor Rate Final (Optional)
                        </label>
                        <input type="number" step="0.01" min="0" name="billing_rate" x-model.number="billingRate" placeholder="Optional final rate" class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm px-3.5 py-2.5 font-jetbrains font-bold text-zinc-900 dark:text-zinc-100 shadow-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                    </div>
                </div>

                {{-- Vendor Advance Alert & Selection Bar --}}
                <div x-show="availableAdvance && availableAdvance.total_remaining > 0" x-cloak class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 flex flex-wrap items-center justify-between gap-4 mb-5">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-rounded text-amber-600 text-2xl">local_shipping</span>
                        <div>
                            <p class="text-xs font-bold text-amber-900 dark:text-amber-200">
                                Vendor Advance Available: <span class="font-jetbrains text-sm">Rs <span x-text="availableAdvance ? availableAdvance.total_remaining.toFixed(2) : '0.00'"></span></span>
                            </p>
                            <p class="text-[11px] text-amber-700 dark:text-amber-400">You can optionally deduct from this advance deposit for this entry.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <div>
                            <select name="vendor_advance_id" x-model="selectedAdvanceId" class="rounded-xl border border-amber-300 dark:border-amber-700 bg-white dark:bg-zinc-900 text-xs px-3 py-2.5 font-semibold shadow-xs">
                                <option value="">-- Select Advance Deposit --</option>
                                <template x-for="adv in (availableAdvance ? availableAdvance.advances : [])" :key="adv.id">
                                    <option :value="adv.id" x-text="adv.date + ' (Bal: Rs ' + adv.remaining + ')'"></option>
                                </template>
                            </select>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <input type="number" step="0.01" min="0" name="apply_advance_amount" x-model="applyAdvanceAmount" placeholder="Amount to adjust" class="w-36 rounded-xl border border-amber-300 dark:border-amber-700 bg-white dark:bg-zinc-900 text-xs px-3 py-2.5 font-jetbrains font-bold shadow-xs">
                            <button type="button" @click="fillMaxAdvance()" class="text-[11px] uppercase font-bold text-amber-800 bg-amber-200/80 px-3 py-2.5 rounded-xl hover:bg-amber-300 transition-all shadow-xs" title="Auto-fill up to entry total cost">Max</button>
                        </div>
                    </div>
                </div>

                {{-- Dynamic Loads Repeater --}}
                <div class="mb-5">
                    <div class="flex items-center justify-between mb-3 px-1">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-rounded text-emerald-600 text-lg">local_shipping</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 font-outfit">Dealer Loads (<span x-text="loads.length"></span>)</h3>
                        </div>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">Add multiple loads for the same dealer or different dealers in this batch</span>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(load, index) in loads" :key="load.id">
                            <div class="p-4 rounded-2xl border border-zinc-200/90 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-900/40 shadow-xs transition-all">
                                <div class="flex flex-wrap items-center justify-between gap-2 pb-3 mb-3 border-b border-zinc-200/60 dark:border-zinc-800/60">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-jetbrains font-extrabold text-xs shadow-xs">
                                            #<span x-text="load.sort_order || (index + 1)"></span>
                                        </span>
                                        <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 font-outfit">
                                            Load Entry #<span x-text="index + 1"></span>
                                        </span>
                                        <template x-if="getBirdWeight(load) > 0">
                                            <span class="px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-[11px] font-bold font-jetbrains">
                                                Net: <span x-text="getBirdWeight(load).toFixed(2)"></span> kg
                                            </span>
                                        </template>
                                        <template x-if="getLoadAmount(load) > 0">
                                            <span class="px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 text-[11px] font-bold font-jetbrains">
                                                Rs <span x-text="Math.round(getLoadAmount(load)).toLocaleString('en-IN')"></span>
                                            </span>
                                        </template>
                                    </div>

                                    <button type="button" x-show="loads.length > 1" @click="removeLoad(index)" class="text-xs font-bold text-rose-500 hover:text-rose-700 dark:text-rose-400 flex items-center gap-1 hover:bg-rose-50 dark:hover:bg-rose-950/30 px-2.5 py-1 rounded-lg transition-colors" title="Remove this load">
                                        <span class="material-symbols-rounded text-base">delete</span>
                                        <span>Remove Load</span>
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3 items-end">
                                    {{-- Order # --}}
                                    <div class="col-span-1">
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1 font-outfit">Order #</label>
                                        <input type="number" min="1" step="1"
                                            :name="'dealers[' + index + '][sort_order]'"
                                            x-model.number="load.sort_order"
                                            class="w-full px-2 py-2 text-xs font-jetbrains font-extrabold text-center rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 shadow-2xs">
                                    </div>

                                    {{-- Dealer --}}
                                    <div class="col-span-1 sm:col-span-2">
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1 font-outfit">Dealer <span class="text-rose-500">*</span></label>
                                        <select :name="'dealers[' + index + '][dealer_id]'" x-model="load.dealer_id" @change="onDealerSelect(load)" required class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-xs px-3 py-2 font-semibold text-zinc-900 dark:text-zinc-100 shadow-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                                            <option value="">Select dealer...</option>
                                            @foreach($dealers as $dealer)
                                                <option value="{{ $dealer->id }}">{{ $dealer->firm_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Customer Rate --}}
                                    <div class="col-span-1">
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1 font-outfit">Cust Rate <span class="text-rose-500">*</span></label>
                                        <input type="number" step="0.01" min="0"
                                            :name="'dealers[' + index + '][customer_rate]'"
                                            x-model.number="load.customer_rate"
                                            placeholder="0.00"
                                            required
                                            class="w-full px-2.5 py-2 text-xs font-jetbrains font-bold rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 shadow-2xs">
                                    </div>

                                    {{-- Boxes --}}
                                    <div class="col-span-1">
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1 font-outfit">Boxes <span class="text-rose-500">*</span></label>
                                        <input type="number" min="1"
                                            :name="'dealers[' + index + '][no_of_boxes]'"
                                            x-model.number="load.no_of_boxes"
                                            placeholder="Boxes"
                                            required
                                            class="w-full px-2 py-2 text-xs font-jetbrains font-bold text-center rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 shadow-2xs">
                                    </div>

                                    {{-- Box Weight --}}
                                    <div class="col-span-1">
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1 font-outfit">Box Wt (kg) <span class="text-rose-500">*</span></label>
                                        <input type="number" step="0.01" min="0"
                                            :name="'dealers[' + index + '][box_weight]'"
                                            x-model.number="load.box_weight"
                                            placeholder="Gross"
                                            required
                                            class="w-full px-2 py-2 text-xs font-jetbrains font-medium text-center rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 shadow-2xs">
                                    </div>

                                    {{-- Empty Weight --}}
                                    <div class="col-span-1">
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1 font-outfit">Empty Wt (kg) <span class="text-rose-500">*</span></label>
                                        <input type="number" step="0.01" min="0"
                                            :name="'dealers[' + index + '][empty_weight]'"
                                            x-model.number="load.empty_weight"
                                            placeholder="Empty"
                                            required
                                            class="w-full px-2 py-2 text-xs font-jetbrains font-medium text-center rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 shadow-2xs">
                                    </div>

                                    {{-- Remarks --}}
                                    <div class="col-span-1">
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1 font-outfit">Remarks</label>
                                        <input type="text"
                                            :name="'dealers[' + index + '][remarks]'"
                                            x-model="load.remarks"
                                            placeholder="Optional"
                                            class="w-full px-2.5 py-2 text-xs rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 shadow-2xs">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Add More Load Button & Form Footer Actions --}}
                    <div class="mt-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 pt-4 border-t border-zinc-200/80 dark:border-zinc-800">
                        <button type="button" @click="addLoad()" class="px-4 py-2.5 rounded-xl border-2 border-dashed border-emerald-500/60 hover:border-emerald-600 bg-emerald-50/50 hover:bg-emerald-100/60 dark:bg-emerald-950/30 dark:hover:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 font-bold text-xs flex items-center justify-center gap-1.5 transition-all shadow-xs">
                            <span class="material-symbols-rounded text-base">add_circle</span>
                            <span>+ Add Another Load</span>
                        </button>

                        <div class="flex items-center justify-end gap-3">
                            <div x-show="totalBoxes > 0" class="text-right mr-2">
                                <span class="text-[10px] uppercase font-bold text-zinc-400 block">Total Summary</span>
                                <span class="text-xs font-jetbrains font-extrabold text-zinc-700 dark:text-zinc-300">
                                    <span x-text="totalBoxes"></span> bxs • <span x-text="totalBirdWeight.toFixed(2)"></span> kg • Rs <span x-text="Math.round(totalDealerAmount).toLocaleString('en-IN')"></span>
                                </span>
                            </div>

                            <x-button type="submit" variant="primary" icon="save" class="font-bold px-6 shadow-md shadow-emerald-500/20">
                                Save <span x-text="loads.length > 1 ? loads.length + ' Loads' : 'Load Entry'"></span>
                            </x-button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </x-card>
    @endcan

    {{-- Search & Date Filter Bar --}}
    <div class="mb-6 flex flex-col sm:flex-row items-center justify-between gap-4 p-4 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-sm">
        <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                <span class="material-symbols-rounded text-lg">local_shipping</span>
            </div>
            <div>
                <h3 class="font-cabinet text-base font-bold text-zinc-900 dark:text-zinc-50">Batches & Load Entries</h3>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Manage individual truck loads and dealer entries</p>
            </div>
            <x-badge variant="zinc">{{ $batches->count() }} {{ \Illuminate\Support\Str::plural('Batch', $batches->count()) }}</x-badge>
        </div>
        <form method="GET" class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <input type="date" name="date" value="{{ $date }}" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search vendor or dealer" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
            <x-button type="submit" variant="outline" icon="filter_alt">Filter</x-button>
        </form>
    </div>

    {{-- Batches List --}}
    @forelse($batches as $batch)
        @php
            $batchEntries = $normalEntries->where('batch_id', $batch->id);
            $batchVendorName = $batchEntries->first()?->vendor?->firm_name ?? $batch->entries->first()?->vendor?->firm_name ?? 'Batch #' . $batch->id;
        @endphp
        <x-card class="mb-8 overflow-hidden border border-zinc-200/80 dark:border-zinc-800 shadow-sm">
            <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50 flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-zinc-50/50 dark:bg-zinc-800/20">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-black text-sm font-jetbrains shadow-sm">
                        #{{ $loop->iteration }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-cabinet text-lg font-bold text-zinc-900 dark:text-zinc-50">{{ $batchVendorName }}</h2>
                            <span class="px-2 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 text-[10px] font-bold">Batch #{{ $batch->id }}</span>
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $batchEntries->count() }} dealers loaded</p>
                    </div>
                    @can('create bills')
                        @if($batchEntries->count() > 0)
                            <x-button
                                variant="primary"
                                size="sm"
                                icon="scale"
                                x-on:click="$dispatch('open-modal', 'set-farm-weight-modal-{{ $batch->id }}')"
                                class="!bg-emerald-600 hover:!bg-emerald-700 ml-2"
                            >
                                Set Farm Weight
                            </x-button>
                        @endif
                    @endcan
                </div>

                {{-- Batch Summary Info Badges --}}
                <div class="flex flex-wrap items-center gap-3 text-xs font-medium text-zinc-600 dark:text-zinc-400 bg-white dark:bg-zinc-900 p-2.5 rounded-xl border border-zinc-200/60 dark:border-zinc-800">
                    <div class="flex items-center gap-1.5" title="Total Boxes">
                        <span class="material-symbols-rounded text-amber-500 text-[16px]">inventory_2</span>
                        <span class="font-jetbrains font-bold">{{ number_format($batch->total_boxes, 0) }} Boxes</span>
                    </div>
                    <div class="w-px h-4 bg-zinc-200 dark:bg-zinc-700"></div>
                    <div class="flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400" title="Shop Bird Weight">
                        <span class="material-symbols-rounded text-[16px]">monitor_weight</span>
                        <span class="font-jetbrains font-bold">{{ number_format($batch->total_bird_weight, 2) }} kg</span>
                    </div>
                    <div class="w-px h-4 bg-zinc-200 dark:bg-zinc-700"></div>
                    <div class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400" title="Farm Weight">
                        <span class="material-symbols-rounded text-[16px]">scale</span>
                        <span class="font-jetbrains font-bold">{{ $batch->total_farm_weight > 0 ? number_format($batch->total_farm_weight, 2) . ' kg' : 'Farm Wt: Not Set' }}</span>
                    </div>
                    @if($batch->total_farm_weight > 0)
                        <div class="w-px h-4 bg-zinc-200 dark:bg-zinc-700"></div>
                        <div class="flex items-center gap-1.5 {{ $batch->weight_loss > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}" title="Weight Loss">
                            <span class="material-symbols-rounded text-[16px]">trending_down</span>
                            <span class="font-jetbrains font-bold">{{ number_format($batch->weight_loss, 2) }} kg ({{ number_format($batch->weight_loss_percentage, 1) }}%)</span>
                        </div>
                    @endif
                </div>
            </div>

            <x-data-table :headers="['Order #', 'Date', 'Vendor', 'Dealer', 'Rates', 'Margin', 'Boxes', 'Weights', 'Amount', 'Dealer Payment', 'Vendor Payment', 'Status', 'Actions']">
                @forelse($batchEntries as $entry)
                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                    <td class="px-4 py-4 text-center font-jetbrains">
                        @if($entry->sort_order)
                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-md text-xs font-black font-jetbrains bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-500/20 shadow-2xs">
                                #{{ $entry->sort_order }}
                            </span>
                        @else
                            <span class="text-xs text-zinc-400 font-mono">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $entry->batch->billing_date->format('d M Y') }}</p>
                        <p class="text-xs text-zinc-500">{{ $entry->batch->billing_date->format('l') }}</p>
                    </td>
                    <td class="px-6 py-4 font-bold text-zinc-900 dark:text-zinc-100">{{ $entry->vendor->firm_name ?? '-' }}</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            @if($entry->sort_order)
                                <span class="inline-flex items-center justify-center min-w-[24px] px-1.5 py-0.5 rounded-md text-[10px] font-black font-jetbrains bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-500/20 shadow-2xs" title="Order #{{ $entry->sort_order }}">
                                    #{{ $entry->sort_order }}
                                </span>
                            @endif
                            <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ $entry->dealer->firm_name ?? '-' }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-xs">
                        <div>Paper: <span class="font-jetbrains">Rs {{ number_format((float) $entry->paper_rate, 2) }}</span></div>
                        <div>Vendor: <span class="font-jetbrains">@if((float) $entry->billing_rate > 0)Rs {{ number_format((float) $entry->billing_rate, 2) }}@else<span class="text-zinc-400">—</span>@endif</span></div>
                        <div>Customer: <span class="font-jetbrains">Rs {{ number_format((float) $entry->customer_rate, 2) }}</span></div>
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $diff = $entry->rate_difference;
                        @endphp
                        <span class="font-jetbrains font-bold {{ $diff >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $diff >= 0 ? '+' : '-' }}Rs {{ number_format(abs($diff), 2) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center font-jetbrains font-bold">{{ $entry->no_of_boxes }}</td>
                    <td class="px-6 py-4 text-xs space-y-0.5">
                        <div class="text-zinc-500">Box: <span class="font-jetbrains font-medium text-zinc-700 dark:text-zinc-300">{{ number_format((float) $entry->box_weight, 2) }}</span></div>
                        <div class="text-zinc-500">Empty: <span class="font-jetbrains font-medium text-zinc-700 dark:text-zinc-300">{{ number_format((float) $entry->empty_weight, 2) }}</span></div>
                        <div class="font-bold text-zinc-900 dark:text-zinc-100">Bird: <span class="font-jetbrains text-indigo-600 dark:text-indigo-400">{{ number_format((float) $entry->bird_weight, 2) }}</span></div>
                        {{-- @if($entry->effective_farm_weight !== null)
                            <div class="text-emerald-700 dark:text-emerald-400 font-bold">Farm: <span class="font-jetbrains">{{ number_format((float) $entry->effective_farm_weight, 2) }}</span></div>
                        @endif
                        @if($entry->loss_weight !== null && (float)$entry->loss_weight != 0)
                            <div class="text-amber-600 font-bold">Loss: <span class="font-jetbrains">{{ number_format((float) $entry->loss_weight, 2) }}</span></div>
                        @endif --}}
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-jetbrains font-bold text-zinc-900 dark:text-zinc-100">Rs {{ number_format((float) $entry->amount, 0) }}</span>
                    </td>
                    <td class="px-6 py-4 text-xs">
                        @php
                            $dStatus = $entry->dealer_payment_status;
                            $dColor = match($dStatus) { 'Paid' => 'success', 'Partial' => 'warning', 'Overpaid' => 'info', default => 'zinc' };
                        @endphp
                        <div class="flex flex-col items-center gap-1">
                            <x-badge :variant="$dColor">{{ $dStatus }}</x-badge>
                            <span class="font-jetbrains text-[11px] {{ (float) $entry->dealer_collected > 0 ? 'text-emerald-600' : 'text-zinc-400' }}">
                                Rs {{ number_format((float) $entry->dealer_collected, 0) }} / Rs {{ number_format($entry->dealer_income, 0) }}
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-xs">
                        @if($entry->effective_farm_weight !== null)
                            @php
                                $vStatus = $entry->vendor_payment_status;
                                $vColor = match($vStatus) { 'Paid' => 'success', 'Partial' => 'warning', 'Overpaid' => 'info', default => 'zinc' };
                            @endphp
                            <div class="flex flex-col items-center gap-1">
                                <x-badge :variant="$vColor">{{ $vStatus }}</x-badge>
                                <span class="font-jetbrains text-[11px] {{ (float) $entry->vendor_paid > 0 ? 'text-violet-600' : 'text-zinc-400' }}">
                                    Rs {{ number_format((float) $entry->vendor_paid, 0) }} / Rs {{ number_format($entry->vendor_cost, 0) }}
                                </span>
                            </div>
                        @else
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-[10px] text-amber-600 dark:text-amber-400 font-medium flex items-center gap-0.5">
                                    <span class="material-symbols-rounded text-[14px]">info</span>
                                    Enter FW for price
                                </span>
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <x-badge variant="success">{{ $entry->status }}</x-badge>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($entry->status === 'Active' || $entry->status === 'Adjusted')
                            <div class="flex items-center justify-center gap-2">
                                <button
                                    type="button"
                                    x-on:click="
                                        $dispatch('open-modal', 'edit-entry-modal');
                                        $nextTick(() => {
                                            editEntryId = {{ $entry->id }};
                                            editSortOrder = '{{ $entry->sort_order ?? '' }}';
                                            editFormAction = '{{ route('billing.day-load.update', $entry->id) }}';
                                            editVendorId = {{ $entry->vendor_id }};
                                            editDealerId = {{ $entry->dealer_id }};
                                            editPaperRate = {{ $entry->paper_rate }};
                                            editBillingRate = {{ $entry->billing_rate }};
                                            editCustomerRate = {{ $entry->customer_rate }};
                                            editNoOfBoxes = {{ $entry->no_of_boxes }};
                                            editBoxWeight = {{ $entry->box_weight }};
                                            editEmptyWeight = {{ $entry->empty_weight }};
                                            editFarmWeight = '{{ $entry->farm_weight ?? '' }}';
                                            editRemarks = '{{ $entry->remarks ?? '' }}';
                                        });
                                    "
                                    class="inline-flex items-center gap-1 text-xs font-medium text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300 transition-colors"
                                >
                                    <span class="material-symbols-rounded text-sm">edit</span>
                                </button>
                                @if($entry->bird_weight > 0)
                                <button
                                    type="button"
                                    x-on:click="
                                        $dispatch('open-modal', 'transfer-boxes-modal');
                                        $nextTick(() => {
                                            transferSourceId = {{ $entry->id }};
                                            transferSourceBoxes = {{ $entry->no_of_boxes }};
                                            transferSourceWeight = {{ $entry->bird_weight }};
                                            transferSourceVendor = '{{ addslashes($entry->vendor->firm_name ?? '-') }}';
                                            transferSourceDealer = '{{ addslashes($entry->dealer->firm_name ?? '-') }}';
                                            transferSourceVendorId = {{ $entry->vendor_id }};
                                            transferTargetVendorId = {{ $entry->vendor_id }};
                                            transferSourceCustomerRate = {{ $entry->customer_rate }};
                                            transferTargetCustomerRate = {{ $entry->customer_rate }};
                                            transferBatchId = {{ $entry->batch_id }};
                                            transferDate = '{{ $entry->batch->billing_date->format('d M Y') }}';
                                            transferMaxWeight = {{ $entry->bird_weight }};
                                            transferWeight = {{ $entry->bird_weight }};
                                            transferFormAction = '{{ route('billing.day-load.transfer', $entry->id) }}';
                                        });
                                    "
                                    class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                                >
                                    <span class="material-symbols-rounded text-sm">swap_horiz</span>
                                </button>
                                @endif
                                <button
                                    type="button"
                                    x-on:click="
                                        $dispatch('open-modal', 'dealer-payment-modal');
                                        $nextTick(() => {
                                            dpEntryId = {{ $entry->id }};
                                            dpFormAction = '{{ route('billing.day-load.dealer-payment', $entry->id) }}';
                                            dpEntryVendor = '{{ addslashes($entry->vendor->firm_name ?? '-') }}';
                                            dpEntryDealer = '{{ addslashes($entry->dealer->firm_name ?? '-') }}';
                                            dpEntryIncome = {{ $entry->dealer_income }};
                                            dpEntryCollected = {{ (float) $entry->dealer_collected }};
                                            dpCashAmount = {{ round($entry->dealer_income - (float) $entry->dealer_collected, 2) }};
                                            dpBankAmount = 0;
                                            dpBankTransferType = '';
                                        });
                                    "
                                    class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors"
                                    title="Record Dealer Payment"
                                >
                                    <span class="material-symbols-rounded text-sm">payments</span>
                                </button>
                                @if($entry->effective_farm_weight !== null)
                                <button
                                    type="button"
                                    x-on:click="
                                        $dispatch('open-modal', 'vendor-payment-modal');
                                        $nextTick(() => {
                                            vpEntryId = {{ $entry->id }};
                                            vpFormAction = '{{ route('billing.day-load.vendor-payment', $entry->id) }}';
                                            vpEntryVendor = '{{ addslashes($entry->vendor->firm_name ?? '-') }}';
                                            vpEntryDealer = '{{ addslashes($entry->dealer->firm_name ?? '-') }}';
                                            vpEntryCost = {{ $entry->vendor_cost }};
                                            vpEntryPaid = {{ (float) $entry->vendor_paid }};
                                            vpCashAmount = {{ round($entry->vendor_cost - (float) $entry->vendor_paid, 2) }};
                                            vpBankAmount = 0;
                                            vpBankTransferType = '';
                                        });
                                    "
                                    class="inline-flex items-center gap-1 text-xs font-medium text-violet-600 hover:text-violet-800 dark:text-violet-400 dark:hover:text-violet-300 transition-colors"
                                    title="Record Vendor Payment"
                                >
                                    <span class="material-symbols-rounded text-sm">account_balance_wallet</span>
                                </button>
                                @endif
                                <form id="delete-entry-form-{{ $entry->id }}" action="{{ route('billing.day-load.destroy-entry', $entry->id) }}" method="POST" class="inline-block ml-1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="confirmDeleteEntry({{ $entry->id }})" class="inline-flex items-center gap-1 text-xs font-medium text-rose-500 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 transition-colors" title="Delete Entry">
                                        <span class="material-symbols-rounded text-sm">delete</span>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </td>
                </tr>
                @empty
                    <x-slot:empty>
                        <x-empty-state icon="inventory_2" title="No load entries found in this batch" description="All entries in this batch were transferred or cancelled." />
                    </x-slot:empty>
                @endforelse
            </x-data-table>
        </x-card>
    @empty
        <x-card class="mb-8 text-center py-12">
            <div class="flex flex-col items-center justify-center gap-3">
                <span class="material-symbols-rounded text-4xl text-zinc-300 dark:text-zinc-600">inventory_2</span>
                <h3 class="text-base font-bold text-zinc-700 dark:text-zinc-300">No load batches found</h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Record the first vendor-to-dealer load batch for this date.</p>
                <x-button variant="primary" icon="playlist_add" @click="openBulkLoadModal(false)" class="mt-2">
                    New Load Entry
                </x-button>
            </div>
        </x-card>
    @endforelse

    {{-- Transferred Weight Entries Table (Downside Table) --}}
    <x-card class="mt-8">
        <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
                    <span class="material-symbols-rounded text-lg">swap_horiz</span>
                </div>
                <div>
                    <h2 class="font-cabinet text-lg font-bold text-zinc-900 dark:text-zinc-50">Transferred Weight Entries</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Entries modified or created via Weight Transfer action</p>
                </div>
                <x-badge variant="info">{{ $transferredEntries->count() }} entries</x-badge>
            </div>
        </div>

        <x-data-table :headers="['Order #', 'Date', 'Vendor', 'Dealer', 'Transfer Info', 'Rates', 'Margin', 'Boxes', 'Weights', 'Amount', 'Dealer Payment', 'Vendor Payment', 'Status', 'Actions']">
            @forelse($transferredEntries as $entry)
                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors bg-blue-50/5 dark:bg-blue-950/10">
                    <td class="px-4 py-4 text-center font-jetbrains">
                        @if($entry->sort_order)
                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-md text-xs font-black font-jetbrains bg-blue-100 dark:bg-blue-950/80 text-blue-800 dark:text-blue-300 border border-blue-500/20 shadow-2xs">
                                #{{ $entry->sort_order }}
                            </span>
                        @else
                            <span class="text-xs text-zinc-400 font-mono">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $entry->batch->billing_date->format('d M Y') }}</p>
                        <p class="text-xs text-zinc-500">{{ $entry->batch->billing_date->format('l') }}</p>
                    </td>
                    <td class="px-6 py-4 font-bold text-zinc-900 dark:text-zinc-100">{{ $entry->vendor->firm_name ?? '-' }}</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            @if($entry->sort_order)
                                <span class="inline-flex items-center justify-center min-w-[24px] px-1.5 py-0.5 rounded-md text-[10px] font-black font-jetbrains bg-blue-100 dark:bg-blue-950/80 text-blue-800 dark:text-blue-300 border border-blue-500/20 shadow-2xs" title="Order #{{ $entry->sort_order }}">
                                    #{{ $entry->sort_order }}
                                </span>
                            @endif
                            <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ $entry->dealer->firm_name ?? '-' }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-xs">
                        @if($entry->parent_entry_id)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 font-semibold text-[11px]">
                                <span class="material-symbols-rounded text-xs">arrow_downward</span>
                                Target (From Entry #{{ $entry->parent_entry_id }})
                            </span>
                            @if($entry->parentEntry && $entry->parentEntry->dealer)
                                <p class="mt-1 text-[10px] text-zinc-500">Source Dealer: {{ $entry->parentEntry->dealer->firm_name }}</p>
                            @endif
                        @elseif($entry->status === 'Adjusted')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 font-semibold text-[11px]">
                                <span class="material-symbols-rounded text-xs">tune</span>
                                Source Entry (Weight Transferred Out)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 font-semibold text-[11px]">
                                <span class="material-symbols-rounded text-xs">swap_horiz</span>
                                Transferred
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-xs">
                        <div>Paper: <span class="font-jetbrains">Rs {{ number_format((float) $entry->paper_rate, 2) }}</span></div>
                        <div>Vendor: <span class="font-jetbrains">@if((float) $entry->billing_rate > 0)Rs {{ number_format((float) $entry->billing_rate, 2) }}@else<span class="text-zinc-400">—</span>@endif</span></div>
                        <div>Customer: <span class="font-jetbrains">Rs {{ number_format((float) $entry->customer_rate, 2) }}</span></div>
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $diff = $entry->rate_difference;
                        @endphp
                        <span class="font-jetbrains font-bold {{ $diff >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $diff >= 0 ? '+' : '-' }}Rs {{ number_format(abs($diff), 2) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center font-jetbrains font-bold">{{ $entry->no_of_boxes }}</td>
                    <td class="px-6 py-4 text-xs space-y-0.5">
                        <div class="text-zinc-500">Box: <span class="font-jetbrains font-medium text-zinc-700 dark:text-zinc-300">{{ number_format((float) $entry->box_weight, 2) }}</span></div>
                        <div class="text-zinc-500">Empty: <span class="font-jetbrains font-medium text-zinc-700 dark:text-zinc-300">{{ number_format((float) $entry->empty_weight, 2) }}</span></div>
                        <div class="font-bold text-zinc-900 dark:text-zinc-100">Bird: <span class="font-jetbrains text-indigo-600 dark:text-indigo-400">{{ number_format((float) $entry->bird_weight, 2) }}</span></div>
                        {{-- @if($entry->effective_farm_weight !== null)
                            <div class="text-emerald-700 dark:text-emerald-400 font-bold">Farm: <span class="font-jetbrains">{{ number_format((float) $entry->effective_farm_weight, 2) }}</span></div>
                        @endif
                        @if($entry->loss_weight !== null && (float)$entry->loss_weight != 0)
                            <div class="text-amber-600 font-bold">Loss: <span class="font-jetbrains">{{ number_format((float) $entry->loss_weight, 2) }}</span></div>
                        @endif --}}
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-jetbrains font-bold text-zinc-900 dark:text-zinc-100">Rs {{ number_format((float) $entry->amount, 0) }}</span>
                    </td>
                    <td class="px-6 py-4 text-xs">
                        @php
                            $dStatus = $entry->dealer_payment_status;
                            $dColor = match($dStatus) { 'Paid' => 'success', 'Partial' => 'warning', 'Overpaid' => 'info', default => 'zinc' };
                        @endphp
                        <div class="flex flex-col items-center gap-1">
                            <x-badge :variant="$dColor">{{ $dStatus }}</x-badge>
                            <span class="font-jetbrains text-[11px] {{ (float) $entry->dealer_collected > 0 ? 'text-emerald-600' : 'text-zinc-400' }}">
                                Rs {{ number_format((float) $entry->dealer_collected, 0) }} / Rs {{ number_format($entry->dealer_income, 0) }}
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-xs">
                        @if($entry->effective_farm_weight !== null)
                            @php
                                $vStatus = $entry->vendor_payment_status;
                                $vColor = match($vStatus) { 'Paid' => 'success', 'Partial' => 'warning', 'Overpaid' => 'info', default => 'zinc' };
                            @endphp
                            <div class="flex flex-col items-center gap-1">
                                <x-badge :variant="$vColor">{{ $vStatus }}</x-badge>
                                <span class="font-jetbrains text-[11px] {{ (float) $entry->vendor_paid > 0 ? 'text-violet-600' : 'text-zinc-400' }}">
                                    Rs {{ number_format((float) $entry->vendor_paid, 0) }} / Rs {{ number_format($entry->vendor_cost, 0) }}
                                </span>
                            </div>
                        @else
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-[10px] text-amber-600 dark:text-amber-400 font-medium flex items-center gap-0.5">
                                    <span class="material-symbols-rounded text-[14px]">info</span>
                                    Enter FW for price
                                </span>
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <x-badge variant="{{ $entry->status === 'Adjusted' ? 'warning' : 'info' }}">{{ $entry->status }}</x-badge>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($entry->status === 'Active' || $entry->status === 'Adjusted')
                            <div class="flex items-center justify-center gap-2">
                                <button
                                    type="button"
                                    x-on:click="
                                        $dispatch('open-modal', 'edit-entry-modal');
                                        $nextTick(() => {
                                            editEntryId = {{ $entry->id }};
                                            editSortOrder = '{{ $entry->sort_order ?? '' }}';
                                            editFormAction = '{{ route('billing.day-load.update', $entry->id) }}';
                                            editVendorId = {{ $entry->vendor_id }};
                                            editDealerId = {{ $entry->dealer_id }};
                                            editPaperRate = {{ $entry->paper_rate }};
                                            editBillingRate = {{ $entry->billing_rate }};
                                            editCustomerRate = {{ $entry->customer_rate }};
                                            editNoOfBoxes = {{ $entry->no_of_boxes }};
                                            editBoxWeight = {{ $entry->box_weight }};
                                            editEmptyWeight = {{ $entry->empty_weight }};
                                            editFarmWeight = '{{ $entry->farm_weight ?? '' }}';
                                            editRemarks = '{{ $entry->remarks ?? '' }}';
                                        });
                                    "
                                    class="inline-flex items-center gap-1 text-xs font-medium text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300 transition-colors"
                                >
                                    <span class="material-symbols-rounded text-sm">edit</span>
                                </button>
                                @if($entry->bird_weight > 0)
                                <button
                                    type="button"
                                    x-on:click="
                                        $dispatch('open-modal', 'transfer-boxes-modal');
                                        $nextTick(() => {
                                            transferSourceId = {{ $entry->id }};
                                            transferSourceBoxes = {{ $entry->no_of_boxes }};
                                            transferSourceWeight = {{ $entry->bird_weight }};
                                            transferSourceVendor = '{{ addslashes($entry->vendor->firm_name ?? '-') }}';
                                            transferSourceDealer = '{{ addslashes($entry->dealer->firm_name ?? '-') }}';
                                            transferSourceVendorId = {{ $entry->vendor_id }};
                                            transferTargetVendorId = {{ $entry->vendor_id }};
                                            transferSourceCustomerRate = {{ $entry->customer_rate }};
                                            transferTargetCustomerRate = {{ $entry->customer_rate }};
                                            transferBatchId = {{ $entry->batch_id }};
                                            transferDate = '{{ $entry->batch->billing_date->format('d M Y') }}';
                                            transferMaxWeight = {{ $entry->bird_weight }};
                                            transferWeight = {{ $entry->bird_weight }};
                                            transferFormAction = '{{ route('billing.day-load.transfer', $entry->id) }}';
                                        });
                                    "
                                    class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                                >
                                    <span class="material-symbols-rounded text-sm">swap_horiz</span>
                                </button>
                                @endif
                                <button
                                    type="button"
                                    x-on:click="
                                        $dispatch('open-modal', 'dealer-payment-modal');
                                        $nextTick(() => {
                                            dpEntryId = {{ $entry->id }};
                                            dpFormAction = '{{ route('billing.day-load.dealer-payment', $entry->id) }}';
                                            dpEntryVendor = '{{ addslashes($entry->vendor->firm_name ?? '-') }}';
                                            dpEntryDealer = '{{ addslashes($entry->dealer->firm_name ?? '-') }}';
                                            dpEntryIncome = {{ $entry->dealer_income }};
                                            dpEntryCollected = {{ (float) $entry->dealer_collected }};
                                            dpCashAmount = {{ round($entry->dealer_income - (float) $entry->dealer_collected, 2) }};
                                            dpBankAmount = 0;
                                            dpBankTransferType = '';
                                        });
                                    "
                                    class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors"
                                    title="Record Dealer Payment"
                                >
                                    <span class="material-symbols-rounded text-sm">payments</span>
                                </button>
                                @if($entry->effective_farm_weight !== null)
                                <button
                                    type="button"
                                    x-on:click="
                                        $dispatch('open-modal', 'vendor-payment-modal');
                                        $nextTick(() => {
                                            vpEntryId = {{ $entry->id }};
                                            vpFormAction = '{{ route('billing.day-load.vendor-payment', $entry->id) }}';
                                            vpEntryVendor = '{{ addslashes($entry->vendor->firm_name ?? '-') }}';
                                            vpEntryDealer = '{{ addslashes($entry->dealer->firm_name ?? '-') }}';
                                            vpEntryCost = {{ $entry->vendor_cost }};
                                            vpEntryPaid = {{ (float) $entry->vendor_paid }};
                                            vpCashAmount = {{ round($entry->vendor_cost - (float) $entry->vendor_paid, 2) }};
                                            vpBankAmount = 0;
                                            vpBankTransferType = '';
                                        });
                                    "
                                    class="inline-flex items-center gap-1 text-xs font-medium text-violet-600 hover:text-violet-800 dark:text-violet-400 dark:hover:text-violet-300 transition-colors"
                                    title="Record Vendor Payment"
                                >
                                    <span class="material-symbols-rounded text-sm">account_balance_wallet</span>
                                </button>
                                @endif
                                <form id="delete-entry-form-{{ $entry->id }}" action="{{ route('billing.day-load.destroy-entry', $entry->id) }}" method="POST" class="inline-block ml-1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="confirmDeleteEntry({{ $entry->id }})" class="inline-flex items-center gap-1 text-xs font-medium text-rose-500 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 transition-colors" title="Delete Entry">
                                        <span class="material-symbols-rounded text-sm">delete</span>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <x-slot:empty>
                    <x-empty-state icon="swap_horiz" title="No transferred entries" description="Transferred or adjusted weight entries will appear in this table." />
                </x-slot:empty>
            @endforelse
        </x-data-table>
    </x-card>

        <template x-teleport="body">
            <x-modal name="edit-entry-modal" title="Edit Entry" subtitle="Adjust rates, weights, or box count" icon="edit" maxWidth="3xl">
                <form id="edit-entry-form" :action="editFormAction" method="POST" class="space-y-6">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">

                    {{-- Primary Details --}}
                    <div>
                        <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-3">Primary Details</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-5">
                            <x-form.input type="number" name="sort_order" label="Order #" min="1" x-model="editSortOrder" placeholder="1, 2..." icon="format_list_numbered" />
                            <x-form.select name="vendor_id" label="Vendor" required x-model="editVendorId">
                                <option value="">Select vendor...</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->firm_name }}{{ $vendor->is_shop ? ' (Shop)' : '' }}</option>
                                @endforeach
                            </x-form.select>

                            <x-form.select name="dealer_id" label="Dealer" required x-model="editDealerId">
                                <option value="">Select dealer...</option>
                                @foreach($dealers as $dealer)
                                    <option value="{{ $dealer->id }}">{{ $dealer->firm_name }}</option>
                                @endforeach
                            </x-form.select>

                            <x-form.input type="number" name="no_of_boxes" label="Boxes" required min="1" x-model.number="editNoOfBoxes" icon="inventory_2" />
                        </div>
                    </div>

                    {{-- Pricing Rates --}}
                    <div>
                        <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-3">Pricing Rates (Rs)</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                            <x-form.input type="number" step="0.01" name="paper_rate" label="Paper Rate" required min="0" x-model.number="editPaperRate" icon="currency_rupee" />
                            <x-form.input type="number" step="0.01" name="billing_rate" label="vendor Rate" required min="0" x-model.number="editBillingRate" icon="currency_rupee" />
                            <x-form.input type="number" step="0.01" name="customer_rate" label="Customer Rate" required min="0" x-model.number="editCustomerRate" icon="currency_rupee" />
                        </div>
                    </div>

                    {{-- Weights --}}
                    <div>
                        <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-3">Load Weights (Kg)</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <x-form.input type="number" step="0.01" name="box_weight" label="Box Weight" required min="0" x-model.number="editBoxWeight" icon="scale" />
                            <x-form.input type="number" step="0.01" name="empty_weight" label="Empty Weight" required min="0" x-model.number="editEmptyWeight" icon="scale" />
                        </div>
                    </div>

                    {{-- Remarks & Audit --}}
                    <div>
                        <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-3">Audit & Remarks</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <x-form.input type="text" name="remarks" label="Remarks" x-model="editRemarks" placeholder="Optional notes" icon="description" />
                            <x-form.input type="text" name="reason" label="Reason for Edit" required placeholder="Why are you editing this entry?" icon="help" />
                        </div>
                    </div>

                    <x-slot:footer>
                        <x-button type="button" variant="outline" x-on:click="$dispatch('close-modal', 'edit-entry-modal')">Cancel</x-button>
                        <x-button type="submit" form="edit-entry-form" variant="primary" icon="check" class="px-8">Save Changes</x-button>
                    </x-slot:footer>
                </form>
            </x-modal>
        </template>

        <template x-teleport="body">
            <x-modal name="transfer-boxes-modal" title="Transfer Weight" subtitle="Move weight of birds from one dealer/vendor to another" icon="swap_horiz" maxWidth="720">
                <form id="transfer-form" :action="transferFormAction" method="POST" class="space-y-6">
                    @csrf

                    {{-- Source Entry Details --}}
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-zinc-400 mb-3 flex items-center gap-1.5">
                            <span class="material-symbols-rounded text-sm">info</span>
                            Source Entry Details
                        </p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="p-3 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm flex flex-col justify-center">
                                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wide block mb-0.5">Vendor</span>
                                <p class="font-extrabold text-zinc-850 dark:text-zinc-150 text-xs truncate" x-text="transferSourceVendor || '—'"></p>
                            </div>
                            <div class="p-3 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm flex flex-col justify-center">
                                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wide block mb-0.5">Dealer</span>
                                <p class="font-extrabold text-zinc-850 dark:text-zinc-150 text-xs truncate" x-text="transferSourceDealer || '—'"></p>
                            </div>
                            <div class="p-3 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm flex flex-col justify-center">
                                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wide block mb-0.5">Available Wt</span>
                                <p class="font-jetbrains font-extrabold text-xs text-indigo-600 dark:text-indigo-400" x-text="parseFloat(transferSourceWeight).toFixed(2) + ' kg'"></p>
                            </div>
                            <div class="p-3 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm flex flex-col justify-center">
                                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wide block mb-0.5">Date</span>
                                <p class="font-extrabold text-zinc-850 dark:text-zinc-150 text-xs" x-text="transferDate || '—'"></p>
                            </div>
                        </div>
                    </div>

                    {{-- Input Fields --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <x-form.input 
                                type="number" 
                                name="transfer_weight" 
                                label="Weight to Transfer (kg)" 
                                required 
                                min="0.01" 
                                step="0.01" 
                                x-bind:max="'transferMaxWeight'" 
                                x-model.number="transferWeight" 
                                icon="scale"
                            />
                            <p class="mt-1.5 text-xs text-zinc-500">
                                Remaining: <span class="font-bold text-zinc-800 dark:text-zinc-200" x-text="parseFloat(transferSourceWeight - transferWeight).toFixed(2)"></span> kg
                            </p>
                        </div>

                        <x-form.select name="target_dealer_id" label="Target Dealer" required>
                            <option value="">Select dealer...</option>
                            @foreach($dealers as $dealer)
                                <option value="{{ $dealer->id }}">{{ $dealer->firm_name }}</option>
                            @endforeach
                        </x-form.select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="hidden">
                            <x-form.select name="target_vendor_id" label="Target Vendor" required x-model="transferTargetVendorId" >
                                <option value="">Select vendor...</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->firm_name }}{{ $vendor->is_shop ? ' (Shop)' : '' }}</option>
                                @endforeach
                            </x-form.select>
                        </div>

                        <x-form.input
                            type="number"
                            step="0.01"
                            min="0"
                            name="target_customer_rate"
                            label="Target Customer Rate (Rs/kg)"
                            required
                            x-model.number="transferTargetCustomerRate"
                            icon="currency_rupee"
                        />
                    </div>

                    <div class="mb-2">
                        <x-form.input type="text" name="reason" label="Reason" required placeholder="e.g. Reassign weight to correct dealer" icon="description" />
                    </div>

                    <x-slot:footer>
                        <x-button type="button" variant="outline" x-on:click="$dispatch('close-modal', 'transfer-boxes-modal')">Cancel</x-button>
                        <x-button type="submit" form="transfer-form" variant="primary" icon="check" class="px-8">Transfer Weight</x-button>
                    </x-slot:footer>
                </form>
            </x-modal>
        </template>

    @foreach($batches as $batch)
        <template x-teleport="body">
            <div x-data="{
                overallFarmWeight: '{{ ($batch->total_farm_weight > 0) ? (float)$batch->total_farm_weight : '' }}',
                reason: 'Farm weighbridge overall weight applied',
                submitting: false,
                entries: [
                    @foreach($batch->entries->where('status', '!=', 'Cancelled') as $entry)
                        {
                            id: {{ $entry->id }},
                            vendor: '{{ addslashes($entry->vendor->firm_name ?? '-') }}',
                            dealer: '{{ addslashes($entry->dealer->firm_name ?? '-') }}',
                            boxes: {{ (int) $entry->no_of_boxes }},
                            birdWeight: {{ (float) $entry->bird_weight }},
                            rate: {{ (float) ($entry->billing_rate > 0 ? $entry->billing_rate : $entry->paper_rate) }}
                        },
                    @endforeach
                ],
                get totalBirdWeight() {
                    return this.entries.reduce((sum, e) => sum + e.birdWeight, 0);
                },
                get totalBoxes() {
                    return this.entries.reduce((sum, e) => sum + e.boxes, 0);
                },
                get totalEntriesCount() {
                    return this.entries.length;
                },
                get totalFarmWeightNum() {
                    let val = parseFloat(this.overallFarmWeight);
                    return (!isNaN(val) && val > 0) ? val : 0;
                },
                get totalLoss() {
                    if (this.totalFarmWeightNum <= 0 || this.totalBirdWeight <= 0) return 0;
                    return (this.totalFarmWeightNum - this.totalBirdWeight).toFixed(2);
                },
                get lossPercentage() {
                    if (this.totalFarmWeightNum <= 0) return 0;
                    return ((parseFloat(this.totalLoss) / this.totalFarmWeightNum) * 100).toFixed(2);
                },
                getEntryFarmWeight(birdWeight) {
                    if (this.totalFarmWeightNum <= 0 || this.totalBirdWeight <= 0) return birdWeight.toFixed(2);
                    let ratio = birdWeight / this.totalBirdWeight;
                    return (ratio * this.totalFarmWeightNum).toFixed(2);
                },
                getEntryLoss(birdWeight) {
                    if (this.totalFarmWeightNum <= 0 || this.totalBirdWeight <= 0) return '0.00';
                    let fw = parseFloat(this.getEntryFarmWeight(birdWeight));
                    return (fw - birdWeight).toFixed(2);
                },
                getEntryLossPct(birdWeight) {
                    let fw = parseFloat(this.getEntryFarmWeight(birdWeight));
                    if (fw <= 0) return '0.0';
                    let loss = parseFloat(this.getEntryLoss(birdWeight));
                    return ((loss / fw) * 100).toFixed(1);
                }
            }">
                <x-modal name="set-farm-weight-modal-{{ $batch->id }}" title="Set Farm Weight — Batch #{{ $batch->id }} ({{ $batch->entries->first()?->vendor?->firm_name ?? 'Vendor' }})" subtitle="Enter the total farm weight for this batch — system automatically calculates and distributes average weight & loss to each entry" icon="scale" maxWidth="5xl">
                    <form id="set-farm-weight-form-{{ $batch->id }}" action="{{ route('billing.day-load.set-farm-weight') }}" method="POST"
                          @submit="if (submitting || totalFarmWeightNum <= 0) { $event.preventDefault(); return false; } submitting = true;"
                          class="space-y-6"
                    >
                        @csrf
                        <input type="hidden" name="batch_id" value="{{ $batch->id }}">

                    {{-- Main Single Overall Farm Weight Input --}}
                    <div class="p-5 rounded-2xl bg-gradient-to-br from-emerald-50/80 via-teal-50/40 to-indigo-50/40 dark:from-emerald-950/40 dark:via-teal-950/20 dark:to-indigo-950/20 border-2 border-emerald-500/30 dark:border-emerald-500/20 shadow-sm">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-emerald-800 dark:text-emerald-300 mb-1 flex items-center gap-1.5">
                                    <span class="material-symbols-rounded text-emerald-600 text-lg">scale</span>
                                    Enter Overall Farm Weight (Kg) *
                                </label>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Enter the total weighbridge weight from the farm for Batch #{{ $batch->id }}. It will be distributed proportionally across its dealers.
                                </p>
                            </div>
                            <div class="w-full sm:w-64">
                                <div class="relative">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        name="total_farm_weight"
                                        x-model="overallFarmWeight"
                                        required
                                        placeholder="0.00"
                                        class="w-full rounded-xl border-2 border-emerald-400 dark:border-emerald-600 bg-white dark:bg-zinc-900 px-4 py-3 text-lg font-jetbrains font-black text-emerald-700 dark:text-emerald-300 text-right focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all shadow-inner"
                                        autofocus
                                    >
                                    <span class="absolute left-3 top-3.5 text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">KG</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Live Summary Metrics Cards --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="rounded-2xl border border-zinc-200/60 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/40 p-4 shadow-sm">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 mb-1">Total Received Bird Wt</p>
                            <p class="font-jetbrains text-2xl font-black text-indigo-600 dark:text-indigo-400" x-text="totalBirdWeight.toFixed(2) + ' kg'"></p>
                            <p class="text-[10px] text-zinc-400 mt-0.5">Sum of shop weights (this batch)</p>
                        </div>
                        <div class="rounded-2xl border border-zinc-200/60 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/40 p-4 shadow-sm">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 mb-1">Overall Farm Weight</p>
                            <p class="font-jetbrains text-2xl font-black text-emerald-600 dark:text-emerald-400" x-text="totalFarmWeightNum > 0 ? totalFarmWeightNum.toFixed(2) + ' kg' : '—'"></p>
                            <p class="text-[10px] text-emerald-600/70 mt-0.5" x-show="totalFarmWeightNum > 0">Entered Farm Total</p>
                            <p class="text-[10px] text-zinc-400 mt-0.5" x-show="!totalFarmWeightNum || totalFarmWeightNum <= 0">Awaiting input</p>
                        </div>
                        <div class="rounded-2xl border border-zinc-200/60 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/40 p-4 shadow-sm">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 mb-1">Calculated Transit Loss</p>
                            <p class="font-jetbrains text-2xl font-black" :class="totalFarmWeightNum > 0 ? (parseFloat(totalLoss) >= 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600') : 'text-zinc-400'" x-text="totalFarmWeightNum > 0 ? (totalLoss >= 0 ? '+' : '') + totalLoss + ' kg' : '—'"></p>
                            <p class="text-[10px] font-bold mt-0.5" :class="totalFarmWeightNum > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-400'" x-text="totalFarmWeightNum > 0 ? 'Loss: ' + lossPercentage + '%' : '—'"></p>
                        </div>
                    </div>

                    {{-- Live Distributed Preview Table --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-300 flex items-center gap-1.5">
                                <span class="material-symbols-rounded text-indigo-500 text-sm">table_view</span>
                                Proportional Weight & Loss Distribution (Preview)
                            </h4>
                            <span class="text-[11px] text-zinc-400" x-show="totalFarmWeightNum > 0">
                                Distributed proportionally based on Bird Weight
                            </span>
                        </div>

                        <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-800 max-h-[42vh] overflow-y-auto scrollbar-thin scrollbar-thumb-zinc-200 dark:scrollbar-thumb-zinc-800">
                            <table class="w-full text-sm">
                                <thead class="sticky top-0 z-10 bg-zinc-100/95 dark:bg-zinc-800/95 backdrop-blur-xs border-b border-zinc-200 dark:border-zinc-750">
                                    <tr class="text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                        <th class="px-4 py-3 text-left">Vendor</th>
                                        <th class="px-4 py-3 text-left">Dealer</th>
                                        <th class="px-4 py-3 text-center">Boxes</th>
                                        <th class="px-4 py-3 text-center">Bird Wt (Kg)</th>
                                        <th class="px-4 py-3 text-center bg-emerald-100/50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300">Auto Farm Wt (Kg)</th>
                                        <th class="px-4 py-3 text-center text-amber-600">Loss (Kg)</th>
                                        <th class="px-4 py-3 text-center text-amber-600">Loss %</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/80 bg-white dark:bg-zinc-900">
                                    <template x-for="entry in entries" :key="entry.id">
                                        <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40 transition-colors">
                                            <td class="px-4 py-3 font-bold text-zinc-900 dark:text-zinc-100 text-xs" x-text="entry.vendor"></td>
                                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300 text-xs truncate max-w-[130px]" x-text="entry.dealer"></td>
                                            <td class="px-4 py-3 text-center font-jetbrains font-bold text-xs text-zinc-500" x-text="entry.boxes"></td>
                                            <td class="px-4 py-3 text-center font-jetbrains text-xs font-semibold text-zinc-800 dark:text-zinc-200" x-text="entry.birdWeight.toFixed(2)"></td>
                                            <td class="px-4 py-3 text-center font-jetbrains font-black text-xs text-emerald-600 dark:text-emerald-400 bg-emerald-50/30 dark:bg-emerald-950/10" x-text="getEntryFarmWeight(entry.birdWeight)"></td>
                                            <td class="px-4 py-3 text-center font-jetbrains font-bold text-xs" :class="parseFloat(getEntryLoss(entry.birdWeight)) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-400'" x-text="getEntryLoss(entry.birdWeight)"></td>
                                            <td class="px-4 py-3 text-center font-jetbrains text-xs text-zinc-500" x-text="getEntryLossPct(entry.birdWeight) + '%'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                                {{-- Total Summary Footer Row --}}
                                <tfoot class="sticky bottom-0 z-10 bg-zinc-100/95 dark:bg-zinc-800/95 backdrop-blur-xs border-t-2 border-zinc-300 dark:border-zinc-700 font-jetbrains font-bold text-xs shadow-md">
                                    <tr>
                                        <td colspan="2" class="px-4 py-3 text-left font-cabinet font-extrabold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider text-[11px]">
                                            <span class="flex items-center gap-1.5">
                                                <span class="material-symbols-rounded text-indigo-500 text-sm">functions</span>
                                                Total (<span x-text="totalEntriesCount"></span> <span x-text="totalEntriesCount === 1 ? 'Entry' : 'Entries'"></span>)
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center text-zinc-800 dark:text-zinc-200 font-bold" x-text="totalBoxes + ' Boxes'"></td>
                                        <td class="px-4 py-3 text-center text-indigo-600 dark:text-indigo-400 font-black" x-text="totalBirdWeight.toFixed(2) + ' kg'"></td>
                                        <td class="px-4 py-3 text-center text-emerald-600 dark:text-emerald-400 font-black bg-emerald-100/40 dark:bg-emerald-950/30" x-text="totalFarmWeightNum > 0 ? totalFarmWeightNum.toFixed(2) + ' kg' : '—'"></td>
                                        <td class="px-4 py-3 text-center font-black" :class="totalFarmWeightNum > 0 ? (parseFloat(totalLoss) >= 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600') : 'text-zinc-400'" x-text="totalFarmWeightNum > 0 ? (parseFloat(totalLoss) >= 0 ? '+' : '') + totalLoss + ' kg' : '—'"></td>
                                        <td class="px-4 py-3 text-center font-bold" :class="totalFarmWeightNum > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-400'" x-text="totalFarmWeightNum > 0 ? lossPercentage + '%' : '—'"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- Reason / Audit --}}
                    <div class="mb-2">
                        <x-form.input
                            type="text"
                            name="reason"
                            label="Reason for setting farm weight"
                            required
                            x-model="reason"
                            placeholder="e.g. Farm weighbridge slip #1234"
                            icon="description"
                        />
                    </div>

                    <x-slot:footer>
                        <x-button type="button" variant="outline" x-on:click="$dispatch('close-modal', 'set-farm-weight-modal-{{ $batch->id }}')">Cancel</x-button>
                        <x-button type="submit" form="set-farm-weight-form-{{ $batch->id }}" variant="primary" icon="check_circle" class="px-8 !bg-emerald-600 hover:!bg-emerald-700" x-bind:disabled="!totalFarmWeightNum || totalFarmWeightNum <= 0 || submitting">
                            <span x-text="submitting ? 'Calculating & Saving...' : 'Save & Apply to Batch'"></span>
                        </x-button>
                    </x-slot:footer>
                </form>
            </x-modal>
        </div>
        </template>
    @endforeach

        <template x-teleport="body">
            <x-modal name="dealer-payment-modal" title="Record Dealer Payment" subtitle="Record payment received from dealer for this entry" icon="payments" maxWidth="720">
                <form id="dealer-payment-form" :action="dpFormAction" method="POST" class="space-y-6">
                    @csrf

                    {{-- Due Summary Details --}}
                    <div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="p-3 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm flex flex-col justify-center">
                                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wide block mb-0.5">Vendor</span>
                                <p class="font-extrabold text-zinc-850 dark:text-zinc-150 text-xs truncate" x-text="dpEntryVendor"></p>
                            </div>
                            <div class="p-3 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm flex flex-col justify-center">
                                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wide block mb-0.5">Dealer</span>
                                <p class="font-extrabold text-zinc-850 dark:text-zinc-150 text-xs truncate" x-text="dpEntryDealer"></p>
                            </div>
                            <div class="p-3 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm flex flex-col justify-center">
                                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wide block mb-0.5">Total Due</span>
                                <p class="font-jetbrains font-extrabold text-xs text-rose-600" x-text="'Rs ' + (dpEntryIncome - dpEntryCollected).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></p>
                            </div>
                            <div class="p-3 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm flex flex-col justify-center">
                                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wide block mb-0.5">Collected</span>
                                <p class="font-jetbrains font-extrabold text-xs text-emerald-600" x-text="'Rs ' + dpEntryCollected.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <x-form.input type="date" name="date" label="Payment Date" required x-model="dpDate" icon="calendar_month" />
                        <x-form.input type="number" step="0.01" min="0" name="cash_amount" label="Cash Amount (Rs)" required x-model.number="dpCashAmount" icon="payments" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <x-form.input type="number" step="0.01" min="0" name="bank_amount" label="Bank Amount (Rs)" required x-model.number="dpBankAmount" icon="account_balance" />
                        <div>
                            <label class="block mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 font-outfit">Total Payment</label>
                            <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-3.5 py-2.5 text-emerald-600 dark:text-emerald-400 font-jetbrains text-lg font-extrabold flex items-center justify-between min-h-[46px]">
                                <span class="text-xs font-bold uppercase tracking-wider text-emerald-500 font-outfit">Total</span>
                                <span x-text="'Rs ' + (dpCashAmount + dpBankAmount).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Method Selection --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 font-outfit mb-3">
                            Payment Mode <span class="text-emerald-500 font-bold ml-0.5">*</span>
                        </label>
                        <div class="grid grid-cols-4 gap-2.5">
                            @php 
                                $pmOptions = [
                                    ['value' => 'Cash', 'icon' => 'payments', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50'],
                                    ['value' => 'Bank Transfer', 'icon' => 'account_balance', 'color' => 'text-blue-500', 'bg' => 'bg-blue-50'],
                                    ['value' => 'UPI', 'icon' => 'smartphone', 'color' => 'text-violet-500', 'bg' => 'bg-violet-50'],
                                    ['value' => 'Card', 'icon' => 'credit_card', 'color' => 'text-rose-500', 'bg' => 'bg-rose-50']
                                ]; 
                            @endphp
                            @foreach($pmOptions as $pm)
                            <label class="group relative flex flex-col items-center gap-2 py-4 px-1 rounded-2xl border-2 cursor-pointer transition-all duration-200 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/80 dark:has-[:checked]:bg-emerald-500/12 has-[:checked]:shadow-[0_0_0_1px_rgba(16,185,129,0.15),0_4px_12px_rgba(16,185,129,0.15)] border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 bg-white/50 dark:bg-zinc-900/50">
                                <input type="radio" name="payment_mode" value="{{ $pm['value'] }}" x-model="dpMode" class="sr-only" required>
                                <div class="w-9 h-9 rounded-full {{ $pm['bg'] }} dark:{{ $pm['bg'] }}/10 flex items-center justify-center {{ $pm['color'] }}">
                                    <span class="material-symbols-rounded text-xl">{{ $pm['icon'] }}</span>
                                </div>
                                <span class="text-[11px] font-semibold text-zinc-500 dark:text-zinc-400 group-has-[:checked]:text-emerald-700 dark:group-has-[:checked]:text-emerald-300 group-has-[:checked]:font-bold transition-all text-center leading-tight">{{ $pm['value'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div x-show="dpBankAmount > 0" x-transition class="w-full">
                            <x-form.select name="bank_transfer_type" label="Bank Transfer Type" x-model="dpBankTransferType" x-bind:required="dpBankAmount > 0">
                                <option value="">Select type...</option>
                                <option value="UPI">UPI</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="NEFT">NEFT</option>
                                <option value="RTGS">RTGS</option>
                                <option value="IMPS">IMPS</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Other">Other</option>
                            </x-form.select>
                        </div>

                        <div x-show="dpBankAmount <= 0" x-transition class="w-full">
                            <x-form.input type="text" name="reference_number" label="Reference No" x-model="dpRefNo" placeholder="UPI ref / Cheque no / Tx ID" icon="description" />
                        </div>
                    </div>

                    <div class="mb-2">
                        <x-form.textarea name="notes" label="Remarks" x-model="dpNotes" rows="2" placeholder="Optional notes" />
                    </div>

                    <x-slot:footer>
                        <x-button type="button" variant="outline" x-on:click="$dispatch('close-modal', 'dealer-payment-modal')">Cancel</x-button>
                        <x-button type="submit" form="dealer-payment-form" variant="primary" icon="check" class="px-8">Record Payment</x-button>
                    </x-slot:footer>
                </form>
            </x-modal>
        </template>

        <template x-teleport="body">
            <x-modal name="vendor-payment-modal" title="Record Vendor Payment" subtitle="Record payment made to vendor for this entry" icon="account_balance_wallet" maxWidth="720">
                <form id="vendor-payment-form" :action="vpFormAction" method="POST" class="space-y-6">
                    @csrf

                    {{-- Payable Summary Details --}}
                    <div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="p-3 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm flex flex-col justify-center">
                                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wide block mb-0.5">Vendor</span>
                                <p class="font-extrabold text-zinc-850 dark:text-zinc-150 text-xs truncate" x-text="vpEntryVendor"></p>
                            </div>
                            <div class="p-3 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm flex flex-col justify-center">
                                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wide block mb-0.5">Dealer</span>
                                <p class="font-extrabold text-zinc-850 dark:text-zinc-150 text-xs truncate" x-text="vpEntryDealer"></p>
                            </div>
                            <div class="p-3 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm flex flex-col justify-center">
                                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wide block mb-0.5">Total Payable</span>
                                <p class="font-jetbrains font-extrabold text-xs text-rose-600" x-text="'Rs ' + (vpEntryCost - vpEntryPaid).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></p>
                            </div>
                            <div class="p-3 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm flex flex-col justify-center">
                                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wide block mb-0.5">Already Paid</span>
                                <p class="font-jetbrains font-extrabold text-xs text-emerald-600" x-text="'Rs ' + vpEntryPaid.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <x-form.input type="date" name="date" label="Payment Date" required x-model="vpDate" icon="calendar_month" />
                        <x-form.input type="number" step="0.01" min="0" name="cash_amount" label="Cash Amount (Rs)" required x-model.number="vpCashAmount" icon="payments" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <x-form.input type="number" step="0.01" min="0" name="bank_amount" label="Bank Amount (Rs)" required x-model.number="vpBankAmount" icon="account_balance" />
                        <div>
                            <label class="block mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 font-outfit">Total Payment</label>
                            <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-3.5 py-2.5 text-emerald-600 dark:text-emerald-400 font-jetbrains text-lg font-extrabold flex items-center justify-between min-h-[46px]">
                                <span class="text-xs font-bold uppercase tracking-wider text-emerald-500 font-outfit">Total</span>
                                <span x-text="'Rs ' + (vpCashAmount + vpBankAmount).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Method Selection --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 font-outfit mb-3">
                            Payment Mode <span class="text-emerald-500 font-bold ml-0.5">*</span>
                        </label>
                        <div class="grid grid-cols-4 gap-2.5">
                            @php 
                                $pmOptions = [
                                    ['value' => 'Cash', 'icon' => 'payments', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50'],
                                    ['value' => 'Bank Transfer', 'icon' => 'account_balance', 'color' => 'text-blue-500', 'bg' => 'bg-blue-50'],
                                    ['value' => 'UPI', 'icon' => 'smartphone', 'color' => 'text-violet-500', 'bg' => 'bg-violet-50'],
                                    ['value' => 'Card', 'icon' => 'credit_card', 'color' => 'text-rose-500', 'bg' => 'bg-rose-50']
                                ]; 
                            @endphp
                            @foreach($pmOptions as $pm)
                            <label class="group relative flex flex-col items-center gap-2 py-4 px-1 rounded-2xl border-2 cursor-pointer transition-all duration-200 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/80 dark:has-[:checked]:bg-emerald-500/12 has-[:checked]:shadow-[0_0_0_1px_rgba(16,185,129,0.15),0_4px_12px_rgba(16,185,129,0.15)] border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 bg-white/50 dark:bg-zinc-900/50">
                                <input type="radio" name="payment_mode" value="{{ $pm['value'] }}" x-model="vpMode" class="sr-only" required>
                                <div class="w-9 h-9 rounded-full {{ $pm['bg'] }} dark:{{ $pm['bg'] }}/10 flex items-center justify-center {{ $pm['color'] }}">
                                    <span class="material-symbols-rounded text-xl">{{ $pm['icon'] }}</span>
                                </div>
                                <span class="text-[11px] font-semibold text-zinc-500 dark:text-zinc-400 group-has-[:checked]:text-emerald-700 dark:group-has-[:checked]:text-emerald-300 group-has-[:checked]:font-bold transition-all text-center leading-tight">{{ $pm['value'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div x-show="vpBankAmount > 0" x-transition class="w-full">
                            <x-form.select name="bank_transfer_type" label="Bank Transfer Type" x-model="vpBankTransferType" x-bind:required="vpBankAmount > 0">
                                <option value="">Select type...</option>
                                <option value="UPI">UPI</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="NEFT">NEFT</option>
                                <option value="RTGS">RTGS</option>
                                <option value="IMPS">IMPS</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Other">Other</option>
                            </x-form.select>
                        </div>

                        <div x-show="vpBankAmount <= 0" x-transition class="w-full">
                            <x-form.input type="text" name="reference_number" label="Reference No" x-model="vpRefNo" placeholder="UPI ref / Cheque no / Tx ID" icon="description" />
                        </div>
                    </div>

                    <div class="mb-2">
                        <x-form.textarea name="notes" label="Remarks" x-model="vpNotes" rows="2" placeholder="Optional notes" />
                    </div>

                    <x-slot:footer>
                        <x-button type="button" variant="outline" x-on:click="$dispatch('close-modal', 'vendor-payment-modal')">Cancel</x-button>
                        <x-button type="submit" form="vendor-payment-form" variant="primary" icon="check" class="px-8">Record Payment</x-button>
                    </x-slot:footer>
                </form>
            </x-modal>
        </template>

        <template x-teleport="body">
            <x-modal name="lump-sum-payment-modal" title="Record Lump-Sum Payment" subtitle="Allocate a single payment across multiple entries" icon="payments" maxWidth="3xl">
                <form id="lump-sum-form" action="{{ route('billing.day-load.lumpsum-dealer-payment') }}" method="POST" class="space-y-6">
                    @csrf
                    <input type="hidden" name="dealer_id" :value="lsDealerId">
                    <input type="hidden" name="date" :value="lsDate">
                    <input type="hidden" name="cash_amount" :value="lsCashAmount">
                    <input type="hidden" name="bank_amount" :value="lsBankAmount">
                    <input type="hidden" name="payment_mode" :value="lsMode">
                    <input type="hidden" name="bank_transfer_type" :value="lsBankTransferType">
                    <input type="hidden" name="reference_number" :value="lsRefNo">
                    <input type="hidden" name="notes" :value="lsNotes">
                    <template x-for="(amount, entryId) in lsAllocations" :key="entryId">
                        <input type="hidden" :name="'allocations[' + entryId + ']'" :value="amount">
                    </template>

                    {{-- Dealer Select Step --}}
                    <div class="grid grid-cols-1">
                        <x-form.select name="dealer_id" label="Select Dealer" x-model="lsDealerId" @change="initLsDealer()">
                            <option value="0">Choose dealer...</option>
                            @foreach($dealers as $dealer)
                                <option value="{{ $dealer->id }}">{{ $dealer->firm_name }}</option>
                            @endforeach
                        </x-form.select>
                    </div>

                    {{-- Allocation Table --}}
                    <template x-if="lsEntries.length > 0">
                        <div class="space-y-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-zinc-400 flex items-center gap-1.5">
                                <span class="material-symbols-rounded text-sm text-zinc-400">playlist_add_check</span>
                                Allocate Payment Across Entries
                            </p>
                            <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-800">
                                <table class="w-full text-sm">
                                    <thead class="bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-750">
                                        <tr class="text-xs font-bold text-zinc-450 uppercase">
                                            <th class="px-4 py-3 text-left">Vendor</th>
                                            <th class="px-4 py-3 text-right">Total (Rs)</th>
                                            <th class="px-4 py-3 text-right text-emerald-600">Collected (Rs)</th>
                                            <th class="px-4 py-3 text-right">Balance (Rs)</th>
                                            <th class="px-4 py-3 text-right min-w-[140px]">Allocate Now (Rs)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-100 dark:divide-zinc-800/80">
                                        <template x-for="entry in lsEntries" :key="entry.id">
                                            <tr class="hover:bg-zinc-50/65 transition-colors border-t border-zinc-100 dark:border-zinc-800">
                                                <td class="px-4 py-3 font-bold text-xs" x-text="entry.vendor"></td>
                                                <td class="px-4 py-3 text-right font-jetbrains text-xs text-zinc-600 dark:text-zinc-400" x-text="entry.dealer_income.toLocaleString('en-IN', {minimumFractionDigits: 2})"></td>
                                                <td class="px-4 py-3 text-right font-jetbrains text-emerald-600 text-xs" x-text="entry.dealer_collected.toLocaleString('en-IN', {minimumFractionDigits: 2})"></td>
                                                <td class="px-4 py-3 text-right font-jetbrains font-bold text-xs text-zinc-850 dark:text-zinc-150" x-text="entry.due.toLocaleString('en-IN', {minimumFractionDigits: 2})"></td>
                                                <td class="px-4 py-3 text-right">
                                                    <input type="number" step="0.01" min="0" :max="entry.due"
                                                        x-model.number="lsAllocations[entry.id]"
                                                        @input="if (lsAllocations[entry.id] > entry.due) lsAllocations[entry.id] = entry.due; recalcAllocSum()"
                                                        class="w-32 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-right text-xs font-jetbrains focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-bold text-emerald-600">
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                    <tfoot class="bg-zinc-50 dark:bg-zinc-850 font-bold border-t border-zinc-200 dark:border-zinc-750">
                                        <tr>
                                            <td class="px-4 py-3 text-xs text-zinc-400 uppercase tracking-wider font-extrabold">TOTAL</td>
                                            <td class="px-4 py-3 text-right font-jetbrains text-xs text-zinc-600 dark:text-zinc-400" x-text="lsEntries.reduce((s, e) => s + e.dealer_income, 0).toLocaleString('en-IN', {minimumFractionDigits: 2})"></td>
                                            <td class="px-4 py-3 text-right font-jetbrains text-emerald-600 text-xs" x-text="lsEntries.reduce((s, e) => s + e.dealer_collected, 0).toLocaleString('en-IN', {minimumFractionDigits: 2})"></td>
                                            <td class="px-4 py-3 text-right font-jetbrains text-xs text-zinc-800 dark:text-zinc-200" x-text="lsEntries.reduce((s, e) => s + e.due, 0).toLocaleString('en-IN', {minimumFractionDigits: 2})"></td>
                                            <td class="px-4 py-3 text-right font-jetbrains text-emerald-600 text-xs" x-text="lsAllocSum.toLocaleString('en-IN', {minimumFractionDigits: 2})"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="mt-2.5 flex items-center justify-end gap-2 text-xs text-zinc-400 font-medium">
                                <span>Allocated: <strong class="font-jetbrains text-zinc-700 dark:text-zinc-300" x-text="'Rs ' + lsAllocSum.toLocaleString('en-IN', {minimumFractionDigits: 2})"></strong></span>
                                <span class="text-zinc-300 dark:text-zinc-600">/</span>
                                <span>Lump sum: <strong class="font-jetbrains text-zinc-700 dark:text-zinc-300" x-text="'Rs ' + lsTotalLump.toLocaleString('en-IN', {minimumFractionDigits: 2})"></strong></span>
                                <template x-if="lsAllocSum > lsTotalLump">
                                    <span class="text-rose-600 font-bold ml-1 flex items-center gap-0.5">
                                        <span class="material-symbols-rounded text-sm">warning</span>
                                        Exceeds by Rs <span x-text="(lsAllocSum - lsTotalLump).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Payment Details Group --}}
                    <div>
                        <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-3">Payment Details</h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                            <x-form.input type="date" name="date" label="Payment Date" x-model="lsDate" icon="calendar_month" />
                            <x-form.input type="number" step="0.01" min="0" label="Cash Amount (Rs)" x-model.number="lsCashAmount" @input="lsTotalLump = Math.round((lsCashAmount + lsBankAmount) * 100) / 100" icon="payments" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                            <x-form.input type="number" step="0.01" min="0" label="Bank Amount (Rs)" x-model.number="lsBankAmount" @input="lsTotalLump = Math.round((lsCashAmount + lsBankAmount) * 100) / 100" icon="account_balance" />
                            <div>
                                <label class="block mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 font-outfit">Total Lump Sum</label>
                                <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-3.5 py-2.5 text-emerald-600 dark:text-emerald-400 font-jetbrains text-lg font-extrabold flex items-center justify-between min-h-[46px]">
                                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-500 font-outfit">Total</span>
                                    <span x-text="'Rs ' + lsTotalLump.toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Payment Method Selection --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 font-outfit mb-3">
                                Payment Mode <span class="text-emerald-500 font-bold ml-0.5">*</span>
                            </label>
                            <div class="grid grid-cols-4 gap-2.5">
                                @php 
                                    $pmOptions = [
                                        ['value' => 'Cash', 'icon' => 'payments', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50'],
                                        ['value' => 'Bank Transfer', 'icon' => 'account_balance', 'color' => 'text-blue-500', 'bg' => 'bg-blue-50'],
                                        ['value' => 'UPI', 'icon' => 'smartphone', 'color' => 'text-violet-500', 'bg' => 'bg-violet-50'],
                                        ['value' => 'Card', 'icon' => 'credit_card', 'color' => 'text-rose-500', 'bg' => 'bg-rose-50']
                                    ]; 
                                @endphp
                                @foreach($pmOptions as $pm)
                                <label class="group relative flex flex-col items-center gap-2 py-4 px-1 rounded-2xl border-2 cursor-pointer transition-all duration-200 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/80 dark:has-[:checked]:bg-emerald-500/12 has-[:checked]:shadow-[0_0_0_1px_rgba(16,185,129,0.15),0_4px_12px_rgba(16,185,129,0.15)] border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 bg-white/50 dark:bg-zinc-900/50">
                                    <input type="radio" name="payment_mode_ls" value="{{ $pm['value'] }}" x-model="lsMode" class="sr-only" required>
                                    <div class="w-9 h-9 rounded-full {{ $pm['bg'] }} dark:{{ $pm['bg'] }}/10 flex items-center justify-center {{ $pm['color'] }}">
                                        <span class="material-symbols-rounded text-xl">{{ $pm['icon'] }}</span>
                                    </div>
                                    <span class="text-[11px] font-semibold text-zinc-500 dark:text-zinc-400 group-has-[:checked]:text-emerald-700 dark:group-has-[:checked]:text-emerald-300 group-has-[:checked]:font-bold transition-all text-center leading-tight">{{ $pm['value'] }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div x-show="lsBankAmount > 0" x-transition class="w-full">
                                <x-form.select name="bank_transfer_type" label="Bank Transfer Type" x-model="lsBankTransferType">
                                    <option value="">Select type...</option>
                                    <option value="UPI">UPI</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="NEFT">NEFT</option>
                                    <option value="RTGS">RTGS</option>
                                    <option value="IMPS">IMPS</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Other">Other</option>
                                </x-form.select>
                            </div>

                            <div x-show="lsBankAmount <= 0" x-transition class="w-full">
                                <x-form.input type="text" name="reference_number" label="Reference No" x-model="lsRefNo" placeholder="UPI ref / Cheque no / Tx ID" icon="description" />
                            </div>
                        </div>

                        <div class="mt-5 mb-2">
                            <x-form.textarea name="notes" label="Remarks" x-model="lsNotes" rows="2" placeholder="Optional notes" />
                        </div>
                    </div>

                    <x-slot:footer>
                        <x-button type="button" variant="outline" x-on:click="$dispatch('close-modal', 'lump-sum-payment-modal')">Cancel</x-button>
                        <x-button type="submit" form="lump-sum-form" variant="primary" icon="check" x-bind:disabled="lsAllocSum > lsTotalLump || lsAllocSum <= 0" class="px-8">Record Lump-Sum Payment</x-button>
                    </x-slot:footer>
                </form>
            </x-modal>
        </template>

        <template x-teleport="body">
            {{-- Bulk Load Entry Modal (One-to-Many: 1 Vendor -> Multiple Dealers) --}}
            <x-modal name="bulk-load-modal" title="New Load Entry (Multi-Dealer Sheet)" subtitle="Record loads across multiple dealers for one vendor in a single batch" icon="playlist_add" maxWidth="7xl">
                <form id="bulk-load-form" action="{{ route('billing.day-load.bulk-store') }}" method="POST" class="flex flex-col h-full max-h-[82vh]">
                    @csrf

                    {{-- Scrollable Content Area --}}
                    <div class="overflow-y-auto px-1 pr-2 space-y-4 flex-1">
                        {{-- Section 1: Master Parameters (Vendor, Date, Rates) --}}
                        <div class="p-4 rounded-2xl bg-gradient-to-br from-zinc-50/90 via-white to-zinc-50/90 dark:from-zinc-900/90 dark:via-zinc-900/60 dark:to-zinc-900/90 border border-zinc-200/80 dark:border-zinc-800 shadow-xs">
                            <div class="flex items-center justify-between gap-2 mb-3 pb-2 border-b border-zinc-200/60 dark:border-zinc-800/60">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-lg bg-emerald-500/10 dark:bg-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                        <span class="material-symbols-rounded text-sm">tune</span>
                                    </div>
                                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-zinc-800 dark:text-zinc-200 font-outfit">Master Load Parameters</h3>
                                </div>
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full ring-1 ring-emerald-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Live Calculation Mode
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3.5 items-start">
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5 font-outfit flex items-center gap-1">
                                        <span class="material-symbols-rounded text-xs text-zinc-400">store</span>
                                        Vendor / Company <span class="text-rose-500">*</span>
                                    </label>
                                    <select name="vendor_id" x-model="bulkVendorId" :class="{'!border-rose-400 !ring-2 !ring-rose-400/25 bg-rose-50/20': bulkValidationAttempted && !bulkVendorId}" required class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-xs px-3 py-2.5 font-semibold text-zinc-900 dark:text-zinc-100 shadow-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                                        <option value="">-- Select Vendor --</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}">{{ $vendor->firm_name }}{{ $vendor->is_shop ? ' (Shop)' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5 font-outfit flex items-center gap-1">
                                        <span class="material-symbols-rounded text-xs text-zinc-400">calendar_today</span>
                                        Billing Date <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="date" name="billing_date" x-model="bulkBillingDate" :class="{'!border-rose-400 !ring-2 !ring-rose-400/25 bg-rose-50/20': bulkValidationAttempted && !bulkBillingDate}" required class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-xs px-3 py-2.5 font-semibold text-zinc-900 dark:text-zinc-100 shadow-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                                        <span class="absolute right-2.5 top-2.5 px-2 py-0.5 rounded-md text-[9px] font-extrabold bg-emerald-50 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20 pointer-events-none" x-text="bulkDayOfWeek"></span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5 font-outfit flex items-center gap-1">
                                        <span class="material-symbols-rounded text-xs text-zinc-400">local_shipping</span>
                                        Target Batch
                                    </label>
                                    <select name="batch_id" x-model="bulkBatchId" class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-xs px-3 py-2.5 font-semibold text-zinc-900 dark:text-zinc-100 shadow-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                                        <option value="">+ New Batch (Auto Create)</option>
                                        @foreach($batches as $b)
                                            <option value="{{ $b->id }}">Batch #{{ $loop->iteration }} ({{ $b->entries->first()?->vendor?->firm_name ?? 'Batch' }} - {{ $b->entries->count() }} entries)</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5 font-outfit flex items-center gap-1">
                                        <span class="material-symbols-rounded text-xs text-zinc-400">newspaper</span>
                                        Paper Rate (Rs) <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="number" step="0.01" min="0" name="paper_rate" x-model.number="bulkPaperRate" :class="{'!border-rose-400 !ring-2 !ring-rose-400/25 bg-rose-50/20': bulkValidationAttempted && (!bulkPaperRate || parseFloat(bulkPaperRate) <= 0)}" required placeholder="120.00" class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-xs px-3 py-2.5 font-jetbrains font-bold text-zinc-900 dark:text-zinc-100 shadow-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-1.5 font-outfit flex items-center gap-1">
                                        <span class="material-symbols-rounded text-xs text-zinc-400">sell</span>
                                        Vendor Rate Final (Rs)
                                    </label>
                                    <input type="number" step="0.01" min="0" name="billing_rate" x-model.number="bulkBillingRate" placeholder="Optional final rate" class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-xs px-3 py-2.5 font-jetbrains font-bold text-zinc-900 dark:text-zinc-100 shadow-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                                </div>

                                <div class="rounded-xl border border-violet-200/80 dark:border-violet-800/80 bg-gradient-to-br from-violet-50/70 to-indigo-50/70 dark:from-violet-950/30 dark:to-indigo-950/30 p-2.5 h-[62px] flex flex-col justify-center shadow-xs">
                                    <div class="flex items-center justify-between">
                                        <p class="text-[9px] font-extrabold uppercase tracking-wider text-violet-700 dark:text-violet-300">Effective Vendor Rate</p>
                                        <span class="material-symbols-rounded text-violet-500 text-sm">verified</span>
                                    </div>
                                    <p class="font-jetbrains text-base font-black text-violet-900 dark:text-violet-200 mt-0.5">
                                        Rs <span x-text="bulkActiveVendorRate > 0 ? bulkActiveVendorRate.toFixed(2) : '0.00'"></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Vendor Advance Alert (If Vendor has Advance) --}}
                        <div x-show="bulkAvailableAdvance && bulkAvailableAdvance.total_remaining > 0" x-cloak class="p-3.5 rounded-2xl bg-gradient-to-r from-amber-50 to-orange-50/80 dark:from-amber-950/40 dark:to-orange-950/30 border border-amber-200/90 dark:border-amber-800/90 flex flex-wrap items-center justify-between gap-3 shadow-xs">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-amber-500/15 text-amber-600 flex items-center justify-center flex-shrink-0">
                                    <span class="material-symbols-rounded text-lg">local_shipping</span>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-amber-950 dark:text-amber-200">
                                        Vendor Advance Available: <span class="font-jetbrains text-xs font-black text-amber-800 dark:text-amber-300">Rs <span x-text="bulkAvailableAdvance ? bulkAvailableAdvance.total_remaining.toFixed(2) : '0.00'"></span></span>
                                    </p>
                                    <p class="text-[10px] text-amber-700/80 dark:text-amber-400 font-medium">Optionally adjust load total against advance deposit.</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <div>
                                    <select name="vendor_advance_id" x-model="bulkVendorAdvanceId" class="rounded-xl border border-amber-300 dark:border-amber-700 bg-white dark:bg-zinc-900 text-xs px-3 py-1.5 font-semibold shadow-xs focus:ring-2 focus:ring-amber-500/20">
                                        <option value="">-- Select Advance Deposit --</option>
                                        <template x-for="adv in (bulkAvailableAdvance ? bulkAvailableAdvance.advances : [])" :key="adv.id">
                                            <option :value="adv.id" x-text="adv.date + ' (Bal: Rs ' + adv.remaining + ')'"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="flex items-center gap-1">
                                    <input type="number" step="0.01" min="0" name="apply_advance_amount" x-model="bulkApplyAdvanceAmount" placeholder="Amount" class="w-28 rounded-xl border border-amber-300 dark:border-amber-700 bg-white dark:bg-zinc-900 text-xs px-2.5 py-1.5 font-jetbrains font-bold shadow-xs focus:ring-2 focus:ring-amber-500/20">
                                    <button type="button" @click="fillBulkMaxAdvance()" class="text-[10px] uppercase font-extrabold text-amber-900 bg-amber-200 hover:bg-amber-300 dark:bg-amber-800/60 dark:text-amber-200 px-2.5 py-1.5 rounded-xl transition-all shadow-xs">Max</button>
                                </div>
                            </div>
                        </div>

                        {{-- Section 2: Smart Customer Rate Manager & Search Toolbar --}}
                        <div class="p-3.5 rounded-2xl bg-gradient-to-r from-zinc-100/90 via-emerald-50/40 to-teal-50/40 dark:from-zinc-900/90 dark:via-emerald-950/20 dark:to-teal-950/20 border border-zinc-200/80 dark:border-zinc-800 shadow-xs flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3.5">
                            {{-- Left: Dealer Search --}}
                            <div class="flex items-center gap-2 flex-1 max-w-sm">
                                <div class="relative w-full">
                                    <span class="material-symbols-rounded absolute left-3 top-2.5 text-zinc-400 text-sm">search</span>
                                    <input type="text" x-model="bulkDealerSearch" placeholder="Search dealer by name / location / route..." class="w-full pl-8 pr-8 py-2 rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-xs font-medium focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 shadow-2xs transition-all">
                                    <button type="button" x-show="bulkDealerSearch" @click="bulkDealerSearch = ''" class="absolute right-2.5 top-2.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                                        <span class="material-symbols-rounded text-sm">close</span>
                                    </button>
                                </div>
                            </div>

                            {{-- Center/Right: Rate Manager Toolbar (Both Option A & Option B) --}}
                            <div class="flex flex-wrap items-center gap-2">
                                {{-- Mode Switcher Pill --}}
                                <div class="inline-flex rounded-xl bg-zinc-200/70 dark:bg-zinc-800 p-0.5 text-[11px] font-bold text-zinc-600 dark:text-zinc-300 shadow-inner">
                                    <button type="button" @click="bulkRateMode = 'margin'" :class="bulkRateMode === 'margin' ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-100'" class="px-2.5 py-1 rounded-lg transition-all flex items-center gap-1">
                                        <span class="material-symbols-rounded text-xs">calculate</span>
                                        Paper + Margin
                                    </button>
                                    <button type="button" @click="bulkRateMode = 'direct'" :class="bulkRateMode === 'direct' ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-100'" class="px-2.5 py-1 rounded-lg transition-all flex items-center gap-1">
                                        <span class="material-symbols-rounded text-xs">tune</span>
                                        Flat Rate
                                    </button>
                                </div>

                                {{-- Option B: Margin Formula Controller --}}
                                <div x-show="bulkRateMode === 'margin'" class="flex flex-wrap items-center gap-1.5 bg-white dark:bg-zinc-900 px-2.5 py-1 rounded-xl border border-emerald-500/30 dark:border-emerald-500/20 shadow-2xs">
                                    <span class="text-[10px] font-bold text-zinc-500 font-outfit">
                                        Paper (<span class="font-jetbrains text-emerald-600 font-bold" x-text="bulkPaperRate > 0 ? bulkPaperRate : '0'"></span>) +
                                    </span>
                                    <input type="number" step="0.5" x-model="bulkMarginOffset" placeholder="5" class="w-12 px-1.5 py-0.5 text-xs font-jetbrains font-bold text-center rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 focus:ring-1 focus:ring-emerald-500">
                                    
                                    {{-- Quick Margin Shortcut Buttons --}}
                                    <div class="hidden sm:flex items-center gap-1">
                                        <template x-for="m in [3, 4, 5, 6, 7]" :key="m">
                                            <button type="button" @click="applyMarginFormula(m)" :class="bulkMarginOffset == m ? 'bg-emerald-600 text-white font-black' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200'" class="px-1.5 py-0.5 rounded-md text-[10px] font-jetbrains transition-transform active:scale-95">
                                                +<span x-text="m"></span>
                                            </button>
                                        </template>
                                    </div>

                                    <button type="button" @click="applyMarginFormula()" class="px-2.5 py-1 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-[11px] font-bold transition-all shadow-xs flex items-center gap-1">
                                        <span class="material-symbols-rounded text-xs">done_all</span>
                                        Apply (<span class="font-jetbrains">Rs <span x-text="computedMarginRate"></span></span>) to All
                                    </button>
                                </div>

                                {{-- Option A (Direct Flat Rate tool): --}}
                                <div x-show="bulkRateMode === 'direct'" class="flex items-center gap-1.5 bg-white dark:bg-zinc-900 px-2 py-1 rounded-xl border border-zinc-300 dark:border-zinc-700 shadow-2xs">
                                    <span class="text-[10px] font-bold text-zinc-500 pl-1">Flat Rate:</span>
                                    <input type="number" step="0.01" min="0" x-model="bulkGlobalCustomerRate" placeholder="e.g. 125.00" class="w-20 px-2 py-0.5 text-xs font-jetbrains font-bold rounded-lg border-0 bg-zinc-100 dark:bg-zinc-800 focus:ring-1 focus:ring-emerald-500">
                                    <button type="button" @click="applyGlobalCustomerRate()" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold transition-colors">
                                        Apply to All
                                    </button>
                                </div>

                                {{-- Reset & Counters --}}
                                <button type="button" @click="clearAllDealers()" class="px-2.5 py-1.5 rounded-xl bg-zinc-200/80 dark:bg-zinc-700 hover:bg-zinc-300 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-200 text-xs font-semibold transition-all active:scale-95 flex items-center gap-1">
                                    <span class="material-symbols-rounded text-xs">restart_alt</span>
                                    Reset All
                                </button>

                                <span class="px-2.5 py-1.5 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-200 text-xs font-black inline-flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span x-text="bulkActiveEntriesCount"></span> / <span x-text="bulkDealers.length"></span> Active
                                </span>
                            </div>
                        </div>

                        {{-- Section 3: Dealers Data Grid --}}
                        <div class="rounded-2xl border border-zinc-200/80 dark:border-zinc-800 overflow-hidden shadow-xs bg-white dark:bg-zinc-950">
                            <div class="overflow-x-auto max-h-[400px]">
                                <table class="w-full text-left text-xs text-zinc-700 dark:text-zinc-300 divide-y divide-zinc-200/80 dark:divide-zinc-800">
                                    <thead class="sticky top-0 z-10 bg-zinc-100/95 dark:bg-zinc-900/95 backdrop-blur-md font-bold uppercase tracking-wider text-[10px] text-zinc-500 dark:text-zinc-400 select-none shadow-xs">
                                        <tr>
                                            <th class="hidden" style="display: none;" title="Load Sequence / Order #">Order #</th>
                                            <th class="px-3.5 py-3 w-48 font-outfit">Dealer Name</th>
                                            <th class="px-2.5 py-3 w-32 font-outfit">Customer Rate (Rs) <span class="text-rose-500">*</span></th>
                                            <th class="px-2.5 py-3 w-20 text-center font-outfit">Boxes <span class="text-rose-500">*</span></th>
                                            <th class="px-2.5 py-3 w-24 text-center font-outfit">Box Wt (kg) <span class="text-rose-500">*</span></th>
                                            <th class="px-2.5 py-3 w-24 text-center font-outfit">Empty Wt (kg) <span class="text-rose-500">*</span></th>
                                            <th class="px-2.5 py-3 w-28 text-center font-outfit">Net Bird Wt</th>
                                            <th class="px-3.5 py-3 w-32 text-right font-outfit">Line Total (Rs)</th>
                                            <th class="px-2.5 py-3 w-28 font-outfit">Remarks</th>
                                            <th class="px-2 py-3 w-28 text-center font-outfit">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60 font-medium">
                                        <template x-for="(dealer, idx) in bulkDealers" :key="dealer.uid">
                                            <tr x-show="isDealerMatched(dealer)" :class="isDealerRowIncomplete(dealer) ? 'bg-rose-50/50 dark:bg-rose-950/20 border-l-[3px] border-l-rose-500 ring-1 ring-inset ring-rose-500/30' : (isDealerRowActive(dealer) ? 'bg-emerald-50/60 dark:bg-emerald-950/20 border-l-[3px] border-l-emerald-500 ring-1 ring-inset ring-emerald-500/20' : 'hover:bg-zinc-50/80 dark:hover:bg-zinc-900/50')" class="transition-colors">
                                                {{-- Hidden Dealer ID --}}
                                                <input type="hidden" :name="'dealers[' + dealer.uid + '][dealer_id]'" :value="dealer.dealer_id || dealer.id">

                                                {{-- Order # (Hidden from modal table view as requested) --}}
                                                <td class="hidden" style="display: none;">
                                                    <input type="hidden"
                                                        :name="'dealers[' + dealer.uid + '][sort_order]'"
                                                        :value="dealer.sort_order">
                                                </td>

                                                {{-- Dealer Name & Badges --}}
                                                <td class="px-3.5 py-2.5">
                                                    <div class="flex items-center gap-2">
                                                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :class="isDealerRowIncomplete(dealer) ? 'bg-rose-500 ring-4 ring-rose-500/20' : (isDealerRowActive(dealer) ? 'bg-emerald-500 ring-4 ring-emerald-500/20 animate-pulse' : 'bg-zinc-300 dark:bg-zinc-700')"></span>
                                                        <div class="truncate">
                                                            <div class="flex items-center gap-1.5">
                                                                <p class="font-bold text-zinc-900 dark:text-zinc-100 text-xs truncate leading-tight font-outfit" x-text="dealer.firm_name"></p>
                                                                <span x-show="dealer.is_extra" class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-blue-100 text-blue-800 dark:bg-blue-950/80 dark:text-blue-300 whitespace-nowrap">
                                                                    Load #<span x-text="dealer.load_index || 2"></span>
                                                                </span>
                                                            </div>
                                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                                <p class="text-[10px] text-zinc-400 dark:text-zinc-500 truncate" x-text="dealer.location || dealer.route || 'No Route'"></p>
                                                                <span x-show="isDealerRowIncomplete(dealer)" class="text-[9px] font-extrabold text-rose-600 dark:text-rose-400 bg-rose-100 dark:bg-rose-950/80 px-1.5 py-0.2 rounded" x-text="'Missing: ' + getDealerIncompleteReason(dealer)"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>

                                                {{-- Customer Rate --}}
                                                <td class="px-2.5 py-2">
                                                    <div>
                                                        <input type="number" step="0.01" min="0" :name="'dealers[' + dealer.uid + '][customer_rate]'" x-model="dealer.customer_rate" placeholder="0.00" :class="{'!border-rose-400 !ring-2 !ring-rose-400/20 bg-rose-50/30': isDealerRowIncomplete(dealer) && (!dealer.customer_rate || dealer.customer_rate <= 0)}" class="w-full px-2.5 py-1.5 text-xs font-jetbrains font-bold rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 shadow-2xs transition-all">
                                                        <template x-if="dealer.customer_rate > 0 && bulkActiveVendorRate > 0">
                                                            <div class="mt-1 flex items-center gap-1">
                                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold font-jetbrains inline-block" :class="(dealer.customer_rate - bulkActiveVendorRate) >= 0 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300'" x-text="((dealer.customer_rate - bulkActiveVendorRate) >= 0 ? '+' : '-') + 'Rs ' + Math.abs(dealer.customer_rate - bulkActiveVendorRate).toFixed(2)"></span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </td>

                                                {{-- Boxes --}}
                                                <td class="px-2.5 py-2 text-center">
                                                    <input type="number" min="0" :name="'dealers[' + dealer.uid + '][no_of_boxes]'" x-model="dealer.no_of_boxes" placeholder="0" :class="{'!border-rose-400 !ring-2 !ring-rose-400/20 bg-rose-50/30': isDealerRowIncomplete(dealer) && (!dealer.no_of_boxes || dealer.no_of_boxes <= 0)}" class="w-full px-2 py-1.5 text-xs font-jetbrains font-bold text-center rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 shadow-2xs transition-all">
                                                </td>

                                                {{-- Box Weight --}}
                                                <td class="px-2.5 py-2 text-center">
                                                    <input type="number" step="0.01" min="0" :name="'dealers[' + dealer.uid + '][box_weight]'" x-model="dealer.box_weight" placeholder="0.00" :class="{'!border-rose-400 !ring-2 !ring-rose-400/20 bg-rose-50/30': isDealerRowIncomplete(dealer) && (!dealer.box_weight || dealer.box_weight <= 0)}" class="w-full px-2 py-1.5 text-xs font-jetbrains font-medium text-center rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 shadow-2xs transition-all">
                                                </td>

                                                {{-- Empty Weight --}}
                                                <td class="px-2.5 py-2 text-center">
                                                    <input type="number" step="0.01" min="0" :name="'dealers[' + dealer.uid + '][empty_weight]'" x-model="dealer.empty_weight" placeholder="0.00" :class="{'!border-rose-400 !ring-2 !ring-rose-400/20 bg-rose-50/30': isDealerRowIncomplete(dealer) && (dealer.empty_weight === '' || parseFloat(dealer.empty_weight) >= parseFloat(dealer.box_weight))}" class="w-full px-2 py-1.5 text-xs font-jetbrains font-medium text-center rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 shadow-2xs transition-all">
                                                </td>

                                                {{-- Net Bird Weight (Calculated) --}}
                                                <td class="px-2.5 py-2 text-center">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 font-jetbrains font-extrabold text-xs" x-text="getDealerBirdWeight(dealer).toFixed(2) + ' kg'"></span>
                                                </td>

                                                {{-- Line Total Amount (Calculated) --}}
                                                <td class="px-3.5 py-2 text-right">
                                                    <span class="font-jetbrains font-black text-xs" :class="isDealerRowActive(dealer) ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400'" x-text="'Rs ' + Math.round(getDealerAmount(dealer)).toLocaleString('en-IN')"></span>
                                                </td>

                                                {{-- Remarks --}}
                                                <td class="px-2.5 py-2">
                                                    <input type="text" :name="'dealers[' + dealer.uid + '][remarks]'" x-model="dealer.remarks" placeholder="Notes" class="w-full px-2 py-1.5 text-xs rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 shadow-2xs transition-all">
                                                </td>

                                                {{-- Actions: + Add Load & Clear / Delete --}}
                                                <td class="px-2 py-2 text-center whitespace-nowrap">
                                                    <div class="inline-flex items-center gap-1 justify-center">
                                                        <button type="button" @click="addExtraDealerLoad(dealer)" class="px-2 py-1 rounded-lg text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-200/60 dark:border-emerald-800/60 transition-all shadow-2xs flex items-center gap-0.5" title="Add another load for this dealer">
                                                            <span class="material-symbols-rounded text-xs">add</span>
                                                            <span>Load</span>
                                                        </button>

                                                        <template x-if="dealer.is_extra">
                                                            <button type="button" @click="removeDealerRow(dealer)" class="p-1 rounded-lg text-rose-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors" title="Remove this extra load">
                                                                <span class="material-symbols-rounded text-sm">delete</span>
                                                            </button>
                                                        </template>
                                                        <template x-if="!dealer.is_extra">
                                                            <button type="button" @click="clearDealerRow(dealer.uid)" class="p-1 rounded-lg text-zinc-300 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors" title="Clear this dealer row">
                                                                <span class="material-symbols-rounded text-sm">delete_outline</span>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>

                                        {{-- Empty Search Match State --}}
                                        <tr x-show="bulkDealers.filter(d => isDealerMatched(d)).length === 0" x-cloak>
                                            <td colspan="9" class="py-12 text-center text-zinc-400 dark:text-zinc-600">
                                                <div class="flex flex-col items-center justify-center gap-2">
                                                    <span class="material-symbols-rounded text-3xl text-zinc-300 dark:text-zinc-700">search_off</span>
                                                    <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">No dealers matching your search term</p>
                                                    <button type="button" @click="bulkDealerSearch = ''" class="text-xs text-emerald-600 hover:underline font-bold">Clear search filter</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Sticky Summary Bar & Footer Actions --}}
                    <div class="mt-3 pt-3 border-t border-zinc-200/80 dark:border-zinc-800 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3.5 bg-zinc-900 dark:bg-zinc-950 text-white rounded-2xl p-3 shadow-xl">
                        {{-- Live Metric Cards --}}
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 text-xs flex-1">
                            <div class="p-2 px-3 rounded-xl bg-zinc-800/80 border border-zinc-700/50 flex flex-col justify-center">
                                <p class="text-[9px] font-extrabold text-zinc-400 uppercase tracking-wider flex items-center gap-1">
                                    <span class="material-symbols-rounded text-[11px] text-zinc-400">groups</span>
                                    Dealers Filled
                                </p>
                                <p class="font-jetbrains font-black text-sm text-white mt-0.5">
                                    <span x-text="bulkActiveEntriesCount"></span> <span class="text-zinc-400 font-normal text-[10px]">/ <span x-text="bulkDealers.length"></span></span>
                                </p>
                            </div>

                            <div class="p-2 px-3 rounded-xl bg-zinc-800/80 border border-zinc-700/50 flex flex-col justify-center">
                                <p class="text-[9px] font-extrabold text-amber-400 uppercase tracking-wider flex items-center gap-1">
                                    <span class="material-symbols-rounded text-[11px]">inventory_2</span>
                                    Total Boxes
                                </p>
                                <p class="font-jetbrains font-black text-sm text-amber-400 mt-0.5" x-text="bulkTotalBoxes"></p>
                            </div>

                            <div class="p-2 px-3 rounded-xl bg-zinc-800/80 border border-zinc-700/50 flex flex-col justify-center">
                                <p class="text-[9px] font-extrabold text-cyan-400 uppercase tracking-wider flex items-center gap-1">
                                    <span class="material-symbols-rounded text-[11px]">scale</span>
                                    Total Net Wt
                                </p>
                                <p class="font-jetbrains font-black text-sm text-cyan-400 mt-0.5" x-text="bulkTotalBirdWeight.toFixed(2) + ' kg'"></p>
                            </div>

                            <div class="p-2 px-3 rounded-xl bg-zinc-800/80 border border-zinc-700/50 flex flex-col justify-center">
                                <p class="text-[9px] font-extrabold text-emerald-400 uppercase tracking-wider flex items-center gap-1">
                                    <span class="material-symbols-rounded text-[11px]">payments</span>
                                    Dealer Sales
                                </p>
                                <p class="font-jetbrains font-black text-sm text-emerald-400 mt-0.5" x-text="'Rs ' + Math.round(bulkTotalDealerAmount).toLocaleString('en-IN')"></p>
                            </div>

                            <div class="p-2 px-3 rounded-xl bg-zinc-800/80 border border-zinc-700/50 flex flex-col justify-center">
                                <p class="text-[9px] font-extrabold text-zinc-400 uppercase tracking-wider flex items-center gap-1">
                                    <span class="material-symbols-rounded text-[11px]">trending_up</span>
                                    Est. Margin
                                </p>
                                <p class="font-jetbrains font-black text-sm mt-0.5" :class="bulkGrossMargin >= 0 ? 'text-emerald-400' : 'text-rose-400'" x-text="(bulkGrossMargin >= 0 ? '+' : '') + 'Rs ' + Math.round(bulkGrossMargin).toLocaleString('en-IN')"></p>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-center justify-end gap-2.5">
                            <x-button type="button" variant="outline" class="!bg-zinc-800 !text-zinc-200 !border-zinc-700 hover:!bg-zinc-700 text-xs px-4 py-2.5" x-on:click="$dispatch('close-modal', 'bulk-load-modal')">
                                Cancel
                            </x-button>
                            <x-button type="button" @click="submitBulkLoadForm($event)" variant="primary" icon="save" class="px-6 py-2.5 !bg-gradient-to-r !from-emerald-500 !to-teal-600 hover:!from-emerald-600 hover:!to-teal-700 text-white shadow-lg shadow-emerald-500/25 font-bold transition-all active:scale-[0.98]">
                                <span x-text="bulkActiveEntriesCount > 0 ? ('Save ' + bulkActiveEntriesCount + ' Load ' + (bulkActiveEntriesCount > 1 ? 'Entries' : 'Entry')) : 'Save Load Entries'"></span>
                            </x-button>
                        </div>
                    </div>
                </form>
            </x-modal>
        </template>
</div>

<script>
    function dayLoadBillingData() {
        return {
            // Bulk Load Entry State
            bulkValidationAttempted: false,
            bulkVendorId: '',
            bulkBatchId: '',
            bulkBillingDate: '{{ $date }}',
            bulkPaperRate: '',
            bulkBillingRate: '',
            bulkRateMode: 'margin', // 'margin' or 'direct'
            bulkMarginOffset: 5,
            bulkGlobalCustomerRate: '',
            bulkVendorAdvanceId: '',
            bulkApplyAdvanceAmount: '',
            bulkDealerSearch: '',
            entryMode: 'bulk', // 'bulk' or 'single'
            advancesByVendor: {{ Js::from($activeAdvancesByVendor ?? []) }},
            existingRatesByVendor: {{ Js::from($existingRatesByVendor ?? []) }},
            bulkDealers: {{ Js::from($dealers->map(fn($d) => [
                'id' => $d->id,
                'dealer_id' => $d->id,
                'uid' => 'd_' . $d->id,
                'sort_order' => $d->sort_order !== null ? (int) $d->sort_order : '',
                'firm_name' => $d->firm_name,
                'location' => $d->location ?? '',
                'route' => $d->routeRelation->name ?? ($d->route ?? ''),
                'customer_rate' => '',
                'no_of_boxes' => '',
                'box_weight' => '',
                'empty_weight' => '',
                'farm_weight' => '',
                'remarks' => '',
                'is_extra' => false,
                'load_index' => 1,
            ])) }},

            fetchVendorRates() {
                if (!this.bulkVendorId || !this.bulkBillingDate) {
                    this.bulkPaperRate = '';
                    this.bulkBillingRate = '';
                    this.bulkDealers.forEach(d => { d.customer_rate = ''; });
                    return;
                }
                fetch(`{{ route('billing.day-load.get-rates') }}?vendor_id=${this.bulkVendorId}&date=${this.bulkBillingDate}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.found) {
                            this.bulkPaperRate = data.paper_rate || '';
                            this.bulkBillingRate = data.billing_rate || '';
                            if (data.dealer_rates) {
                                this.bulkDealers.forEach(d => {
                                    let did = d.dealer_id || d.id;
                                    if (data.dealer_rates[did] !== undefined) {
                                        d.customer_rate = data.dealer_rates[did];
                                    }
                                });
                            }
                        } else {
                            this.bulkPaperRate = '';
                            this.bulkBillingRate = '';
                            this.bulkDealers.forEach(d => { d.customer_rate = ''; });
                        }
                    })
                    .catch(() => {
                        this.bulkPaperRate = '';
                        this.bulkBillingRate = '';
                        this.bulkDealers.forEach(d => { d.customer_rate = ''; });
                    });
            },

            init() {
                this.$watch('bulkVendorId', () => this.fetchVendorRates());
                this.$watch('bulkBillingDate', () => this.fetchVendorRates());
            },

            get bulkDayOfWeek() {
                if (!this.bulkBillingDate) return '';
                const parts = this.bulkBillingDate.split('-');
                if (parts.length === 3) {
                    const d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
                    return ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][d.getDay()] || '';
                }
                return '';
            },

            get bulkActiveVendorRate() {
                let br = parseFloat(this.bulkBillingRate);
                let pr = parseFloat(this.bulkPaperRate);
                if (!isNaN(br) && br > 0) return br;
                if (!isNaN(pr) && pr > 0) return pr;
                return 0;
            },

            get computedMarginRate() {
                let base = parseFloat(this.bulkPaperRate) || parseFloat(this.bulkBillingRate) || 0;
                let offset = parseFloat(this.bulkMarginOffset) || 0;
                return (base + offset).toFixed(2);
            },

            applyMarginFormula(offset = null) {
                if (offset !== null) {
                    this.bulkMarginOffset = offset;
                }
                let base = parseFloat(this.bulkPaperRate) || parseFloat(this.bulkBillingRate) || 0;
                let off = parseFloat(this.bulkMarginOffset) || 0;
                let rate = base + off;
                if (rate > 0) {
                    this.bulkGlobalCustomerRate = rate.toFixed(2);
                    this.bulkDealers.forEach(d => {
                        d.customer_rate = rate.toFixed(2);
                    });
                }
            },

            get bulkAvailableAdvance() {
                if (!this.bulkVendorId || !this.advancesByVendor[this.bulkVendorId]) return null;
                return this.advancesByVendor[this.bulkVendorId];
            },

            fillBulkMaxAdvance() {
                if (!this.bulkAvailableAdvance) return;
                let maxAdv = this.bulkAvailableAdvance.total_remaining || 0;
                let cost = this.bulkTotalVendorCost;
                let fillAmount = (cost > 0 && cost < maxAdv) ? cost : maxAdv;
                this.bulkApplyAdvanceAmount = fillAmount > 0 ? fillAmount.toFixed(2) : '';
                if (this.bulkAvailableAdvance.advances && this.bulkAvailableAdvance.advances.length) {
                    this.bulkVendorAdvanceId = this.bulkAvailableAdvance.advances[0].id;
                }
            },

            applyGlobalCustomerRate() {
                let rate = parseFloat(this.bulkGlobalCustomerRate);
                if (!isNaN(rate) && rate > 0) {
                    this.bulkDealers.forEach(d => {
                        d.customer_rate = rate.toFixed(2);
                    });
                }
            },

            isDealerMatched(d) {
                if (!this.bulkDealerSearch) return true;
                let q = this.bulkDealerSearch.trim().toLowerCase();
                return (d.firm_name && d.firm_name.toLowerCase().includes(q)) ||
                       (d.location && d.location.toLowerCase().includes(q)) ||
                       (d.route && d.route.toLowerCase().includes(q));
            },

            hasDealerAnyLoadData(d) {
                let boxes = (d.no_of_boxes !== '' && d.no_of_boxes !== null) ? parseFloat(d.no_of_boxes) : 0;
                let boxWeight = (d.box_weight !== '' && d.box_weight !== null) ? parseFloat(d.box_weight) : 0;
                let emptyWeight = (d.empty_weight !== '' && d.empty_weight !== null) ? parseFloat(d.empty_weight) : 0;
                let remarks = (d.remarks || '').trim();
                return boxes > 0 || boxWeight > 0 || emptyWeight > 0 || remarks.length > 0;
            },

            isDealerRowComplete(d) {
                let boxes = parseFloat(d.no_of_boxes);
                let boxWeight = parseFloat(d.box_weight);
                let emptyWeight = (d.empty_weight !== '' && d.empty_weight !== null) ? parseFloat(d.empty_weight) : 0;
                let rate = parseFloat(d.customer_rate);
                return !isNaN(boxes) && boxes > 0 && 
                       !isNaN(boxWeight) && boxWeight > 0 && 
                       !isNaN(rate) && rate > 0 &&
                       !isNaN(emptyWeight) && emptyWeight >= 0 && emptyWeight < boxWeight;
            },

            isDealerRowIncomplete(d) {
                if (!this.hasDealerAnyLoadData(d)) return false;
                return !this.isDealerRowComplete(d);
            },

            isDealerRowActive(d) {
                return this.isDealerRowComplete(d);
            },

            getDealerIncompleteReason(d) {
                if (!this.hasDealerAnyLoadData(d)) return '';
                let missing = [];
                if (!d.customer_rate || parseFloat(d.customer_rate) <= 0) missing.push('Rate');
                if (!d.no_of_boxes || parseFloat(d.no_of_boxes) <= 0) missing.push('Boxes');
                if (!d.box_weight || parseFloat(d.box_weight) <= 0) missing.push('Box Wt');
                if (d.empty_weight === '' || d.empty_weight === null || isNaN(parseFloat(d.empty_weight))) missing.push('Empty Wt');
                if (parseFloat(d.empty_weight) >= parseFloat(d.box_weight) && parseFloat(d.box_weight) > 0) missing.push('Empty Wt >= Box Wt');
                return missing.join(', ');
            },

            getDealerBirdWeight(d) {
                let bw = parseFloat(d.box_weight) || 0;
                let ew = parseFloat(d.empty_weight) || 0;
                return Math.max(0, bw - ew);
            },

            getDealerAmount(d) {
                let birdWeight = this.getDealerBirdWeight(d);
                let rate = parseFloat(d.customer_rate) || 0;
                return birdWeight * rate;
            },

            addExtraDealerLoad(dealer) {
                let dId = dealer.dealer_id || dealer.id;
                let existingLoads = this.bulkDealers.filter(item => (item.dealer_id || item.id) === dId);
                let nextLoadIndex = existingLoads.length + 1;

                // Calculate next sort_order based on existing max
                let maxSort = this.bulkDealers.reduce((max, item) => Math.max(max, parseInt(item.sort_order) || 0), 0);
                let nextSort = maxSort > 0 ? maxSort + 1 : '';

                let newRow = {
                    id: dId,
                    dealer_id: dId,
                    uid: 'd_' + dId + '_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
                    sort_order: nextSort,
                    firm_name: dealer.firm_name,
                    location: dealer.location || '',
                    route: dealer.route || '',
                    customer_rate: dealer.customer_rate || '',
                    no_of_boxes: '',
                    box_weight: '',
                    empty_weight: '',
                    farm_weight: '',
                    remarks: '',
                    is_extra: true,
                    load_index: nextLoadIndex,
                };

                // Find index of the last row for this dealer
                let insertIdx = -1;
                for (let i = this.bulkDealers.length - 1; i >= 0; i--) {
                    if ((this.bulkDealers[i].dealer_id || this.bulkDealers[i].id) === dId) {
                        insertIdx = i;
                        break;
                    }
                }

                if (insertIdx !== -1) {
                    this.bulkDealers.splice(insertIdx + 1, 0, newRow);
                } else {
                    this.bulkDealers.push(newRow);
                }
            },

            removeDealerRow(dealer) {
                this.bulkDealers = this.bulkDealers.filter(item => item.uid !== dealer.uid);
            },

            clearDealerRow(uidOrId) {
                let d = this.bulkDealers.find(item => item.uid === uidOrId || item.id === uidOrId);
                if (d) {
                    d.customer_rate = '';
                    d.no_of_boxes = '';
                    d.box_weight = '';
                    d.empty_weight = '';
                    d.farm_weight = '';
                    d.remarks = '';
                }
            },

            clearAllDealers() {
                if (confirm('Clear all entered dealer data in this load sheet?')) {
                    this.bulkDealers = this.bulkDealers.filter(d => !d.is_extra);
                    this.bulkDealers.forEach(d => {
                        d.customer_rate = '';
                        d.no_of_boxes = '';
                        d.box_weight = '';
                        d.empty_weight = '';
                        d.farm_weight = '';
                        d.remarks = '';
                    });
                    this.bulkGlobalCustomerRate = '';
                }
            },

            get bulkActiveEntriesCount() {
                return this.bulkDealers.filter(d => this.isDealerRowActive(d)).length;
            },

            get bulkTotalBoxes() {
                return this.bulkDealers
                    .filter(d => this.isDealerRowActive(d))
                    .reduce((sum, d) => sum + (parseInt(d.no_of_boxes) || 0), 0);
            },

            get bulkTotalBirdWeight() {
                return this.bulkDealers
                    .filter(d => this.isDealerRowActive(d))
                    .reduce((sum, d) => sum + this.getDealerBirdWeight(d), 0);
            },

            get bulkTotalDealerAmount() {
                return this.bulkDealers
                    .filter(d => this.isDealerRowActive(d))
                    .reduce((sum, d) => sum + this.getDealerAmount(d), 0);
            },

            get bulkTotalVendorCost() {
                return this.bulkTotalBirdWeight * this.bulkActiveVendorRate;
            },

            get bulkGrossMargin() {
                return this.bulkTotalDealerAmount - this.bulkTotalVendorCost;
            },

            openBulkLoadModal(requireValidation = false, vendorId = null) {
                if (typeof requireValidation === 'number' || (typeof requireValidation === 'string' && !isNaN(requireValidation) && requireValidation !== 'true' && requireValidation !== 'false')) {
                    vendorId = requireValidation;
                    requireValidation = false;
                }
                if (vendorId) {
                    this.bulkVendorId = vendorId;
                }

                if (requireValidation) {
                    this.bulkValidationAttempted = true;
                    if (!this.bulkVendorId) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Vendor Required',
                                text: 'Please select a Vendor / Company to continue.',
                                confirmButtonColor: '#059669',
                            });
                        } else {
                            alert('Please select a Vendor / Company to continue.');
                        }
                        return false;
                    }

                    if (!this.bulkBillingDate) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Billing Date Required',
                                text: 'Please choose a valid Billing Date.',
                                confirmButtonColor: '#059669',
                            });
                        } else {
                            alert('Please choose a valid Billing Date.');
                        }
                        return false;
                    }

                    if (!this.bulkPaperRate || parseFloat(this.bulkPaperRate) <= 0) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Paper Rate Required',
                                text: 'Please enter a valid Paper Rate (Rs).',
                                confirmButtonColor: '#059669',
                            });
                        } else {
                            alert('Please enter a valid Paper Rate (Rs).');
                        }
                        return false;
                    }
                }

                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'bulk-load-modal' }));
            },

            submitBulkLoadForm(event) {
                if (event) event.preventDefault();
                this.bulkValidationAttempted = true;

                // 1. Check Master Parameters
                if (!this.bulkVendorId) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Vendor Required',
                            text: 'Please select a Vendor / Company before saving.',
                            confirmButtonColor: '#059669',
                        });
                    } else {
                        alert('Please select a Vendor / Company before saving.');
                    }
                    return false;
                }

                if (!this.bulkBillingDate) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Billing Date Required',
                            text: 'Please choose a valid Billing Date.',
                            confirmButtonColor: '#059669',
                        });
                    } else {
                        alert('Please choose a valid Billing Date.');
                    }
                    return false;
                }

                if (!this.bulkPaperRate || parseFloat(this.bulkPaperRate) <= 0) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Paper Rate Required',
                            text: 'Please enter a valid Paper Rate (Rs).',
                            confirmButtonColor: '#059669',
                        });
                    } else {
                        alert('Please enter a valid Paper Rate (Rs).');
                    }
                    return false;
                }

                // Remove extra dealer rows that are completely untouched
                this.bulkDealers = this.bulkDealers.filter(d => !d.is_extra || this.hasDealerAnyLoadData(d));

                // 2. Check for partially filled / incomplete dealer rows
                let incompleteDealers = this.bulkDealers.filter(d => this.isDealerRowIncomplete(d));
                if (incompleteDealers.length > 0) {
                    let first = incompleteDealers[0];
                    let reason = this.getDealerIncompleteReason(first);
                    let dealerTitle = first.firm_name + (first.is_extra ? ' (Load #' + (first.load_index || 2) + ')' : '');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Incomplete Dealer Row',
                            html: `<strong>${dealerTitle}</strong> is partially filled.<br><span class="text-rose-600 font-bold">Missing: ${reason}</span>.<br><small class="text-zinc-500">Please complete the required fields or remove/clear this row.</small>`,
                            confirmButtonColor: '#059669',
                        });
                    } else {
                        alert(`Dealer "${dealerTitle}" is partially filled. Missing: ${reason}. Please complete or clear this row.`);
                    }
                    return false;
                }

                // 3. Check if at least 1 dealer is completely filled
                let completeDealers = this.bulkDealers.filter(d => this.isDealerRowComplete(d));
                if (completeDealers.length === 0) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'info',
                            title: 'No Dealer Entries Filled',
                            text: 'Please enter load details (Boxes, Box Weight, Customer Rate) for at least one dealer.',
                            confirmButtonColor: '#059669',
                        });
                    } else {
                        alert('Please enter load details for at least one dealer.');
                    }
                    return false;
                }

                // 4. Submit form
                let form = document.getElementById('bulk-load-form');
                if (form) {
                    form.submit();
                }
            },

            // Single / Edit / Transfer / Payments state
            transferSourceVendor: '',
            transferSourceDealer: '',
            transferSourceId: 0,
            transferSourceBoxes: 0,
            transferSourceWeight: 0,
            transferSourceVendorId: 0,
            transferTargetVendorId: 0,
            transferSourceCustomerRate: 0,
            transferTargetCustomerRate: 0,
            transferBatchId: 0,
            transferDate: '',
            transferMaxWeight: 0,
            transferWeight: 0,
            transferFormAction: '',
            editEntryId: 0,
            editSortOrder: '',
            editFormAction: '',
            editVendorId: 0,
            editDealerId: 0,
            editPaperRate: 0,
            editBillingRate: 0,
            editCustomerRate: 0,
            editNoOfBoxes: 0,
            editBoxWeight: 0,
            editEmptyWeight: 0,
            editFarmWeight: '',
            editRemarks: '',
            dpEntryId: 0,
            dpFormAction: '',
            dpEntryVendor: '',
            dpEntryDealer: '',
            dpEntryIncome: 0,
            dpEntryCollected: 0,
            dpCashAmount: 0,
            dpBankAmount: 0,
            dpBankTransferType: '',
            dpDate: '{{ $date }}',
            dpMode: 'Cash',
            dpRefNo: '',
            dpNotes: '',
            vpEntryId: 0,
            vpFormAction: '',
            vpEntryVendor: '',
            vpEntryDealer: '',
            vpEntryCost: 0,
            vpEntryPaid: 0,
            vpCashAmount: 0,
            vpBankAmount: 0,
            vpBankTransferType: '',
            vpDate: '{{ $date }}',
            vpMode: 'Cash',
            vpRefNo: '',
            vpNotes: '',
            lsEntriesByDealer: {{ Js::from($lsEntriesByDealer) }},
            lsDealerId: 0,
            lsEntries: [],
            lsAllocations: {},
            lsAllocSum: 0,
            lsCashAmount: 0,
            lsBankAmount: 0,
            lsTotalLump: 0,
            lsDate: '{{ $date }}',
            lsMode: 'Cash',
            lsBankTransferType: '',
            lsRefNo: '',
            lsNotes: '',
            initLsDealer() {
                this.lsEntries = this.lsEntriesByDealer[this.lsDealerId] || [];
                this.lsAllocations = {};
                this.lsEntries.forEach(e => { this.lsAllocations[e.id] = 0; });
                this.recalcAllocSum();
            },
            recalcAllocSum() {
                let sum = 0;
                Object.values(this.lsAllocations).forEach(v => { sum += parseFloat(v) || 0; });
                this.lsAllocSum = Math.round(sum * 100) / 100;
            }
        };
    }
</script>
@endsection
