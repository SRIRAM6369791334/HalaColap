@extends(request()->ajax() ? 'layouts.empty' : 'layouts.app')
@section('title', 'Billing History - ' . $customer->name)

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
            <x-button href="{{ route('masters.customers.edit', $customer) }}" variant="secondary" icon="edit">Edit Profile</x-button>
            <form action="{{ route('masters.customers.destroy', $customer) }}" method="POST" onsubmit="return confirm('Delete {{ $customer->name }}? This will keep their transaction history intact.')">
                @csrf @method('DELETE')
                <x-button type="submit" variant="danger" icon="delete">Delete</x-button>
            </form>
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
                        <x-button href="{{ route('payments.customers.create', ['customer_id' => $customer->id]) }}" variant="secondary" icon="payments" class="w-full justify-center !text-rose-700 !bg-white/80 hover:!bg-white !border-white backdrop-blur-md shadow-sm">
                            Record Payment
                        </x-button>
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
@endif
            <div id="cm-tabs-container" x-data="ajaxTabs" @click="handleTabClick" @mouseover="prefetchTab" @popstate.window="window.location.reload()" class="bg-white/30 dark:bg-zinc-900/40 backdrop-blur-2xl border border-white/60 dark:border-zinc-800/80 rounded-[2rem] overflow-hidden shadow-[0_8px_32px_rgba(31,38,135,0.07)] z-10 relative">
                
                <div class="flex flex-wrap p-2 m-4 bg-white/40 dark:bg-zinc-900/40 backdrop-blur-md rounded-2xl border border-white/50 dark:border-zinc-700/50 gap-2">
                    <a href="{{ route('masters.customers.show', $customer) }}" class="flex-1 text-center py-3 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-white/50 dark:hover:bg-zinc-800/50 rounded-xl transition-all duration-300">
                        Quick Overview
                    </a>
                    <a href="{{ route('masters.customers.billing-history', $customer) }}" class="flex-1 text-center py-3 text-sm font-bold text-emerald-700 dark:text-emerald-400 bg-white/70 dark:bg-zinc-800/80 border border-black dark:border-white shadow-sm rounded-xl transition-all duration-300">
                        Billing History
                    </a>
                    <a href="{{ route('masters.customers.payment-history', $customer) }}" class="flex-1 text-center py-3 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-white/50 dark:hover:bg-zinc-800/50 rounded-xl transition-all duration-300">
                        Payment History
                    </a>
                    <a href="{{ route('masters.customers.emi-history', $customer) }}" class="flex-1 text-center py-3 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-white/50 dark:hover:bg-zinc-800/50 rounded-xl transition-all duration-300">
                        EMI Schedule
                    </a>
                </div>

                <div class="p-0">
                    @php
                        $activeSubTab = request()->has('daily_page') ? 'retail' : 'wholesale';
                    @endphp
                    
                    <div class="p-6 border-b border-zinc-200 dark:border-zinc-800">
                        <div class="flex items-center justify-between mb-6">
                            <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider">Complete Billing Ledger</h4>
                            <x-button href="{{ route('billing.weekly.export', ['customer_id' => $customer->id]) }}" variant="secondary" size="sm" icon="download">Export</x-button>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            <x-stat-card title="Total Bills" value="{{ $weeklyBills->total() + $dailyBills->total() }}" color="zinc" />
                            <x-stat-card title="Total Billed" value="{{ number_format($totalBilled, 0) }}" prefix="Rs " color="blue" />
                            <x-stat-card title="Avg. Bill Value" value="{{ number_format(($weeklyBills->total() + $dailyBills->total()) > 0 ? $totalBilled / ($weeklyBills->total() + $dailyBills->total()) : 0, 0) }}" prefix="Rs " color="emerald" />
                            <x-stat-card title="Current Due" value="{{ number_format($customer->balance, 0) }}" prefix="Rs " color="rose" />
                        </div>

                        <div x-data="{ activeSubTab: '{{ $activeSubTab }}' }">
                            <div class="flex gap-2 p-1 bg-white/40 dark:bg-zinc-900/40 backdrop-blur-md rounded-xl border border-white/50 dark:border-zinc-700/50 mb-6 inline-flex">
                                <button @click="activeSubTab = 'wholesale'" :class="{'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 shadow-sm font-bold': activeSubTab === 'wholesale', 'text-zinc-500 hover:text-zinc-700': activeSubTab !== 'wholesale'}" class="px-4 py-2 text-sm rounded-lg transition-all duration-300">
                                    Wholesale Weekly ({{ $weeklyBills->total() }})
                                </button>
                                <button @click="activeSubTab = 'retail'" :class="{'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 shadow-sm font-bold': activeSubTab === 'retail', 'text-zinc-500 hover:text-zinc-700': activeSubTab !== 'retail'}" class="px-4 py-2 text-sm rounded-lg transition-all duration-300">
                                    Retail Counter ({{ $dailyBills->total() }})
                                </button>
                            </div>

                            <div class="w-full" x-show="activeSubTab === 'wholesale'" style="display: none;">
                                <x-data-table :headers="['Bill ID', 'Period End', 'Products', ['label' => 'Qty', 'align' => 'right'], ['label' => 'Amount', 'align' => 'right'], ['label' => 'Status', 'align' => 'center'], ['label' => 'Actions', 'align' => 'center']]">
                                    @forelse($weeklyBills as $bill)
                                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50">
                                            <td class="px-6 py-4 font-mono text-sm">#WB-{{ str_pad($bill->id, 5, '0', STR_PAD_LEFT) }}</td>
                                            <td class="px-6 py-4">
                                                <div class="font-bold text-sm">{{ $bill->period_end->format('d M Y') }}</div>
                                                <div class="text-xs text-zinc-500">{{ $bill->period_start->format('d M') }} - {{ $bill->period_end->format('d M') }}</div>
                                            </td>
                                            <td class="px-6 py-4 text-sm">
                                                @if($bill->items_description)
                                                    {{ Str::limit($bill->items_description, 50) }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-right font-mono text-sm">{{ number_format($bill->quantity_kg, 1) }}</td>
                                            <td class="px-6 py-4 text-right font-bold text-sm font-jetbrains">Rs {{ number_format($bill->amount, 0) }}</td>
                                            <td class="px-6 py-4 text-center">
                                                @if($bill->status === 'Paid')
                                                    <x-badge color="emerald">Paid</x-badge>
                                                @elseif($bill->status === 'Pending')
                                                    <x-badge color="rose">Pending</x-badge>
                                                @else
                                                    <x-badge color="blue">Generated</x-badge>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <x-button href="{{ route('billing.weekly.show', $bill) }}" variant="ghost" size="sm" icon="visibility" />
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center py-8 text-zinc-500">No wholesale billing records found.</td></tr>
                                    @endforelse
                                    @if($weeklyBills->hasPages())
                                        <x-slot:pagination>
                                            {!! $weeklyBills->appends(request()->except('weekly_page'))->links() !!}
                                        </x-slot:pagination>
                                    @endif
                                </x-data-table>
                            </div>

                            <div class="w-full mt-4" x-show="activeSubTab === 'retail'" style="display: none;">
                                <x-data-table :headers="['Invoice ID', 'Date', 'Products', ['label' => 'Qty', 'align' => 'right'], ['label' => 'Amount', 'align' => 'right'], ['label' => 'Status', 'align' => 'center'], ['label' => 'Actions', 'align' => 'center']]">
                                    @forelse($dailyBills as $bill)
                                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50">
                                            <td class="px-6 py-4 font-mono text-sm">#DB-{{ str_pad($bill->id, 5, '0', STR_PAD_LEFT) }}</td>
                                            <td class="px-6 py-4 font-bold text-sm">{{ $bill->date->format('d M Y') }}</td>
                                            <td class="px-6 py-4 text-sm">
                                                @forelse($bill->items as $item)
                                                    {{ $item->item_name }} ({{ number_format($item->quantity_kg, 1) }}kg){{ !$loop->last ? ', ' : '' }}
                                                @empty
                                                    -
                                                @endforelse
                                            </td>
                                            <td class="px-6 py-4 text-right font-mono text-sm">{{ number_format($bill->items->sum('quantity_kg'), 1) }}</td>
                                            <td class="px-6 py-4 text-right font-bold text-sm font-jetbrains">Rs {{ number_format($bill->amount, 0) }}</td>
                                            <td class="px-6 py-4 text-center">
                                                @if($bill->status === 'Paid')
                                                    <x-badge color="emerald">Paid</x-badge>
                                                @elseif($bill->status === 'Pending')
                                                    <x-badge color="rose">Pending</x-badge>
                                                @else
                                                    <x-badge color="blue">Generated</x-badge>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <x-button href="{{ route('billing.daily.invoice', $bill) }}" target="_blank" variant="ghost" size="sm" icon="print" />
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center py-8 text-zinc-500">No retail counter billing records found.</td></tr>
                                    @endforelse
                                    @if($dailyBills->hasPages())
                                        <x-slot:pagination>
                                            {!! $dailyBills->appends(request()->except('daily_page'))->links() !!}
                                        </x-slot:pagination>
                                    @endif
                                </x-data-table>
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
