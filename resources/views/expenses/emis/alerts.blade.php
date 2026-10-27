@extends('layouts.app')
@section('title', 'EMI Alerts')

@section('content')
<div class="animate-fade-in" x-data="{ activeTab: 'receive' }">
    <div class="mb-6">
        <a href="{{ route('expenses.emis.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 uppercase tracking-wider mb-2 inline-block">← Back to EMIs</a>
        <h1 class="text-2xl font-bold text-zinc-950 dark:text-zinc-50 font-cabinet">EMI Early Warning Alerts</h1>
        <p class="text-sm text-zinc-500 mt-0.5 font-outfit">Track installments due within 7 days or overdue</p>
    </div>

    <!-- Tab Navigation Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <!-- Tab 1: To Receive -->
        <button @click="activeTab = 'receive'"
                :class="activeTab === 'receive' ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/10 dark:bg-emerald-950/10' : 'border-zinc-200 dark:border-zinc-800/50 bg-white dark:bg-zinc-900'"
                class="w-full border rounded-2xl p-5 text-left transition-all duration-200 group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 font-cabinet">To Receive</span>
                <span class="material-symbols-rounded text-emerald-500" :style="activeTab === 'receive' ? 'font-weight: bold;' : ''">call_received</span>
            </div>
            <span class="font-jetbrains font-bold text-xl text-zinc-900 dark:text-zinc-50 block leading-tight">
                {{ count($overdueToReceive) + count($upcomingToReceive) }} Alerts
            </span>
            <span class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 block font-outfit">From Customers & Dealers</span>
        </button>

        <!-- Tab 2: To Pay -->
        <button @click="activeTab = 'pay'"
                :class="activeTab === 'pay' ? 'border-amber-500 ring-2 ring-amber-500/20 bg-amber-50/10 dark:bg-amber-950/10' : 'border-zinc-200 dark:border-zinc-800/50 bg-white dark:bg-zinc-900'"
                class="w-full border rounded-2xl p-5 text-left transition-all duration-200 group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 font-cabinet">To Pay</span>
                <span class="material-symbols-rounded text-amber-500" :style="activeTab === 'pay' ? 'font-weight: bold;' : ''">call_made</span>
            </div>
            <span class="font-jetbrains font-bold text-xl text-zinc-900 dark:text-zinc-50 block leading-tight">
                {{ count($overdueToPay) + count($upcomingToPay) }} Alerts
            </span>
            <span class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 block font-outfit">To Vendors & Bank Loans</span>
        </button>
    </div>

    <!-- PANEL 1: TO RECEIVE ALERTS -->
    <div x-show="activeTab === 'receive'" x-transition:enter="transition ease-out duration-200" class="space-y-6">
        <!-- Overdue Section -->
        <div>
            <h2 class="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400 mb-3 font-cabinet flex items-center gap-1.5">
                <span class="material-symbols-rounded text-base">warning</span>
                Overdue — {{ count($overdueToReceive) }}
            </h2>
            <div class="space-y-4 max-w-3xl">
                @forelse($overdueToReceive as $emi)
                    @php
                        $entityName = '';
                        if ($emi->emi_type === 'Customer') {
                            $entityName = $emi->customer ? $emi->customer->name : 'Unknown Customer';
                        } elseif ($emi->emi_type === 'Dealer') {
                            $entityName = $emi->dealer ? ($emi->dealer->firm_name ?? $emi->dealer->name) : 'Unknown Dealer';
                        } elseif ($emi->emi_type === 'Vendor') {
                            $entityName = $emi->vendor ? ($emi->vendor->firm_name ?? $emi->vendor->name) : 'Unknown Vendor';
                        } else {
                            $entityName = $emi->bank_name ?? 'Bank Loan / Finance';
                        }
                    @endphp
                    <div class="bg-gradient-to-br from-white via-zinc-50/10 to-zinc-100/10 dark:from-zinc-900 dark:to-zinc-950 rounded-2xl border-l-8 border-rose-500 p-5 shadow-sm flex items-center justify-between group hover:border-emerald-500 transition-all border border-zinc-200 dark:border-zinc-800">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 rounded-xl flex items-center justify-center text-xl group-hover:bg-emerald-50 dark:group-hover:bg-emerald-950/30 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                <span class="material-symbols-rounded">call_received</span>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-zinc-950 dark:text-zinc-50 font-cabinet">{{ $emi->loan_name }}</h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 border-l-2 border-rose-200 dark:border-rose-800 pl-2 mt-0.5">
                                    {{ $entityName }} ({{ $emi->emi_type }})
                                </p>
                            </div>
                        </div>
                        
                        <div class="text-right flex items-center gap-8 font-outfit">
                            <div>
                                <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Amount Due</p>
                                <p class="text-lg font-black text-rose-600 dark:text-rose-400">
                                    <x-currency :amount="$emi->amount" />
                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Deadline</p>
                                <p class="text-sm font-black text-rose-600 dark:text-rose-400">
                                    {{ $emi->due_date->format('d M (D)') }}
                                </p>
                            </div>
                            <div class="no-print flex items-center gap-2">
                                @can('edit emis')
                                <form action="{{ route('expenses.emis.pay', $emi) }}" method="POST" class="inline" onsubmit="return confirm('Mark this installment as Paid?')">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-gradient-to-br from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                                        Pay Now
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center bg-zinc-50 dark:bg-zinc-800/30 rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-800">
                        <p class="text-sm text-zinc-500 font-outfit">No Overdue EMIs</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Upcoming Section -->
        <div>
            <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-3 font-cabinet flex items-center gap-1.5">
                <span class="material-symbols-rounded text-base">schedule</span>
                Upcoming in 7 Days — {{ count($upcomingToReceive) }}
            </h2>
            <div class="space-y-4 max-w-3xl">
                @forelse($upcomingToReceive as $emi)
                    @php
                        $entityName = '';
                        if ($emi->emi_type === 'Customer') {
                            $entityName = $emi->customer ? $emi->customer->name : 'Unknown Customer';
                        } elseif ($emi->emi_type === 'Dealer') {
                            $entityName = $emi->dealer ? ($emi->dealer->firm_name ?? $emi->dealer->name) : 'Unknown Dealer';
                        } elseif ($emi->emi_type === 'Vendor') {
                            $entityName = $emi->vendor ? ($emi->vendor->firm_name ?? $emi->vendor->name) : 'Unknown Vendor';
                        } else {
                            $entityName = $emi->bank_name ?? 'Bank Loan / Finance';
                        }
                    @endphp
                    <div class="bg-gradient-to-br from-white via-zinc-50/10 to-zinc-100/10 dark:from-zinc-900 dark:to-zinc-950 rounded-2xl border-l-8 border-amber-400 p-5 shadow-sm flex items-center justify-between group hover:border-emerald-500 transition-all border border-zinc-200 dark:border-zinc-800">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 rounded-xl flex items-center justify-center text-xl group-hover:bg-emerald-50 dark:group-hover:bg-emerald-950/30 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                <span class="material-symbols-rounded">call_received</span>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-zinc-950 dark:text-zinc-50 font-cabinet">{{ $emi->loan_name }}</h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 border-l-2 border-amber-200 dark:border-amber-800 pl-2 mt-0.5">
                                    {{ $entityName }} ({{ $emi->emi_type }})
                                </p>
                            </div>
                        </div>
                        
                        <div class="text-right flex items-center gap-8 font-outfit">
                            <div>
                                <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Amount Due</p>
                                <p class="text-lg font-black text-emerald-900 dark:text-emerald-400">
                                    <x-currency :amount="$emi->amount" />
                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Deadline</p>
                                <p class="text-sm font-black text-red-600">
                                    {{ $emi->due_date->format('d M (D)') }}
                                </p>
                            </div>
                            <div class="no-print flex items-center gap-2">
                                @can('edit emis')
                                <form action="{{ route('expenses.emis.pay', $emi) }}" method="POST" class="inline" onsubmit="return confirm('Mark this installment as Paid?')">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-gradient-to-br from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                                        Pay Now
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center bg-zinc-50 dark:bg-zinc-800/30 rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-800">
                        <p class="text-sm text-zinc-500 font-outfit">No Upcoming EMIs</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- PANEL 2: TO PAY ALERTS -->
    <div x-show="activeTab === 'pay'" x-transition:enter="transition ease-out duration-200" class="space-y-6" style="display: none;">
        <!-- Overdue Section -->
        <div>
            <h2 class="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400 mb-3 font-cabinet flex items-center gap-1.5">
                <span class="material-symbols-rounded text-base">warning</span>
                Overdue — {{ count($overdueToPay) }}
            </h2>
            <div class="space-y-4 max-w-3xl">
                @forelse($overdueToPay as $emi)
                    @php
                        $entityName = '';
                        if ($emi->emi_type === 'Customer') {
                            $entityName = $emi->customer ? $emi->customer->name : 'Unknown Customer';
                        } elseif ($emi->emi_type === 'Dealer') {
                            $entityName = $emi->dealer ? ($emi->dealer->firm_name ?? $emi->dealer->name) : 'Unknown Dealer';
                        } elseif ($emi->emi_type === 'Vendor') {
                            $entityName = $emi->vendor ? ($emi->vendor->firm_name ?? $emi->vendor->name) : 'Unknown Vendor';
                        } else {
                            $entityName = $emi->bank_name ?? 'Bank Loan / Finance';
                        }
                    @endphp
                    <div class="bg-gradient-to-br from-white via-zinc-50/10 to-zinc-100/10 dark:from-zinc-900 dark:to-zinc-950 rounded-2xl border-l-8 border-rose-500 p-5 shadow-sm flex items-center justify-between group hover:border-emerald-500 transition-all border border-zinc-200 dark:border-zinc-800">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 rounded-xl flex items-center justify-center text-xl group-hover:bg-emerald-50 dark:group-hover:bg-emerald-950/30 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                <span class="material-symbols-rounded">call_made</span>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-zinc-950 dark:text-zinc-50 font-cabinet">{{ $emi->loan_name }}</h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 border-l-2 border-rose-200 dark:border-rose-800 pl-2 mt-0.5">
                                    {{ $entityName }} ({{ $emi->emi_type }})
                                </p>
                            </div>
                        </div>
                        
                        <div class="text-right flex items-center gap-8 font-outfit">
                            <div>
                                <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Amount Due</p>
                                <p class="text-lg font-black text-rose-600 dark:text-rose-400">
                                    <x-currency :amount="$emi->amount" />
                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Deadline</p>
                                <p class="text-sm font-black text-rose-600 dark:text-rose-400">
                                    {{ $emi->due_date->format('d M (D)') }}
                                </p>
                            </div>
                            <div class="no-print flex items-center gap-2">
                                @can('edit emis')
                                <form action="{{ route('expenses.emis.pay', $emi) }}" method="POST" class="inline" onsubmit="return confirm('Mark this installment as Paid?')">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-gradient-to-br from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                                        Pay Now
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center bg-zinc-50 dark:bg-zinc-800/30 rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-800">
                        <p class="text-sm text-zinc-500 font-outfit">No Overdue EMIs</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Upcoming Section -->
        <div>
            <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-3 font-cabinet flex items-center gap-1.5">
                <span class="material-symbols-rounded text-base">schedule</span>
                Upcoming in 7 Days — {{ count($upcomingToPay) }}
            </h2>
            <div class="space-y-4 max-w-3xl">
                @forelse($upcomingToPay as $emi)
                    @php
                        $entityName = '';
                        if ($emi->emi_type === 'Customer') {
                            $entityName = $emi->customer ? $emi->customer->name : 'Unknown Customer';
                        } elseif ($emi->emi_type === 'Dealer') {
                            $entityName = $emi->dealer ? ($emi->dealer->firm_name ?? $emi->dealer->name) : 'Unknown Dealer';
                        } elseif ($emi->emi_type === 'Vendor') {
                            $entityName = $emi->vendor ? ($emi->vendor->firm_name ?? $emi->vendor->name) : 'Unknown Vendor';
                        } else {
                            $entityName = $emi->bank_name ?? 'Bank Loan / Finance';
                        }
                    @endphp
                    <div class="bg-gradient-to-br from-white via-zinc-50/10 to-zinc-100/10 dark:from-zinc-900 dark:to-zinc-950 rounded-2xl border-l-8 border-amber-400 p-5 shadow-sm flex items-center justify-between group hover:border-emerald-500 transition-all border border-zinc-200 dark:border-zinc-800">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 rounded-xl flex items-center justify-center text-xl group-hover:bg-emerald-50 dark:group-hover:bg-emerald-950/30 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                <span class="material-symbols-rounded">call_made</span>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-zinc-950 dark:text-zinc-50 font-cabinet">{{ $emi->loan_name }}</h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 border-l-2 border-amber-200 dark:border-amber-800 pl-2 mt-0.5">
                                    {{ $entityName }} ({{ $emi->emi_type }})
                                </p>
                            </div>
                        </div>
                        
                        <div class="text-right flex items-center gap-8 font-outfit">
                            <div>
                                <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Amount Due</p>
                                <p class="text-lg font-black text-emerald-900 dark:text-emerald-400">
                                    <x-currency :amount="$emi->amount" />
                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">Deadline</p>
                                <p class="text-sm font-black text-red-600">
                                    {{ $emi->due_date->format('d M (D)') }}
                                </p>
                            </div>
                            <div class="no-print flex items-center gap-2">
                                @can('edit emis')
                                <form action="{{ route('expenses.emis.pay', $emi) }}" method="POST" class="inline" onsubmit="return confirm('Mark this installment as Paid?')">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-gradient-to-br from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                                        Pay Now
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center bg-zinc-50 dark:bg-zinc-800/30 rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-800">
                        <p class="text-sm text-zinc-500 font-outfit">No Upcoming EMIs</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
