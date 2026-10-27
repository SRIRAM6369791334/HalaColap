@extends('layouts.app')
@section('title', 'SaaS Business Tenants')

@section('content')
<div class="space-y-6">
    <x-page-header 
        title="Business Accounts (SaaS Tenants)" 
        subtitle="Manage independent business tenants, reseller subscriptions, and platform access"
    >
        <x-slot:actions>
            <a href="{{ route('admin.tenants.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm shadow-sm transition-colors">
                <span class="material-symbols-rounded text-lg">add_business</span>
                Onboard New Business
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card title="Total Businesses" value="{{ $tenants->total() }}" icon="domain" color="emerald" />
        <x-stat-card title="Active Subscriptions" value="{{ $tenants->where('is_active', true)->count() }}" icon="check_circle" color="blue" />
        <x-stat-card title="Total Resellers" value="{{ $resellers->count() }}" icon="storefront" color="amber" />
        <x-stat-card title="Platform Role" value="Super Admin" icon="shield_person" color="purple" />
    </div>

    {{-- Search & Filters --}}
    <x-card>
        <form method="GET" action="{{ route('admin.tenants.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-zinc-500 mb-1">Search Business</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, company, email, slug..."
                       class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
            </div>
            <div>
                <label class="block text-xs font-semibold text-zinc-500 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 rounded-xl text-sm font-semibold hover:opacity-90 transition-opacity">
                    Filter
                </button>
                <a href="{{ route('admin.tenants.index') }}" class="px-3 py-2 border border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400 rounded-xl text-sm font-semibold hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </x-card>

    {{-- Tenants Table --}}
    <x-card padding="p-0">
        <x-data-table :headers="['Business Profile', 'Domain / Slug', 'Users & Dealers', 'Reseller', 'Status', 'Actions']">
            @forelse($tenants as $tenant)
                <tr class="hover:bg-white/80 dark:hover:bg-zinc-800/50 transition-all duration-300">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500/20 to-teal-500/20 flex items-center justify-center font-bold text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                {{ strtoupper(substr($tenant->name, 0, 2)) }}
                            </div>
                            <div>
                                <div class="font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                    {{ $tenant->name }}
                                    @if($tenant->id === 1)
                                        <span class="px-1.5 py-0.5 text-[9px] bg-emerald-600 text-white rounded font-bold uppercase tracking-wider">Primary (MSR)</span>
                                    @endif
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $tenant->email ?: 'No email' }} • {{ $tenant->phone ?: 'No phone' }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-jetbrains text-zinc-700 dark:text-zinc-300">{{ $tenant->slug }}</div>
                        <div class="text-xs text-zinc-400">{{ $tenant->currency }} • {{ $tenant->timezone }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-xs space-y-0.5">
                            <div class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $tenant->users_count }} Staff User(s)</div>
                            <div class="text-zinc-500">{{ $tenant->dealers_count }} Dealer(s)</div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @if($tenant->reseller)
                            <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 font-semibold border border-amber-200/60 dark:border-amber-800/40">
                                <span class="material-symbols-rounded text-xs">storefront</span>
                                {{ $tenant->reseller->company_name }}
                            </span>
                        @else
                            <span class="text-xs text-zinc-400">Direct Platform</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <form action="{{ route('admin.tenants.toggle-status', $tenant) }}" method="POST">
                            @csrf
                            <button type="submit" 
                                    {{ $tenant->id === 1 ? 'disabled title="Cannot suspend Primary Tenant"' : '' }}
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition-colors duration-200 ease-in-out focus:outline-none {{ $tenant->is_active ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-700' }} {{ $tenant->id === 1 ? 'opacity-80 cursor-not-allowed' : '' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $tenant->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                        </form>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            {{-- Quick Switch Button --}}
                            <form action="{{ route('tenant.switch') }}" method="POST" class="inline m-0">
                                @csrf
                                <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
                                <button type="submit" title="Switch into this tenant context"
                                        class="p-1.5 rounded-lg border border-emerald-200 dark:border-emerald-800/60 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 transition-colors">
                                    <span class="material-symbols-rounded text-sm">login</span>
                                </button>
                            </form>

                            {{-- Edit Button --}}
                            <a href="{{ route('admin.tenants.edit', $tenant) }}" title="Edit Business Details"
                               class="p-1.5 rounded-lg border border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                <span class="material-symbols-rounded text-sm">edit</span>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-zinc-500">
                        No business accounts found. Click "Onboard New Business" to add your first tenant.
                    </td>
                </tr>
            @endforelse
        </x-data-table>
        
        <div class="px-6 py-4 border-t border-zinc-100 dark:border-zinc-800">
            {{ $tenants->links() }}
        </div>
    </x-card>
</div>
@endsection
