@extends('layouts.app')
@section('title', 'Dealer Master')

@section('content')
<div class="space-y-6">
    <x-page-header 
        title="Dealer Master" 
        subtitle="Manage relationships with feed, chick, and medicine suppliers"
    >
        <x-slot:actions>
            @can('create dealers')
                <x-button href="{{ route('masters.dealers.create') }}" variant="primary" icon="add">
                    Register Dealer
                </x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-stat-card title="Total Dealers" value="{{ $dealers->total() }}" icon="group" color="emerald" />
        <x-stat-card title="Total Receivable" value="{{ number_format($totalOutstanding, 0) }}" icon="warning" color="rose" prefix="Rs " />
        <x-stat-card title="Active Accounts" value="{{ $activeDealersCount }}" icon="account_balance" color="amber" subtitle="with dues" />
    </div>

    <x-card padding="p-0">
        <div class="p-5 flex flex-wrap gap-4 items-center justify-between relative z-10 border-b border-white/40">
            <form action="{{ route('masters.dealers.index') }}" method="GET" class="flex flex-wrap gap-4 items-center w-full md:w-auto">
                <div class="w-full md:w-64">
                    <x-search name="search" value="{{ request('search') }}" placeholder="Search firm, contact..." />
                </div>
                
                <div class="w-full md:w-64">
                    <x-form.select 
                        name="balance" 
                        :options="['' => 'All Dealers', 'pending' => 'Owe Money (Pending Balance)', 'cleared' => 'Settled (No Balance)']" 
                        :selected="request('balance')" 
                        onchange="this.form.submit()" 
                    />
                </div>

                @if(request('search') || request('balance'))
                    <x-button href="{{ route('masters.dealers.index') }}" variant="secondary" size="md">Clear</x-button>
                @endif
            </form>
        </div>

        <x-data-table :headers="['Order #', 'Firm & Location', 'Point of Contact', 'Operational Area', 'Pending Balance', 'Actions']">
            @forelse($dealers as $dealer)
                <tr class="hover:bg-white/80 dark:hover:bg-zinc-800/50 transition-all duration-300 group">
                    <td class="px-4 py-4 text-center font-jetbrains">
                        @if($dealer->sort_order)
                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-lg text-xs font-black bg-amber-100 dark:bg-amber-950/70 text-amber-800 dark:text-amber-300 border border-amber-300/60 dark:border-amber-700/60 shadow-2xs">
                                #{{ $dealer->sort_order }}
                            </span>
                        @else
                            <span class="text-xs text-zinc-400 font-mono">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <x-avatar name="{{ $dealer->firm_name }}" size="sm" />
                            <div>
                                <a href="{{ route('masters.dealers.show', $dealer) }}" class="font-medium text-zinc-900 dark:text-zinc-100 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                    {{ $dealer->firm_name }}
                                </a>
                                <div class="text-xs text-zinc-500">{{ $dealer->location ?: 'No Location' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $dealer->contact_person ?: '-' }}</div>
                        <div class="text-xs text-zinc-500">{{ $dealer->phone }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $dealer->route ?: 'General' }}</span>
                    </td>
                    <td class="px-6 py-4 font-jetbrains">
                        @if($dealer->displayed_outstanding > 0)
                            <span class="inline-flex items-center px-2 py-1 rounded-lg bg-rose-50 text-rose-500 dark:bg-rose-500/10 dark:text-rose-400 font-medium border border-rose-100 dark:border-rose-500/20"><x-currency :amount="$dealer->displayed_outstanding" /></span>
                        @else
                            <span class="text-emerald-600 dark:text-emerald-400"><x-currency :amount="0" /></span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <x-button href="{{ route('masters.dealers.ledger-pdf', $dealer) }}" variant="ghost" size="sm" icon="receipt_long" title="Download Ledger" />
                            @can('edit dealers')
                                <x-button href="{{ route('masters.dealers.edit', $dealer) }}" variant="ghost" size="sm" icon="edit" title="Edit" />
                            @endcan
                            @can('delete dealers')
                                <form action="{{ route('masters.dealers.destroy', $dealer) }}" method="POST" class="inline" onsubmit="return confirm('Delete {{ $dealer->firm_name }}?');">
                                    @csrf @method('DELETE')
                                    <x-button type="submit" variant="ghost" size="sm" icon="delete" class="text-rose-500 hover:text-rose-600" title="Delete" />
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <x-empty-state 
                            icon="store" 
                            title="No dealers found" 
                            subtitle="Start by registering your first supplier." 
                        />
                    </td>
                </tr>
            @endforelse

            <x-slot:pagination>
                {{ $dealers->withQueryString()->links() }}
            </x-slot:pagination>
        </x-data-table>
    </x-card>
</div>
@endsection
