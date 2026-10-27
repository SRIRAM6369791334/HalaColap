@extends('layouts.app')
@section('title', 'Customer Master')

@section('content')
<div class="space-y-6">
    <x-page-header 
        title="Customer Master" 
        subtitle="Directory of retail buyers and wholesale partners"
    >
        <x-slot:actions>
            @can('view payments')
                <x-button href="{{ route('payments.customers.index') }}" variant="outline" icon="payments">
                    Customer Payments
                </x-button>
            @endcan
            @can('create bills')
                <x-button href="{{ route('billing.daily.create') }}" variant="outline" icon="receipt_long">
                    Customer Billing
                </x-button>
            @endcan
            @can('create customers')
                <x-button href="{{ route('masters.customers.create') }}" variant="primary" icon="add">
                    Register Customer
                </x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-stat-card title="Total Active" value="{{ $customers->total() }}" icon="group" color="emerald" />
        <x-stat-card title="Wholesale" value="{{ $customers->where('type', 'Wholesale')->count() }}" icon="warehouse" color="blue" />
        <x-stat-card title="Retail" value="{{ $customers->where('type', 'Retail')->count() }}" icon="storefront" color="amber" />
        <x-stat-card title="With Balance" value="{{ $customers->where('balance', '>', 0)->count() }}" icon="warning" color="rose" />
    </div>

    <x-card padding="p-0">
        <div class="p-5 flex flex-wrap gap-4 items-center justify-between relative z-10 border-b border-white/40">
            <form action="{{ route('masters.customers.index') }}" method="GET" class="flex flex-wrap gap-4 items-center w-full lg:w-auto">
                <div class="w-full sm:w-64">
                    <x-search name="search" value="{{ request('search') }}" placeholder="Search name, phone..." />
                </div>
                
                <div class="w-full sm:w-40">
                    <x-form.select 
                        name="type" 
                        :options="['' => 'All Types', 'Retail' => 'Retail', 'Wholesale' => 'Wholesale']" 
                        :selected="request('type')" 
                        onchange="this.form.submit()" 
                    />
                </div>
                
                <div class="w-full sm:w-48">
                    <x-form.select 
                        name="balance" 
                        :options="['' => 'All Balances', 'pending' => 'Has Pending Balance', 'cleared' => 'Cleared (No Balance)']" 
                        :selected="request('balance')" 
                        onchange="this.form.submit()" 
                    />
                </div>

                @if(request('search') || request('type') || request('balance'))
                    <x-button href="{{ route('masters.customers.index') }}" variant="secondary" size="md">Clear</x-button>
                @endif
            </form>
            
            <x-button href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" variant="secondary" icon="download">
                Export PDF
            </x-button>
        </div>

        <x-data-table :headers="['Customer', 'Contact', 'Route', 'Type', 'Outstanding', 'Actions']">
            @forelse($customers as $customer)
                <tr class="hover:bg-white/80 dark:hover:bg-zinc-800/50 transition-all duration-300 group">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <x-avatar name="{{ $customer->name }}" size="sm" />
                            <div>
                                <a href="{{ route('masters.customers.show', $customer) }}" class="font-medium text-zinc-900 dark:text-zinc-100 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                    {{ $customer->name }}
                                </a>
                                <div class="text-xs text-zinc-500">{{ $customer->gst_number ?: 'No GST' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $customer->phone }}</div>
                        <div class="text-xs text-zinc-500 truncate max-w-[150px]">{{ $customer->address ?: 'No address' }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $customer->route ?: 'General' }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <x-badge variant="{{ $customer->type === 'Wholesale' ? 'info' : 'warning' }}">
                            {{ $customer->type }}
                        </x-badge>
                    </td>
                    <td class="px-6 py-4 font-jetbrains">
                        @if($customer->balance > 0)
                            <span class="inline-flex items-center px-2 py-1 rounded-lg bg-rose-50 text-rose-500 dark:bg-rose-500/10 dark:text-rose-400 font-medium border border-rose-100 dark:border-rose-500/20"><x-currency :amount="$customer->balance" /></span>
                        @else
                            <span class="text-emerald-600 dark:text-emerald-400"><x-currency :amount="0" /></span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <x-button href="{{ route('masters.customers.ledger-pdf', $customer) }}" variant="ghost" size="sm" icon="receipt_long" title="Download Ledger" />
                            @can('edit customers')
                                <x-button href="{{ route('masters.customers.edit', $customer) }}" variant="ghost" size="sm" icon="edit" title="Edit" />
                            @endcan
                            @can('delete customers')
                                <form action="{{ route('masters.customers.destroy', $customer) }}" method="POST" class="inline" onsubmit="return confirm('Delete {{ $customer->name }}?');">
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
                            icon="group_off" 
                            title="No customers found" 
                            subtitle="Start by registering your first buyer." 
                        />
                    </td>
                </tr>
            @endforelse

            <x-slot:pagination>
                {{ $customers->withQueryString()->links() }}
            </x-slot:pagination>
        </x-data-table>
    </x-card>
</div>
@endsection
