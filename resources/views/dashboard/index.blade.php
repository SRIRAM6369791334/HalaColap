@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div>
    
    {{-- Page Header --}}
    @php
        $tenantName = app(\App\Services\Tenancy\TenantContext::class)->getTenant()?->name ?? 'MSR BROILERS';
    @endphp
    <x-page-header title="Executive Dashboard" subtitle="Real-time overview of {{ $tenantName }} operations, financials, and inventory.">
        <x-slot name="actions">
            <x-button href="{{ route('reports.index') }}" variant="ghost" icon="download">
                <x-slot name="icon">
                    <span class="material-symbols-rounded text-[18px]">download</span>
                </x-slot>
                Export Report
            </x-button>
            <x-button href="{{ route('billing.day-load.index') }}" variant="primary" icon="add">
                <x-slot name="icon">
                    <span class="material-symbols-rounded text-[18px]">add</span>
                </x-slot>
                New Entry
            </x-button>
        </x-slot>
    </x-page-header>

    {{-- Stats Grid (Bento Box) --}}
    <section class="grid grid-cols-1 gap-6 md:grid-cols-3 mb-8">
        @php
            $pb = $stats['profitBreakdown'] ?? [];
            $statCards = [
                [
                    'label'    => 'Total Billed Amount', 
                    'value'    => '₹' . number_format($pb['total_billed'] ?? 0, 2), 
                    'subtitle' => 'MTD Total Billed Sales', 
                    'icon'     => 'receipt_long', 
                    'color'    => 'emerald'
                ],
                [
                    'label'    => 'Total Vendor Cost', 
                    'value'    => '₹' . number_format($pb['vendor_cost'] ?? 0, 2), 
                    'subtitle' => 'MTD Feed & Supply Cost', 
                    'icon'     => 'inventory_2', 
                    'color'    => 'indigo'
                ],
                [
                    'label'    => 'Total Expenses', 
                    'value'    => '₹' . number_format($pb['total_expenses'] ?? 0, 2), 
                    'subtitle' => 'MTD Operating Burn & EMIs', 
                    'icon'     => 'trending_up', 
                    'color'    => 'rose'
                ],
            ];
        @endphp

        @foreach($statCards as $card)
            <div>
                <x-stat-card 
                    :label="$card['label']"
                    :value="$card['value']"
                    :subtitle="$card['subtitle']"
                    :icon="$card['icon']"
                    :color="$card['color']">
                </x-stat-card>
            </div>
        @endforeach
    </section>


    {{-- Bottom Grid --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
         
        {{-- Recent Activity Table --}}
        <div class="xl:col-span-2">
            <x-card title="Recent Transactions" subtitle="Latest sales and billing flow" padding="p-0">
                <x-slot name="actions">
                    <a href="{{ route('billing.daily.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">View All</a>
                </x-slot>
                
                <div class="p-4 sm:p-6">
                    <x-data-table :headers="['Customer', 'Items', 'Amount', 'Status']">
                        @forelse($recentSales as $sale)
                            <tr class="transition-colors hover:bg-zinc-50/80 dark:hover:bg-zinc-800/50 group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-zinc-100 text-sm font-bold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 shadow-sm border border-zinc-200/50 dark:border-zinc-700/50">
                                            {{ substr($sale->customer->name ?? '?', 0, 1) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $sale->customer->name ?? 'System User' }}</p>
                                            <p class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400 font-jetbrains">{{ $sale->date->format('d M, h:i A') }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-xs font-medium text-zinc-600 dark:text-zinc-400 max-w-[150px] truncate">
                                        {{ $sale->items->pluck('item_name')->join(', ') }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-zinc-900 dark:text-zinc-100 font-jetbrains">₹{{ number_format($sale->net_amount, 0) }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusVariant = match(strtolower($sale->status)) {
                                            'completed', 'paid' => 'success',
                                            'pending' => 'warning',
                                            'cancelled', 'failed' => 'danger',
                                            default => 'neutral'
                                        };
                                    @endphp
                                    <x-badge variant="{{ $statusVariant }}" dot="true">
                                        {{ $sale->status }}
                                    </x-badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-zinc-500 dark:text-zinc-400">
                                        <span class="material-symbols-rounded text-4xl mb-2 opacity-50">receipt_long</span>
                                        <p class="text-sm">No recent transactions found.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </x-data-table>
                </div>
            </x-card>
        </div>

        {{-- Financial Alerts & Summary --}}
        <div class="space-y-6">
            {{-- Upcoming Dues --}}
            <x-card title="Pending Dues" padding="p-6">
                <x-slot name="actions">
                    <x-badge variant="danger" size="sm">Requires Action</x-badge>
                </x-slot>

                <div class="space-y-4">
                    @forelse($upcomingEmis as $emi)
                        <div class="flex items-center justify-between rounded-xl border border-zinc-200/50 dark:border-zinc-700/50 bg-zinc-50/50 dark:bg-zinc-800/30 p-4 transition-all hover:border-zinc-300 dark:hover:border-zinc-600 hover:shadow-sm">
                            <div>
                                <p class="text-xs font-bold text-zinc-900 dark:text-zinc-100">{{ $emi->item }}</p>
                                <p class="mt-1 text-[10px] font-medium text-zinc-500 dark:text-zinc-400">Due: {{ $emi->due_date->format('d M') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-zinc-900 dark:text-zinc-100 font-jetbrains">₹{{ number_format($emi->amount, 0) }}</p>
                                <p class="mt-1 text-[10px] font-bold text-rose-600 dark:text-rose-400">{{ now()->diffInDays($emi->due_date) }}d left</p>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-zinc-200 dark:border-zinc-700 p-6 text-center bg-zinc-50/50 dark:bg-zinc-800/30">
                            <span class="material-symbols-rounded text-3xl text-zinc-300 dark:text-zinc-600 mb-2">verified</span>
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">No pending dues right now.</p>
                        </div>
                    @endforelse
                </div>
            </x-card>

            {{-- Summary Card (Dark Theme) --}}
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-6 text-white shadow-xl relative overflow-hidden group">
                <!-- Decorative background elements -->
                <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl -mr-10 -mt-10 transition-transform group-hover:scale-150 duration-700"></div>
                <div class="absolute bottom-0 left-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl -ml-10 -mb-10 transition-transform group-hover:scale-150 duration-700"></div>
                
                <div class="relative z-10">
                    <h3 class="mb-6 text-sm font-bold flex items-center gap-2 font-cabinet">
                        <span class="material-symbols-rounded text-[18px] text-emerald-400">account_balance</span>
                        Financial Health
                    </h3>
                    
                    <div class="space-y-5">
                        <div class="flex justify-between items-end border-b border-white/10 pb-4">
                            <div>
                                <p class="text-[10px] font-semibold text-zinc-400 uppercase tracking-wider mb-1 font-outfit">MTD Net Revenue</p>
                                <p class="text-xl font-bold font-jetbrains">₹{{ number_format($stats['monthlyRevenue'], 0) }}</p>
                            </div>
                            <span class="material-symbols-rounded text-emerald-400">trending_up</span>
                        </div>
                        <div class="flex justify-between items-end border-b border-white/10 pb-4">
                            <div>
                                <p class="text-[10px] font-semibold text-zinc-400 uppercase tracking-wider mb-1 font-outfit">Exposure</p>
                                <p class="text-xl font-bold text-rose-400 font-jetbrains">₹{{ number_format($stats['pendingPayments'], 0) }}</p>
                            </div>
                            <span class="material-symbols-rounded text-rose-400">error_outline</span>
                        </div>
                        <div class="flex justify-between items-end">
                            <div>
                                <p class="text-[10px] font-semibold text-zinc-400 uppercase tracking-wider mb-1 font-outfit">Active Partners</p>
                                <p class="text-xl font-bold text-emerald-400 font-jetbrains">{{ $stats['activeDealers'] }}</p>
                            </div>
                            <span class="material-symbols-rounded text-emerald-400">handshake</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
