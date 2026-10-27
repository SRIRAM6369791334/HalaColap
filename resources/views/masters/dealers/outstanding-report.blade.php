@extends('layouts.app')
@section('title', 'Outstanding Report - ' . $dealer->firm_name)

@section('content')
<div class="space-y-6">
    <div class="mb-4">
        <a href="{{ route('masters.dealers.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
            <span class="material-symbols-rounded text-[20px]">arrow_back</span>
            Back to Directory
        </a>
    </div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <x-avatar name="{{ $dealer->firm_name }}" size="lg" />
            <div>
                <h1 class="text-2xl font-bold font-cabinet text-zinc-900 dark:text-zinc-100 tracking-tight">{{ $dealer->firm_name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <x-badge color="blue">Supplier / Partner</x-badge>
                    <x-badge color="zinc">
                        <span class="material-symbols-rounded text-[14px] mr-1">alt_route</span>
                        {{ $dealer->route ?: 'General Area' }}
                    </x-badge>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <x-button href="{{ route('masters.dealers.edit', $dealer) }}" variant="secondary" icon="edit">Edit Profile</x-button>
            <form action="{{ route('masters.dealers.destroy', $dealer) }}" method="POST" onsubmit="return confirm('Delete {{ $dealer->firm_name }}? This will keep their transaction history intact.')">
                @csrf @method('DELETE')
                <x-button type="submit" variant="danger" icon="delete">Delete</x-button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-1 space-y-6">
            <div class="rounded-3xl p-6 bg-amber-500/40 dark:bg-amber-900/40 backdrop-blur-2xl text-amber-900 dark:text-amber-100 shadow-[0_8px_32px_rgba(245,158,11,0.15)] border border-amber-300/50 dark:border-amber-700/50 relative overflow-hidden transition-all duration-300 hover:shadow-[0_8px_32px_rgba(245,158,11,0.25)] hover:-translate-y-1">
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/20 dark:bg-amber-400/10 rounded-full blur-2xl"></div>
                <div class="absolute -left-10 -bottom-10 w-32 h-32 bg-amber-400/20 dark:bg-amber-600/20 rounded-full blur-2xl"></div>
                <div class="relative z-10 text-center">
                    <div class="text-xs font-bold uppercase tracking-widest text-amber-800/80 dark:text-amber-200 mb-2">Total Payable</div>
                    <div class="text-3xl font-extrabold tracking-tight font-jetbrains mb-2 text-amber-950 dark:text-white drop-shadow-sm">
                        Rs {{ number_format($dealer->displayed_outstanding, 2) }}
                    </div>
                    @if($dealer->dayload_outstanding > 0)
                        <div class="text-xs font-medium text-amber-700/70 dark:text-amber-300 mb-6">
                            Old: Rs {{ number_format($dealer->pending_amount, 0) }} + Day-Load: Rs {{ number_format($dealer->dayload_outstanding, 0) }}
                        </div>
                    @endif
                    <div class="flex flex-col gap-3">
                        <x-button href="{{ route('payments.dealers.create', ['dealer_id' => $dealer->id]) }}" variant="secondary" icon="payments" class="w-full justify-center !text-amber-700 !bg-white/80 hover:!bg-white !border-white backdrop-blur-md shadow-sm">
                            Record Payment
                        </x-button>
                        <x-button href="{{ route('masters.dealers.ledger-pdf', $dealer) }}" variant="secondary" icon="download" class="w-full justify-center !bg-amber-600/20 !text-amber-900 dark:!text-amber-100 !border-amber-400/30 hover:!bg-amber-600/30 backdrop-blur-md">
                            Download Ledger
                        </x-button>
                    </div>
                </div>
            </div>

            <x-card title="Firm Credentials" icon="contact_page">
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-rounded text-zinc-400">person</span>
                        <div>
                            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Contact Person</div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $dealer->contact_person ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-rounded text-zinc-400">call</span>
                        <div>
                            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Contact Phone</div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $dealer->phone }}</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-rounded text-zinc-400">location_on</span>
                        <div>
                            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Store Location</div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $dealer->location ?: 'Not provided' }}</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-rounded text-zinc-400">badge</span>
                        <div>
                            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">GSTIN / Registration</div>
                            <div class="font-mono text-sm text-zinc-900 dark:text-zinc-100">{{ $dealer->gst_number ?: 'Unregistered' }}</div>
                        </div>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="lg:col-span-2">
            <div id="cm-tabs-container" class="bg-white/30 dark:bg-zinc-900/40 backdrop-blur-2xl border border-white/60 dark:border-zinc-800/80 rounded-[2rem] overflow-hidden shadow-[0_8px_32px_rgba(31,38,135,0.07)] z-10 relative">
                <div class="flex flex-wrap p-2 m-4 bg-white/40 dark:bg-zinc-900/40 backdrop-blur-md rounded-2xl border border-white/50 dark:border-zinc-700/50 gap-2">
                    <a href="{{ route('masters.dealers.show', $dealer) }}" class="flex-1 text-center py-3 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-white/50 dark:hover:bg-zinc-800/50 rounded-xl transition-all duration-300">
                        Quick Overview
                    </a>
                    <a href="{{ route('masters.dealers.purchase-history', $dealer) }}" class="flex-1 text-center py-3 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-white/50 dark:hover:bg-zinc-800/50 rounded-xl transition-all duration-300">
                        Purchase Orders
                    </a>
                    <a href="{{ route('payments.dealers.ledger', $dealer) }}" class="flex-1 text-center py-3 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-white/50 dark:hover:bg-zinc-800/50 rounded-xl transition-all duration-300">
                        Payment Ledger
                    </a>
                    <a href="{{ route('masters.dealers.outstanding-report', $dealer) }}" class="flex-1 text-center py-3 text-sm font-bold text-emerald-700 dark:text-emerald-400 bg-white/70 dark:bg-zinc-800/80 border border-black dark:border-white shadow-sm rounded-xl transition-all duration-300">
                        Outstanding Report
                    </a>
                </div>

                <div class="p-6">
                    <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider mb-6">Financial Reconciliation & Metrics</h4>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                        <div class="p-4 rounded-xl border border-blue-200 bg-blue-50 dark:border-blue-900/50 dark:bg-blue-900/20 flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                <span class="material-symbols-rounded text-xl">shopping_bag</span>
                            </div>
                            <div>
                                <div class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Total Purchased</div>
                                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-jetbrains">Rs {{ number_format($totalPurchased, 0) }}</div>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50 dark:border-emerald-900/50 dark:bg-emerald-900/20 flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                <span class="material-symbols-rounded text-xl">payments</span>
                            </div>
                            <div>
                                <div class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Total Paid</div>
                                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-jetbrains">Rs {{ number_format($totalPaid, 0) }}</div>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl border border-purple-200 bg-purple-50 dark:border-purple-900/50 dark:bg-purple-900/20 flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                                <span class="material-symbols-rounded text-xl">account_balance_wallet</span>
                            </div>
                            <div>
                                <div class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Outstanding</div>
                                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-jetbrains">Rs {{ number_format($outstanding, 0) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <div class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm">
                            <h5 class="text-xs font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider mb-4">Aging Analysis</h5>
                            
                            <div class="space-y-4">
                                @php
                                    $totalAging = $buckets['0_30'] + $buckets['31_60'] + $buckets['60_plus'];
                                    $pct0_30 = $totalAging > 0 ? round(($buckets['0_30'] / $totalAging) * 100) : 0;
                                    $pct31_60 = $totalAging > 0 ? round(($buckets['31_60'] / $totalAging) * 100) : 0;
                                    $pct60_plus = $totalAging > 0 ? 100 - $pct0_30 - $pct31_60 : 0;
                                @endphp

                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">0 - 30 Days</span>
                                        <span class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Rs {{ number_format($buckets['0_30'], 0) }}</span>
                                    </div>
                                    <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2">
                                        <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ $pct0_30 }}%"></div>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">31 - 60 Days</span>
                                        <span class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Rs {{ number_format($buckets['31_60'], 0) }}</span>
                                    </div>
                                    <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2">
                                        <div class="bg-amber-500 h-2 rounded-full" style="width: {{ $pct31_60 }}%"></div>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">60+ Days</span>
                                        <span class="text-sm font-bold text-rose-600 dark:text-rose-400">Rs {{ number_format($buckets['60_plus'], 0) }}</span>
                                    </div>
                                    <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2">
                                        <div class="bg-rose-500 h-2 rounded-full" style="width: {{ $pct60_plus }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 rounded-xl border border-indigo-200 bg-indigo-50/50 dark:border-indigo-900/30 dark:bg-indigo-900/10">
                            <h5 class="text-xs font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider mb-4">Payment Health</h5>
                            
                            <div class="space-y-4 mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 text-lg">⚡</div>
                                    <div>
                                        <div class="text-xs text-zinc-500 font-medium">Avg. Payment Days</div>
                                        <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                            {{ $avgPaymentDays !== null ? $avgPaymentDays . ' Days' : 'No payment data' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 text-lg">🛡️</div>
                                    <div>
                                        <div class="text-xs text-zinc-500 font-medium">Total Payments Made</div>
                                        <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100 font-jetbrains">{{ $payments->count() }} transactions</div>
                                    </div>
                                </div>
                            </div>

                            <x-button href="{{ route('masters.dealers.ledger-pdf', $dealer) }}" variant="primary" icon="description" class="w-full justify-center !bg-indigo-600 hover:!bg-indigo-700">Download Ledger PDF</x-button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
