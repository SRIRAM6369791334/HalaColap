@extends('layouts.app')
@section('title', 'Vendor Master')

@section('content')
<div class="space-y-6">
    <x-page-header 
        title="Vendor Master" 
        subtitle="Directory of MSR BROILERS bird suppliers, farm vendors, and logistics partners"
    >
        <x-slot:actions>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="$dispatch('open-modal', 'record-vendor-advance-modal')" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white shadow-sm transition-all">
                    <span class="material-symbols-rounded text-[18px]">local_shipping</span>
                    + Record Advance
                </button>
                @can('create vendors')
                    <x-button href="{{ route('masters.vendors.create') }}" variant="primary" icon="add">
                        Register Vendor
                    </x-button>
                @endcan
            </div>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <x-stat-card title="Total Suppliers" value="{{ $totalVendors }}" icon="inventory_2" color="teal" />
        <x-stat-card title="Outstanding Payable" value="{{ number_format($totalPayable, 0) }}" icon="warning" color="rose" prefix="Rs " subtitle="Total vendor dues" />
        <x-stat-card title="Advances & Credits" value="{{ number_format($totalAdvanceBalance, 0) }}" icon="local_shipping" color="amber" prefix="Rs " subtitle="Unadjusted / Excess" />
        <x-stat-card title="Active Accounts" value="{{ $activeVendorsCount }}" icon="account_balance" color="purple" subtitle="With dues" />
        <x-stat-card title="GST Registered" value="{{ $gstRegistered }}" icon="verified" color="blue" />
    </div>

    {{-- Main Vendors Data Table Card --}}
    <x-card padding="p-0">
        <div class="p-5 flex flex-wrap gap-4 items-center justify-between relative z-10 border-b border-white/40">
            <form action="{{ route('masters.vendors.index') }}" method="GET" class="flex flex-wrap gap-4 items-center w-full lg:w-auto">
                <div class="w-full sm:w-64">
                    <x-search name="search" value="{{ request('search') }}" placeholder="Search firm, contact..." />
                </div>
                
                <div class="w-full md:w-64">
                    <x-form.select 
                        name="route" 
                        :options="['' => 'All Routes'] + collect($routes)->mapWithKeys(fn($rt) => [$rt => $rt])->toArray()" 
                        :selected="request('route')" 
                        onchange="this.form.submit()" 
                    />
                </div>

                @if(request('search') || request('route'))
                    <x-button href="{{ route('masters.vendors.index') }}" variant="secondary" size="md">Clear</x-button>
                @endif
            </form>
        </div>

        <x-data-table :headers="['Firm & Location', 'Point of Contact', 'Route', 'GSTIN', 'Advance / Credit', 'Outstanding Payable', 'Actions']">
            @forelse($vendors as $vendor)
                <tr class="hover:bg-white/80 dark:hover:bg-zinc-800/50 transition-all duration-300 group">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <x-avatar name="{{ $vendor->firm_name }}" size="sm" />
                            <div>
                                <a href="{{ route('masters.vendors.show', $vendor) }}" class="font-medium text-zinc-900 dark:text-zinc-100 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                    {{ $vendor->firm_name }}
                                </a>
                                <div class="text-xs text-zinc-500">{{ $vendor->location ?: 'No Location Specified' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $vendor->contact_person ?: 'No contact person' }}</div>
                        <div class="text-xs text-zinc-500">{{ $vendor->phone }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $vendor->route ?: 'General Sector' }}</span>
                    </td>
                    <td class="px-6 py-4">
                        @if($vendor->gst_number)
                            <x-badge variant="success" class="font-jetbrains">{{ $vendor->gst_number }}</x-badge>
                        @else
                            <x-badge variant="secondary">UNREGISTERED</x-badge>
                        @endif
                    </td>

                    {{-- Advance Balance Column --}}
                    {{-- Advance Balance Column --}}
                    <td class="px-6 py-4 font-jetbrains">
                        @php
                            $displayAdv = $vendor->outstanding_balance < 0 ? abs((float) $vendor->outstanding_balance) : 0;
                        @endphp
                        @if($displayAdv > 0)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 font-bold border border-emerald-100 dark:border-emerald-500/20 text-xs">
                                <span class="material-symbols-rounded text-[14px] mr-1">arrow_downward</span>
                                Rs {{ number_format($displayAdv, 2) }}
                            </span>
                        @else
                            <span class="text-zinc-400 dark:text-zinc-500 font-medium text-xs">Rs 0.00</span>
                        @endif
                    </td>

                    {{-- Outstanding Payable Column --}}
                    <td class="px-6 py-4 font-jetbrains">
                        @if($vendor->outstanding_balance > 0)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-rose-50 text-rose-500 dark:bg-rose-500/10 dark:text-rose-400 font-bold border border-rose-100 dark:border-rose-500/20 text-xs">
                                Rs {{ number_format($vendor->outstanding_balance, 2) }}
                            </span>
                        @elseif($vendor->outstanding_balance < 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 font-medium text-xs">
                                Advance
                            </span>
                        @else
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold text-xs">Rs 0.00</span>
                        @endif
                    </td>

                    {{-- Actions Column --}}
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-1">
                            {{-- Pay Advance to this vendor --}}
                            <button type="button" @click="$dispatch('set-vendor', {{ $vendor->id }}); $dispatch('open-modal', 'record-vendor-advance-modal')" class="p-1.5 text-amber-500 hover:text-amber-700 hover:bg-amber-50 dark:hover:bg-amber-950/40 rounded-lg transition-colors" title="Issue Advance to {{ $vendor->firm_name }}">
                                <span class="material-symbols-rounded text-[18px]">local_shipping</span>
                            </button>

                            {{-- Ledger & Payments --}}
                            <a href="{{ route('payments.vendors.ledger', $vendor) }}" class="p-1.5 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 rounded-lg transition-colors" title="Ledger & Payments">
                                <span class="material-symbols-rounded text-[18px]">account_balance_wallet</span>
                            </a>

                            {{-- Download History PDF --}}
                            <a href="{{ route('masters.vendors.history-pdf', $vendor) }}" class="p-1.5 text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors" title="Download History">
                                <span class="material-symbols-rounded text-[18px]">picture_as_pdf</span>
                            </a>

                            @can('edit vendors')
                                <a href="{{ route('masters.vendors.edit', $vendor) }}" class="p-1.5 text-zinc-400 hover:text-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors" title="Edit">
                                    <span class="material-symbols-rounded text-[18px]">edit</span>
                                </a>
                            @endcan

                            @can('delete vendors')
                                <form action="{{ route('masters.vendors.destroy', $vendor) }}" method="POST" class="inline" onsubmit="return confirm('Delete {{ $vendor->firm_name }}?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors" title="Delete">
                                        <span class="material-symbols-rounded text-[18px]">delete</span>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <x-slot:empty>
                    <x-empty-state 
                        icon="inventory_2" 
                        title="No vendors found" 
                        description="Start by registering your first MSR BROILERS supply partner." 
                    />
                </x-slot:empty>
            @endforelse

            <x-slot:pagination>
                {{ $vendors->withQueryString()->links() }}
            </x-slot:pagination>
        </x-data-table>
    </x-card>
</div>

@push('modals')
{{-- Standardized Record Vendor Advance Modal with Image 2 Styling --}}
<x-modal name="record-vendor-advance-modal" title="Record Vendor Advance" subtitle="Deposit advance funds to supplier accounts across cash, bank, and capital pool" icon="local_shipping" maxWidth="720" :show="$errors->any()">
    <form id="record-vendor-advance-form" 
          action="{{ route('payments.vendors.advances.store') }}" 
          method="POST"
          x-data="{
              advVendorId: '{{ $allVendors->first()?->id ?? '' }}',
              advDate: '{{ now()->format('Y-m-d') }}',
              advCash: '',
              advBank: '',
              advInvestment: '',
              advMode: 'Cash',
              advBankType: '',
              advRef: '',
              advNotes: '',

              get totalAdvance() {
                  let c = parseFloat(this.advCash) || 0;
                  let b = parseFloat(this.advBank) || 0;
                  let i = parseFloat(this.advInvestment) || 0;
                  return (c + b + i).toFixed(2);
              },

              resetForm() {
                  this.advCash = '';
                  this.advBank = '';
                  this.advInvestment = '';
                  this.advRef = '';
                  this.advNotes = '';
              }
          }"
          x-on:set-vendor.window="advVendorId = $event.detail; resetForm()">
        @csrf

        {{-- Select Vendor & Advance Date in 2 columns --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-6">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 font-outfit mb-2">
                    Select Vendor <span class="text-rose-500">*</span>
                </label>
                <select name="vendor_id" x-model="advVendorId" required class="w-full rounded-2xl border-2 border-zinc-200 dark:border-zinc-700 bg-white/50 dark:bg-zinc-900/50 px-4 py-2.5 text-sm font-semibold focus:border-emerald-500 focus:ring-0">
                    @foreach($allVendors as $v)
                        <option value="{{ $v->id }}">{{ $v->firm_name }} ({{ $v->contact_person ?? 'Vendor' }})</option>
                    @endforeach
                </select>
            </div>
            <x-form.input type="date" name="date" label="Advance Date" required x-model="advDate" icon="calendar_month" />
        </div>

        {{-- Funding Source Split Container --}}
        <div class="mb-6 p-5 rounded-2xl bg-zinc-50/90 dark:bg-zinc-800/50 border border-zinc-200/80 dark:border-zinc-700/80 space-y-4">
            <div class="flex items-center justify-between border-b border-zinc-200/60 dark:border-zinc-700/60 pb-3">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-rounded text-amber-500 text-lg">account_balance_wallet</span>
                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">Funding Source Split</span>
                </div>
                <span class="text-xs font-bold text-zinc-500">
                    Total Advance: <strong class="font-jetbrains text-sm font-bold text-amber-600 dark:text-amber-400">Rs <span x-text="totalAdvance"></span></strong>
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                {{-- Cash Input --}}
                <div class="p-3.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300 flex items-center gap-1">
                            <span class="material-symbols-rounded text-emerald-500 text-base">payments</span> Cash
                        </span>
                        <button type="button" @click="advCash = '{{ $currentCashBalance }}'" class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase hover:underline">Max</button>
                    </div>
                    <div class="text-[10px] text-zinc-400 mb-2 font-mono">Avail: Rs {{ number_format($currentCashBalance, 2) }}</div>
                    <input type="number" step="0.01" min="0" name="cash_amount" x-model="advCash" placeholder="0.00" class="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/50 px-2.5 py-1.5 text-xs font-jetbrains font-bold focus:border-emerald-500 focus:ring-0">
                </div>

                {{-- Bank Input --}}
                <div class="p-3.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300 flex items-center gap-1">
                            <span class="material-symbols-rounded text-blue-500 text-base">account_balance</span> Bank
                        </span>
                        <button type="button" @click="advBank = '{{ $currentBankBalance }}'" class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase hover:underline">Max</button>
                    </div>
                    <div class="text-[10px] text-zinc-400 mb-2 font-mono">Avail: Rs {{ number_format($currentBankBalance, 2) }}</div>
                    <input type="number" step="0.01" min="0" name="bank_amount" x-model="advBank" placeholder="0.00" class="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/50 px-2.5 py-1.5 text-xs font-jetbrains font-bold focus:border-blue-500 focus:ring-0">
                </div>

                {{-- Investment Pool Input --}}
                <div class="p-3.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300 flex items-center gap-1">
                            <span class="material-symbols-rounded text-indigo-500 text-base">savings</span> Pool
                        </span>
                        <button type="button" @click="advInvestment = '{{ $currentInvestmentBalance }}'" class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase hover:underline">Max</button>
                    </div>
                    <div class="text-[10px] text-zinc-400 mb-2 font-mono">Avail: Rs {{ number_format($currentInvestmentBalance, 2) }}</div>
                    <input type="number" step="0.01" min="0" name="investment_amount" x-model="advInvestment" placeholder="0.00" class="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/50 px-2.5 py-1.5 text-xs font-jetbrains font-bold focus:border-indigo-500 focus:ring-0">
                </div>
            </div>
        </div>

        {{-- Dynamic Bank Channel when Bank is funded --}}
        <div x-show="parseFloat(advBank) > 0" x-cloak class="mb-4 p-3.5 rounded-xl bg-blue-50/60 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800">
            <x-form.select name="bank_transfer_type" label="Bank Channel / Transfer Mode" x-model="advBankType">
                <option value="UPI">UPI (GPay / PhonePe / Paytm)</option>
                <option value="NEFT">NEFT</option>
                <option value="IMPS">IMPS</option>
                <option value="RTGS">RTGS</option>
                <option value="Cheque">Cheque</option>
                <option value="Other">Other Bank Transfer</option>
            </x-form.select>
        </div>

        {{-- Reference & Notes in 2 columns --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-2">
            <x-form.input name="reference_number" label="Reference / UTR Number" x-model="advRef" placeholder="Optional transaction ID..." icon="tag" />
            <x-form.input name="notes" label="Notes & Purpose" x-model="advNotes" placeholder="e.g. Advance deposit for batch load" icon="description" />
        </div>

        {{-- Footer Buttons --}}
        <x-slot:footer>
            <x-button type="button" variant="outline" x-on:click="show = false">Cancel</x-button>
            <x-button type="submit" form="record-vendor-advance-form" variant="primary" icon="check" class="px-8 !bg-emerald-600 hover:!bg-emerald-700">Record Advance</x-button>
        </x-slot:footer>
    </form>
</x-modal>
@endpush
@endsection
