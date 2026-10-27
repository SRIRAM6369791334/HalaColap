@extends('layouts.app')
@section('title', 'Customer Payments')

@section('content')

<div class="animate-fade-in">
    <x-page-header title="Customer Collections" subtitle="Manage inbound payments and customer ledgers">
        <x-slot:actions>
            <x-button variant="outline" href="{{ route('payments.customers.export') }}" icon="download">
                Export
            </x-button>
            @can('create payments')
            <x-button variant="primary" href="{{ route('payments.customers.create') }}" icon="add">
                Record Collection
            </x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-stat-card 
            label="Total Collected" 
            value="Rs {{ number_format($payments->sum('amount'), 0) }}" 
            icon="payments" 
            color="emerald" />
        <x-stat-card 
            label="Total Outstanding" 
            value="Rs {{ number_format($customers->where('balance', '>', 0)->sum('balance'), 0) }}" 
            icon="error" 
            color="rose" />
        <x-stat-card 
            label="Recent Collections" 
            value="{{ $payments->where('date', '>=', now()->subDays(7))->count() }}" 
            icon="history" 
            color="blue" 
            trend="This Week" 
            :trendUp="true" />
    </div>

    {{-- Table Card --}}
    <x-card>
        <div class="p-4 border-b border-white/40 dark:border-zinc-800/60 bg-white/40 dark:bg-zinc-900/40 backdrop-blur-md flex flex-wrap gap-4 items-center justify-between">
            <form method="GET" class="flex-1 min-w-[280px] max-w-md">
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-zinc-400 group-focus-within:text-emerald-500 transition-colors">
                        <span class="material-symbols-rounded text-[22px]">search</span>
                    </div>
                    <input type="text" name="search" value="{{ $search }}" class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-700/80 text-zinc-900 dark:text-zinc-100 text-sm rounded-2xl focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-400 block w-full pl-11 p-3 transition-all duration-300 placeholder:text-zinc-400 dark:placeholder:text-zinc-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.02)] hover:border-emerald-300/50" placeholder="Search customer or reference…" onchange="this.form.submit()">
                </div>
            </form>
            @if(request('search'))
                <x-button href="{{ route('payments.customers.index') }}" variant="secondary" size="md">Clear Search</x-button>
            @endif
        </div>
        
        <x-data-table :headers="['Customer', 'Collection Date', 'Amount Received', 'Payment Mode', 'Receipt Type', 'Balance After']">
            @forelse($payments as $p)
                <tr class="hover:bg-white/80 dark:hover:bg-zinc-800/50 transition-all duration-300 group">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <x-avatar name="{{ $p->customer->name ?? '?' }}" size="sm" />
                            <div>
                                <a href="{{ route('masters.customers.show', $p->customer_id) }}" class="font-medium text-zinc-900 dark:text-zinc-100 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                    {{ $p->customer->name ?? '-' }}
                                </a>
                                <p class="font-outfit text-xs text-zinc-500">{{ $p->customer->phone ?? 'NO PHONE' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $p->date->format('d M, Y') }}</p>
                        <p class="text-xs text-zinc-500">{{ $p->date->format('l') }}</p>
                    </td>
                    <td class="px-6 py-4 font-jetbrains font-medium text-emerald-600 dark:text-emerald-400 text-right">
                        <x-currency :amount="$p->amount" />
                    </td>
                    <td class="px-6 py-4 text-center">
                        <x-badge variant="zinc">{{ $p->payment_mode }}</x-badge>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php
                            $typeMap = [
                                'Full' => 'success',
                                'Part' => 'warning',
                                'Advance' => 'info',
                            ];
                            $badgeVariant = $typeMap[$p->payment_type] ?? 'zinc';
                        @endphp
                        <x-badge :variant="$badgeVariant">{{ strtoupper($p->payment_type) }}</x-badge>
                    </td>
                    <td class="px-6 py-4 text-right">
                        @if($p->balance_after > 0)
                            <span class="font-jetbrains text-rose-600 dark:text-rose-400 font-medium">
                                <x-currency :amount="$p->balance_after" />
                            </span>
                        @elseif($p->balance_after < 0)
                            <span class="font-jetbrains text-emerald-600 dark:text-emerald-400 font-medium">
                                Advance: <x-currency :amount="abs($p->balance_after)" />
                            </span>
                        @else
                            <x-badge variant="success" class="font-jetbrains">CLEARED</x-badge>
                        @endif
                    </td>
                </tr>
            @empty
                <x-slot:empty>
                    <x-empty-state 
                        icon="payments" 
                        title="No Collections Found" 
                        description="Record your first customer payment today." />
                </x-slot:empty>
            @endforelse

            @if($payments->hasPages())
                <x-slot:pagination>
                    {{ $payments->withQueryString()->links() }}
                </x-slot:pagination>
            @endif
        </x-data-table>
    </x-card>

@endsection
