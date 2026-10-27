@extends(request()->ajax() ? 'layouts.empty' : 'layouts.app')
@section('title', 'Customer Details - ' . $customer->name)

@section('content')
@if(!request()->ajax())
<div class="space-y-6">
    <div class="mb-4">
        <a href="{{ route('masters.customers.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
            <span class="material-symbols-rounded text-[20px]">arrow_back</span>
            Back to Directory
        </a>
    </div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <x-avatar name="{{ $customer->name }}" size="lg" />
            <div>
                <h1 class="text-2xl font-bold font-cabinet text-zinc-900 dark:text-zinc-100 tracking-tight">{{ $customer->name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    @if($customer->type === 'Wholesale')
                        <x-badge color="blue">Wholesale Partner</x-badge>
                    @else
                        <x-badge color="rose">Retail Buyer</x-badge>
                    @endif
                    <x-badge color="zinc">
                        <span class="material-symbols-rounded text-[14px] mr-1">alt_route</span>
                        {{ $customer->route ?: 'General Sector' }}
                    </x-badge>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @can('edit customers')
                <x-button href="{{ route('masters.customers.edit', $customer) }}" variant="secondary" icon="edit">Edit Profile</x-button>
            @endcan
            @can('delete customers')
                <form action="{{ route('masters.customers.destroy', $customer) }}" method="POST" onsubmit="return confirm('Delete {{ $customer->name }}? This will keep their transaction history intact.')">
                    @csrf @method('DELETE')
                    <x-button type="submit" variant="danger" icon="delete">Delete</x-button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-1 space-y-6">
            <div class="rounded-3xl p-6 bg-rose-500/40 dark:bg-rose-900/40 backdrop-blur-2xl text-rose-900 dark:text-rose-100 shadow-[0_8px_32px_rgba(225,29,72,0.15)] border border-rose-300/50 dark:border-rose-700/50 relative overflow-hidden transition-all duration-300 hover:shadow-[0_8px_32px_rgba(225,29,72,0.25)] hover:-translate-y-1">
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/20 dark:bg-rose-400/10 rounded-full blur-2xl"></div>
                <div class="absolute -left-10 -bottom-10 w-32 h-32 bg-rose-400/20 dark:bg-rose-600/20 rounded-full blur-2xl"></div>
                <div class="relative z-10 text-center">
                    <div class="text-xs font-bold uppercase tracking-widest text-rose-800/80 dark:text-rose-200 mb-2">Total Outstanding</div>
                    <div class="text-3xl font-extrabold tracking-tight font-jetbrains mb-6 text-rose-950 dark:text-white drop-shadow-sm">
                        Rs {{ number_format($customer->balance, 2) }}
                    </div>
                    <div class="flex flex-col gap-3">
                        @can('create payments')
                            <x-button href="{{ route('payments.customers.create', ['customer_id' => $customer->id]) }}" variant="secondary" icon="payments" class="w-full justify-center !text-rose-700 !bg-white/80 hover:!bg-white !border-white backdrop-blur-md shadow-sm">
                                Record Payment
                            </x-button>
                        @endcan
                        <x-button href="{{ route('masters.customers.ledger-pdf', $customer) }}" variant="secondary" icon="download" class="w-full justify-center !bg-rose-600/20 !text-rose-900 dark:!text-rose-100 !border-rose-400/30 hover:!bg-rose-600/30 backdrop-blur-md">
                            Download Statement
                        </x-button>
                    </div>
                </div>
            </div>

            <x-card title="Profile Credentials" icon="contact_page">
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-rounded text-zinc-400">call</span>
                        <div>
                            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Contact Phone</div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $customer->phone }}</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-rounded text-zinc-400">location_on</span>
                        <div>
                            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Store Address</div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $customer->address ?: 'Not provided' }}</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-rounded text-zinc-400">badge</span>
                        <div>
                            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">GSTIN / Registration</div>
                            <div class="font-mono text-sm text-zinc-900 dark:text-zinc-100">{{ $customer->gst_number ?: 'Unregistered' }}</div>
                        </div>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="lg:col-span-2 space-y-6">
            
            @if($overdueEmis->count() > 0 || $upcomingEmis->count() > 0)
                <div class="space-y-4">
                    @if($overdueEmis->count() > 0)
                    <div class="flex items-center gap-4 p-4 rounded-xl border border-rose-200 bg-rose-50 dark:border-rose-900/50 dark:bg-rose-900/20">
                        <span class="material-symbols-rounded text-rose-600 dark:text-rose-400 text-3xl">error</span>
                        <div class="flex-1">
                            <h4 class="text-sm font-bold text-rose-800 dark:text-rose-300">Overdue EMI Alert!</h4>
                            <p class="text-sm text-rose-700 dark:text-rose-400 mt-0.5">
                                Customer has {{ $overdueEmis->count() }} overdue EMI(s) totaling <strong>Rs {{ number_format($overdueEmis->sum('emi_amount'), 2) }}</strong>.
                            </p>
                        </div>
                        <x-button href="{{ route('masters.customers.emi-history', $customer) }}" variant="danger" size="sm">View Details</x-button>
                    </div>
                    @endif

                    @if($upcomingEmis->count() > 0)
                    <div class="flex items-center gap-4 p-4 rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-900/20">
                        <span class="material-symbols-rounded text-amber-600 dark:text-amber-400 text-3xl">warning</span>
                        <div class="flex-1">
                            <h4 class="text-sm font-bold text-amber-800 dark:text-amber-300">Upcoming EMI</h4>
                            <p class="text-sm text-amber-700 dark:text-amber-400 mt-0.5">
                                Customer has {{ $upcomingEmis->count() }} EMI(s) due in the next 7 days.
                            </p>
                        </div>
                        <x-button href="{{ route('masters.customers.emi-history', $customer) }}" variant="secondary" class="!bg-amber-600 !text-white !border-amber-600 hover:!bg-amber-700" size="sm">View Details</x-button>
                    </div>
                    @endif
                </div>
            @endif

@endif
            <div id="cm-tabs-container" x-data="ajaxTabs" @click="handleTabClick" @mouseover="prefetchTab" @popstate.window="window.location.reload()" class="bg-white/30 dark:bg-zinc-900/40 backdrop-blur-2xl border border-white/60 dark:border-zinc-800/80 rounded-[2rem] overflow-hidden shadow-[0_8px_32px_rgba(31,38,135,0.07)] z-10 relative">
                
                <div class="flex flex-wrap p-2 m-4 bg-white/40 dark:bg-zinc-900/40 backdrop-blur-md rounded-2xl border border-white/50 dark:border-zinc-700/50 gap-2">
                    <a href="{{ route('masters.customers.show', $customer) }}" class="flex-1 text-center py-3 text-sm font-bold text-emerald-700 dark:text-emerald-400 bg-white/70 dark:bg-zinc-800/80 border border-black dark:border-white shadow-sm rounded-xl transition-all duration-300">
                        Quick Overview
                    </a>
                    @can('view customer bills')
                    <a href="{{ route('masters.customers.billing-history', $customer) }}" class="flex-1 text-center py-3 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-white/50 dark:hover:bg-zinc-800/50 rounded-xl transition-all duration-300">
                        Billing History
                    </a>
                    @endcan
                    @can('view customer payments')
                    <a href="{{ route('masters.customers.payment-history', $customer) }}" class="flex-1 text-center py-3 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-white/50 dark:hover:bg-zinc-800/50 rounded-xl transition-all duration-300">
                        Payment History
                    </a>
                    @endcan
                    @can('view customer emis')
                    <a href="{{ route('masters.customers.emi-history', $customer) }}" class="flex-1 text-center py-3 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-white/50 dark:hover:bg-zinc-800/50 rounded-xl transition-all duration-300">
                        EMI Schedule
                    </a>
                    @endcan
                </div>

                <div class="p-6">
                    <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider mb-6">Recent Activity Insights</h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <x-stat-card title="Last Bill Date" color="blue" icon="calendar_today" value="{{ $latestBill ? ($latestBill instanceof \App\Models\WeeklyBill ? $latestBill->period_end->format('d M Y') : $latestBill->date->format('d M Y')) : 'No bills yet' }}" />
                        <x-stat-card title="Last Payment" color="emerald" icon="payments" value="{{ $latestPayment ? 'Rs ' . number_format($latestPayment->amount, 0) : 'N/A' }}" subtitle="{{ $latestPayment ? $latestPayment->date->format('d M') : '' }}" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                        <div class="p-4 rounded-2xl border border-white/60 dark:border-zinc-700 shadow-[inset_0_2px_4px_rgba(0,0,0,0.05)] bg-white/40 dark:bg-zinc-900/40 backdrop-blur-xl flex items-center gap-4 transition-all duration-300 hover:bg-white/60">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                <span class="material-symbols-rounded text-xl">receipt</span>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Total Bills</div>
                                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ $customer->weekly_bills_count + $customer->daily_bills_count }}
                                </div>
                                <div class="text-xs font-medium text-zinc-500 mt-0.5">({{ $customer->weekly_bills_count }} Whs / {{ $customer->daily_bills_count }} Ret)</div>
                            </div>
                        </div>

                        <div class="p-4 rounded-2xl border border-white/60 dark:border-zinc-700 shadow-[inset_0_2px_4px_rgba(0,0,0,0.05)] bg-white/40 dark:bg-zinc-900/40 backdrop-blur-xl flex items-center gap-4 transition-all duration-300 hover:bg-white/60">
                            <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                                <span class="material-symbols-rounded text-xl">done_all</span>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Total Payments</div>
                                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ $customer->payments_count }}
                                </div>
                            </div>
                        </div>

                        <div class="p-4 rounded-2xl border border-white/60 dark:border-zinc-700 shadow-[inset_0_2px_4px_rgba(0,0,0,0.05)] bg-white/40 dark:bg-zinc-900/40 backdrop-blur-xl flex items-center gap-4 transition-all duration-300 hover:bg-white/60">
                            <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                <span class="material-symbols-rounded text-xl">account_balance_wallet</span>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Total Paid</div>
                                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-jetbrains">
                                    Rs {{ number_format($customer->payments_sum_amount ?? 0, 0) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-8 border-t border-zinc-200 dark:border-zinc-800">
                        <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider flex items-center gap-2 mb-6">
                            <span class="material-symbols-rounded text-emerald-600">shopping_bag</span>
                            Purchased Products Profile
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <div class="p-5 rounded-2xl border border-blue-200/60 dark:border-blue-800/30 bg-blue-50/30 dark:bg-blue-900/20 backdrop-blur-xl shadow-[inset_0_2px_4px_rgba(0,0,0,0.02)]">
                                <h5 class="text-xs font-bold text-blue-600 uppercase tracking-wider flex items-center gap-2 mb-4">
                                    <span class="material-symbols-rounded text-base">warehouse</span>
                                    Wholesale Products
                                </h5>
                                <div class="space-y-3">
                                    @forelse($topWholesaleProducts as $prod)
                                        <div class="flex items-center justify-between py-2 border-b border-blue-100 dark:border-blue-900/50 last:border-0">
                                            <div class="font-medium text-sm text-zinc-900 dark:text-zinc-100">{{ $prod['item_name'] }}</div>
                                            <div class="flex items-center gap-2">
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">{{ $prod['times_bought'] }}x</span>
                                                <span class="font-bold text-sm text-zinc-900 dark:text-zinc-100 font-jetbrains">{{ number_format($prod['total_qty'], 1) }} kg</span>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-xs text-center text-zinc-500 py-4">No wholesale product purchases recorded.</div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="p-5 rounded-2xl border border-emerald-200/60 dark:border-emerald-800/30 bg-emerald-50/30 dark:bg-emerald-900/20 backdrop-blur-xl shadow-[inset_0_2px_4px_rgba(0,0,0,0.02)]">
                                <h5 class="text-xs font-bold text-emerald-600 uppercase tracking-wider flex items-center gap-2 mb-4">
                                    <span class="material-symbols-rounded text-base">storefront</span>
                                    Retail Products
                                </h5>
                                <div class="space-y-3">
                                    @forelse($topRetailProducts as $prod)
                                        <div class="flex items-center justify-between py-2 border-b border-emerald-100 dark:border-emerald-900/50 last:border-0">
                                            <div class="font-medium text-sm text-zinc-900 dark:text-zinc-100">{{ $prod->item_name }}</div>
                                            <div class="flex items-center gap-2">
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">{{ $prod->times_bought }}x</span>
                                                <span class="font-bold text-sm text-zinc-900 dark:text-zinc-100 font-jetbrains">{{ number_format($prod->total_qty, 1) }} kg</span>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-xs text-center text-zinc-500 py-4">No retail product purchases recorded.</div>
                                    @endforelse
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

@if(!request()->ajax())
        </div>
    </div>
</div>
@endif
@endsection
