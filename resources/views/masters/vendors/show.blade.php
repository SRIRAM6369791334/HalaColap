@extends('layouts.app')
@section('title', 'Vendor Details - ' . $vendor->firm_name)

@section('content')
<div class="space-y-6">
    <div class="mb-4">
        <a href="{{ route('masters.vendors.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
            <span class="material-symbols-rounded text-[20px]">arrow_back</span>
            Back to Directory
        </a>
    </div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <x-avatar name="{{ $vendor->firm_name }}" size="lg" />
            <div>
                <h1 class="text-2xl font-bold font-cabinet text-zinc-900 dark:text-zinc-100 tracking-tight">{{ $vendor->firm_name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <x-badge color="teal">Supplier Partner</x-badge>
                    <x-badge color="zinc">
                        <span class="material-symbols-rounded text-[14px] mr-1">alt_route</span>
                        {{ $vendor->route ?: 'General Sector' }}
                    </x-badge>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @can('edit vendors')
                <x-button href="{{ route('masters.vendors.edit', $vendor) }}" variant="secondary" icon="edit">Edit Profile</x-button>
            @endcan
            @can('delete vendors')
                <form action="{{ route('masters.vendors.destroy', $vendor) }}" method="POST" onsubmit="return confirm('Delete {{ $vendor->firm_name }}? This will keep their transaction history intact.')">
                    @csrf @method('DELETE')
                    <x-button type="submit" variant="danger" icon="delete">Delete</x-button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            {{-- Outstanding Payable Card --}}
            <div class="rounded-3xl p-6 bg-rose-500/40 dark:bg-rose-900/40 backdrop-blur-2xl text-rose-900 dark:text-rose-100 shadow-[0_8px_32px_rgba(244,63,94,0.15)] border border-rose-300/50 dark:border-rose-700/50 relative overflow-hidden transition-all duration-300 hover:shadow-[0_8px_32px_rgba(244,63,94,0.25)] hover:-translate-y-1">
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/20 dark:bg-rose-400/10 rounded-full blur-2xl"></div>
                <div class="absolute -left-10 -bottom-10 w-32 h-32 bg-rose-400/20 dark:bg-rose-600/20 rounded-full blur-2xl"></div>
                <div class="relative z-10 text-center">
                    <div class="text-xs font-bold uppercase tracking-widest text-rose-800/80 dark:text-rose-200 mb-2">Outstanding Payable</div>
                    <div class="text-3xl font-extrabold tracking-tight font-jetbrains mb-2 text-rose-950 dark:text-white drop-shadow-sm">
                        Rs {{ number_format($outstandingBalance, 2) }}
                    </div>
                    @if((float) $vendor->pending_amount > 0 || $totalDayLoadLiabilities > 0 || $totalCreditPurchases > 0 || $totalPaymentsPaid > 0)
                        <div class="text-[10px] font-medium text-rose-800/80 dark:text-rose-300/80 mb-6 leading-relaxed">
                            @if((float) $vendor->pending_amount > 0)
                                Old: Rs {{ number_format($vendor->pending_amount, 0) }} + 
                            @endif
                            Purchases: Rs {{ number_format($totalCreditPurchases, 0) }} + Day-Load: Rs {{ number_format($totalDayLoadLiabilities, 0) }}<br>Paid: Rs {{ number_format($totalPaymentsPaid, 0) }}
                        </div>
                    @endif
                    <div class="flex flex-col gap-3">
                        <x-button href="{{ route('payments.vendors.create', ['vendor_id' => $vendor->id]) }}" variant="secondary" icon="payments" class="w-full justify-center !text-rose-700 !bg-white/80 hover:!bg-white !border-white backdrop-blur-md shadow-sm">
                            Record Payment
                        </x-button>
                        <x-button href="{{ route('masters.vendors.purchase-history', $vendor) }}" variant="secondary" icon="history" class="w-full justify-center !bg-rose-600/20 !text-rose-900 dark:!text-rose-100 !border-rose-400/30 hover:!bg-rose-600/30 backdrop-blur-md">
                            View Full History
                        </x-button>
                    </div>
                </div>
            </div>

            {{-- Active Advance Balance Card --}}
            <div class="rounded-3xl p-6 bg-gradient-to-br from-amber-400/30 via-amber-500/20 to-orange-400/20 dark:from-amber-900/40 dark:via-amber-950/30 dark:to-orange-900/30 backdrop-blur-2xl text-amber-950 dark:text-amber-100 shadow-[0_8px_32px_rgba(245,158,11,0.15)] border border-amber-300/60 dark:border-amber-700/60 relative overflow-hidden transition-all duration-300 hover:shadow-[0_8px_32px_rgba(245,158,11,0.25)] hover:-translate-y-1">
                <div class="absolute -right-8 -top-8 w-32 h-32 bg-amber-300/30 dark:bg-amber-500/10 rounded-full blur-xl"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-xs font-black uppercase tracking-widest text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                            <span class="material-symbols-rounded text-[18px] text-amber-600">local_shipping</span>
                            Active Advance Balance
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100/80 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300 border border-amber-300/60">
                            {{ $advances->where('status', '!=', 'Fully Adjusted')->count() }} Active
                        </span>
                    </div>

                    <div class="text-3xl font-extrabold tracking-tight font-jetbrains my-2 text-amber-950 dark:text-white drop-shadow-sm text-center">
                        Rs {{ number_format($totalActiveAdvanceBalance, 2) }}
                    </div>

                    <div class="p-3 rounded-2xl bg-white/60 dark:bg-black/20 border border-amber-200/60 dark:border-amber-800/40 space-y-1.5 text-xs mb-4">
                        <div class="flex justify-between text-zinc-600 dark:text-zinc-300">
                            <span>Total Advances Given:</span>
                            <strong class="font-jetbrains text-zinc-900 dark:text-zinc-100">Rs {{ number_format($totalAdvanceGiven, 2) }}</strong>
                        </div>
                        <div class="flex justify-between text-zinc-600 dark:text-zinc-300">
                            <span>Adjusted against Bills:</span>
                            <strong class="font-jetbrains text-emerald-600">Rs {{ number_format($totalAdvanceAdjusted, 2) }}</strong>
                        </div>
                        <div class="flex justify-between pt-1.5 border-t border-amber-200/60 dark:border-amber-800/40 font-bold text-amber-900 dark:text-amber-200">
                            <span>Net Settlement Payable:</span>
                            <strong class="font-jetbrains {{ $netSettlementBalance > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                Rs {{ number_format($netSettlementBalance, 2) }}
                            </strong>
                        </div>
                    </div>

                    <button type="button" 
                            @click="$dispatch('open-modal', 'record-vendor-advance-modal')"
                            class="w-full py-2.5 px-4 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white shadow-sm flex items-center justify-center gap-1.5 transition-all">
                        <span class="material-symbols-rounded text-[18px]">add_circle</span>
                        + Issue Advance Deposit
                    </button>
                </div>
            </div>

            <x-card title="Profile Credentials" icon="contact_page">
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-rounded text-zinc-400">person</span>
                        <div>
                            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Contact Person</div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $vendor->contact_person ?: 'Not specified' }}</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-rounded text-zinc-400">call</span>
                        <div>
                            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Contact Phone</div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $vendor->phone }}</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-rounded text-zinc-400">location_on</span>
                        <div>
                            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Firm Location</div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $vendor->location ?: 'Not set' }}</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-rounded text-zinc-400">badge</span>
                        <div>
                            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">GSTIN / Registration</div>
                            <div class="font-mono text-sm text-zinc-900 dark:text-zinc-100">{{ $vendor->gst_number ?: 'Unregistered' }}</div>
                        </div>
                    </div>
                </div>
            </x-card>

            @if($vendor->notes)
                <x-card title="Vendor Notes" icon="description" class="border-l-4 border-l-teal-500">
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 whitespace-pre-line leading-relaxed">
                        {{ $vendor->notes }}
                    </div>
                </x-card>
            @endif
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div id="cm-tabs-container" class="bg-white/30 dark:bg-zinc-900/40 backdrop-blur-2xl border border-white/60 dark:border-zinc-800/80 rounded-[2rem] overflow-hidden shadow-[0_8px_32px_rgba(31,38,135,0.07)] z-10 relative">
                <div class="flex flex-wrap p-2 m-4 bg-white/40 dark:bg-zinc-900/40 backdrop-blur-md rounded-2xl border border-white/50 dark:border-zinc-700/50 gap-2">
                    <a href="{{ route('masters.vendors.show', $vendor) }}" class="flex-1 text-center py-3 text-sm font-bold text-teal-700 dark:text-teal-400 bg-white/70 dark:bg-zinc-800/80 shadow-sm rounded-xl transition-all duration-300">
                        Quick Look & Activity
                    </a>
                    @can('view vendor purchases')
                    <a href="{{ route('masters.vendors.purchase-history', $vendor) }}" class="flex-1 text-center py-3 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-white/50 dark:hover:bg-zinc-800/50 rounded-xl transition-all duration-300">
                        Full Purchase History
                    </a>
                    @endcan
                </div>

                <div class="p-6">
                    <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider mb-6">Recent Activity Insights</h4>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                        <div class="p-4 rounded-2xl border border-white/60 dark:border-zinc-700 shadow-[inset_0_2px_4px_rgba(0,0,0,0.05)] bg-white/40 dark:bg-zinc-900/40 backdrop-blur-xl flex items-center gap-4 transition-all duration-300 hover:bg-white/60">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                <span class="material-symbols-rounded text-xl">shopping_cart</span>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Total Purchases</div>
                                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ $totalPurchaseCount }}
                                </div>
                            </div>
                        </div>

                        <div class="p-4 rounded-2xl border border-white/60 dark:border-zinc-700 shadow-[inset_0_2px_4px_rgba(0,0,0,0.05)] bg-white/40 dark:bg-zinc-900/40 backdrop-blur-xl flex items-center gap-4 transition-all duration-300 hover:bg-white/60">
                            <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                <span class="material-symbols-rounded text-xl">payments</span>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Total Volume</div>
                                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-jetbrains">
                                    Rs {{ number_format($totalPurchaseAmount, 0) }}
                                </div>
                            </div>
                        </div>

                        <div class="p-4 rounded-2xl border border-white/60 dark:border-zinc-700 shadow-[inset_0_2px_4px_rgba(0,0,0,0.05)] bg-white/40 dark:bg-zinc-900/40 backdrop-blur-xl flex items-center gap-4 transition-all duration-300 hover:bg-white/60">
                            <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                                <span class="material-symbols-rounded text-xl">calendar_today</span>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Last Purchase</div>
                                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ $lastPurchaseDate?->format('d M y') ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($loadCount > 0)
                    <div class="pt-8 border-t border-zinc-200 dark:border-zinc-800 mt-8">
                        <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider mb-6">Day-Load Activity</h4>

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                            <div class="p-4 rounded-2xl border border-white/60 dark:border-zinc-700 bg-white/40 dark:bg-zinc-900/40 backdrop-blur-xl">
                                <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1">Total Loads</div>
                                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-jetbrains">{{ $loadCount }}</div>
                            </div>
                            <div class="p-4 rounded-2xl border border-white/60 dark:border-zinc-700 bg-white/40 dark:bg-zinc-900/40 backdrop-blur-xl">
                                <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1">Total Boxes</div>
                                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-jetbrains">{{ number_format($totalBoxesLoaded) }}</div>
                            </div>
                            <div class="p-4 rounded-2xl border border-white/60 dark:border-zinc-700 bg-white/40 dark:bg-zinc-900/40 backdrop-blur-xl">
                                <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1">Bird Weight</div>
                                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-jetbrains">{{ number_format($totalBirdWeight, 1) }} kg</div>
                            </div>
                            <div class="p-4 rounded-2xl border border-white/60 dark:border-zinc-700 bg-white/40 dark:bg-zinc-900/40 backdrop-blur-xl">
                                <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1">Farm Weight</div>
                                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-jetbrains">{{ number_format($totalFarmWeight, 1) }} kg</div>
                            </div>
                            <div class="p-4 rounded-2xl border border-white/60 dark:border-zinc-700 bg-white/40 dark:bg-zinc-900/40 backdrop-blur-xl">
                                <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1">Loss Weight</div>
                                <div class="text-lg font-bold text-rose-600 dark:text-rose-400 font-jetbrains">{{ number_format($totalLossWeight, 1) }} kg</div>
                            </div>
                            <div class="p-4 rounded-2xl border border-white/60 dark:border-zinc-700 bg-white/40 dark:bg-zinc-900/40 backdrop-blur-xl">
                                <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1">Avg Rate Variance</div>
                                <div class="text-lg font-bold {{ $avgRateVariance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} font-jetbrains">
                                    {{ $avgRateVariance !== null ? number_format($avgRateVariance, 2) : 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Advance Deposit History Section --}}
            <x-card title="Advance Deposits & Adjustments" icon="local_shipping">
                <div class="p-4">
                    @if($advances->isEmpty())
                        <div class="py-8 text-center">
                            <span class="material-symbols-rounded text-4xl text-zinc-300 dark:text-zinc-600 mb-2">account_balance_wallet</span>
                            <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">No Advance Deposits Recorded</p>
                            <p class="text-xs text-zinc-400 mt-1">Click the "+ Issue Advance Deposit" button to log an advance payment.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/50 text-zinc-500 uppercase tracking-wider font-bold">
                                        <th class="px-4 py-3">Date</th>
                                        <th class="px-4 py-3">Total Advance</th>
                                        <th class="px-4 py-3">Funding Split</th>
                                        <th class="px-4 py-3">Adjusted</th>
                                        <th class="px-4 py-3">Remaining Balance</th>
                                        <th class="px-4 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                    @foreach($advances as $adv)
                                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $adv->date->format('d M Y') }}
                                            <span class="block text-[10px] text-zinc-400">{{ $adv->date->format('l') }}</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap font-jetbrains font-bold text-sm text-amber-600 dark:text-amber-400">
                                            Rs {{ number_format($adv->total_amount, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-[11px] text-zinc-600 dark:text-zinc-300">
                                            @if($adv->cash_amount > 0)
                                                <span class="inline-block px-1.5 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 font-mono mr-1">Cash: {{ number_format($adv->cash_amount, 0) }}</span>
                                            @endif
                                            @if($adv->bank_amount > 0)
                                                <span class="inline-block px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-mono mr-1">Bank: {{ number_format($adv->bank_amount, 0) }}</span>
                                            @endif
                                            @if($adv->investment_amount > 0)
                                                <span class="inline-block px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 font-mono mr-1">Pool: {{ number_format($adv->investment_amount, 0) }}</span>
                                            @endif
                                            @if($adv->reference_number)
                                                <span class="block text-[10px] text-zinc-400 font-mono mt-0.5">Ref: {{ $adv->reference_number }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap font-jetbrains text-emerald-600">
                                            Rs {{ number_format($adv->adjusted_amount, 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap font-jetbrains font-bold {{ $adv->remaining_amount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-400' }}">
                                            Rs {{ number_format($adv->remaining_amount, 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if($adv->status === 'Fully Adjusted')
                                                <x-badge color="green">Fully Adjusted</x-badge>
                                            @elseif($adv->adjusted_amount > 0)
                                                <x-badge color="teal">Partially Adjusted</x-badge>
                                            @else
                                                <x-badge color="amber">Active</x-badge>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </x-card>
        </div>
    </div>
</div>

@push('modals')
{{-- Record Vendor Advance Modal for this specific Vendor --}}
<x-modal name="record-vendor-advance-modal" title="Record Vendor Advance" subtitle="Issue advance deposit to {{ $vendor->firm_name }}" icon="local_shipping" maxWidth="720" :show="$errors->any()">
    <form id="record-vendor-advance-form" 
          action="{{ route('payments.vendors.advances.store') }}" 
          method="POST"
          x-data="{
              advDate: '{{ now()->format('Y-m-d') }}',
              advCash: '',
              advBank: '',
              advInvestment: '',
              advBankType: 'UPI',
              advRef: '',
              advNotes: '',

              get totalAdvance() {
                  let c = parseFloat(this.advCash) || 0;
                  let b = parseFloat(this.advBank) || 0;
                  let i = parseFloat(this.advInvestment) || 0;
                  return (c + b + i).toFixed(2);
              }
          }">
        @csrf
        <input type="hidden" name="vendor_id" value="{{ $vendor->id }}">

        {{-- Date Field --}}
        <div class="mb-6">
            <x-form.input type="date" name="date" label="Payment Date" required x-model="advDate" icon="calendar_month" />
        </div>

        {{-- Funding Source Split --}}
        <div class="mb-6 p-4 rounded-2xl bg-zinc-50/80 dark:bg-zinc-800/40 border border-zinc-200/80 dark:border-zinc-700/80">
            <div class="flex items-center justify-between mb-3 border-b border-zinc-200/60 dark:border-zinc-700/60 pb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                    <span class="material-symbols-rounded text-amber-500 text-lg">account_balance_wallet</span>
                    Funding Source Split
                </span>
                <span class="text-xs font-bold text-zinc-500">
                    Total Advance: <strong class="font-jetbrains text-sm font-black text-amber-600 dark:text-amber-400">Rs <span x-text="totalAdvance"></span></strong>
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
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
