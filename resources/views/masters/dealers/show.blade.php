@extends('layouts.app')
@section('title', 'Dealer Details - ' . $dealer->firm_name)

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
            @can('edit dealers')
                <x-button href="{{ route('masters.dealers.edit', $dealer) }}" variant="secondary" icon="edit">Edit Profile</x-button>
            @endcan
            @can('delete dealers')
                <form action="{{ route('masters.dealers.destroy', $dealer) }}" method="POST" onsubmit="return confirm('Delete {{ $dealer->firm_name }}? This will keep their transaction history intact.')">
                    @csrf @method('DELETE')
                    <x-button type="submit" variant="danger" icon="delete">Delete</x-button>
                </form>
            @endcan
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
                        @can('create payments')
                            <x-button href="{{ route('payments.dealers.create', ['dealer_id' => $dealer->id]) }}" variant="secondary" icon="payments" class="w-full justify-center !text-amber-700 !bg-white/80 hover:!bg-white !border-white backdrop-blur-md shadow-sm">
                                Record Payment
                            </x-button>
                        @endcan
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
                    <a href="{{ route('masters.dealers.show', $dealer) }}" class="flex-1 text-center py-3 text-sm font-bold text-emerald-700 dark:text-emerald-400 bg-white/70 dark:bg-zinc-800/80 border border-black dark:border-white shadow-sm rounded-xl transition-all duration-300">
                        Quick Overview
                    </a>
                    @can('view dealer purchases')
                    <a href="{{ route('masters.dealers.purchase-history', $dealer) }}" class="flex-1 text-center py-3 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-white/50 dark:hover:bg-zinc-800/50 rounded-xl transition-all duration-300">
                        Purchase Orders
                    </a>
                    @endcan
                    @can('view dealer ledger')
                    <a href="{{ route('payments.dealers.ledger', $dealer) }}" class="flex-1 text-center py-3 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-white/50 dark:hover:bg-zinc-800/50 rounded-xl transition-all duration-300">
                        Payment Ledger
                    </a>
                    @endcan
                    <a href="{{ route('masters.dealers.outstanding-report', $dealer) }}" class="flex-1 text-center py-3 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-white/50 dark:hover:bg-zinc-800/50 rounded-xl transition-all duration-300">
                        Outstanding Report
                    </a>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider mb-4">Recent Purchases</h4>
                        <div class="space-y-3">
                            @forelse($recentPurchases as $purchase)
                                <div class="flex justify-between items-center p-4 rounded-2xl border border-white/60 dark:border-zinc-700 shadow-[inset_0_2px_4px_rgba(0,0,0,0.05)] bg-white/40 dark:bg-zinc-900/40 backdrop-blur-xl transition-all duration-300 hover:bg-white/60 hover:-translate-y-0.5">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                                            <span class="material-symbols-rounded text-xl">shopping_cart</span>
                                        </div>
                                        <div>
                                            <p class="font-bold text-sm text-zinc-900 dark:text-zinc-100">
                                                @if($purchase->items->isNotEmpty())
                                                    {{ str($purchase->items->pluck('item_name')->join(', '))->limit(25) }}
                                                @else
                                                    {{ str($purchase->item)->limit(25) }}
                                                @endif
                                            </p>
                                            <p class="text-xs font-medium text-zinc-500 mt-0.5">{{ $purchase->date->format('d M Y') }}</p>
                                        </div>
                                    </div>
                                    <div class="font-bold text-base text-zinc-900 dark:text-zinc-100 font-jetbrains">
                                        Rs {{ number_format($purchase->total_amount, 0) }}
                                    </div>
                                </div>
                            @empty
                                <div class="text-sm italic text-zinc-500">No recent purchases.</div>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider mb-4">Recent Payments</h4>
                        <div class="space-y-3">
                            @forelse($recentPayments as $payment)
                                <div class="flex justify-between items-center p-4 rounded-2xl border border-white/60 dark:border-zinc-700 shadow-[inset_0_2px_4px_rgba(0,0,0,0.05)] bg-white/40 dark:bg-zinc-900/40 backdrop-blur-xl transition-all duration-300 hover:bg-white/60 hover:-translate-y-0.5">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                            <span class="material-symbols-rounded text-xl">payments</span>
                                        </div>
                                        <div>
                                            <p class="font-bold text-sm text-zinc-900 dark:text-zinc-100">Payment Sent</p>
                                            <p class="text-xs font-medium text-zinc-500 mt-0.5">{{ $payment->payment_mode }} &bull; {{ $payment->date->format('d M Y') }}</p>
                                        </div>
                                    </div>
                                    <div class="font-bold text-base text-emerald-600 dark:text-emerald-400 font-jetbrains">
                                        Rs {{ number_format($payment->amount, 0) }}
                                    </div>
                                </div>
                            @empty
                                <div class="text-sm italic text-zinc-500">No recent payments.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                @if($loadCount > 0)
                <div class="px-6 pb-6 pt-2">
                    <div class="pt-6 border-t border-zinc-200 dark:border-zinc-800">
                        <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider mb-6">Day-Load Activity</h4>

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div class="p-4 rounded-2xl border border-white/60 dark:border-zinc-700 bg-white/40 dark:bg-zinc-900/40 backdrop-blur-xl">
                                <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1">Total Loads</div>
                                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-jetbrains">{{ $loadCount }}</div>
                            </div>
                            <div class="p-4 rounded-2xl border border-white/60 dark:border-zinc-700 bg-white/40 dark:bg-zinc-900/40 backdrop-blur-xl">
                                <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1">Total Boxes</div>
                                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-jetbrains">{{ number_format($totalBoxesReceived) }}</div>
                            </div>
                            <div class="p-4 rounded-2xl border border-white/60 dark:border-zinc-700 bg-white/40 dark:bg-zinc-900/40 backdrop-blur-xl">
                                <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1">Bird Weight</div>
                                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-jetbrains">{{ number_format($totalBirdWeight, 1) }} kg</div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
