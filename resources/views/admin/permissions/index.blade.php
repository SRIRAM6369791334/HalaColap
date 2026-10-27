@extends('layouts.app')
@section('title', 'Manage Permissions')

@section('content')
<div class="space-y-6">
    <x-page-header 
        title="Permission Management" 
        subtitle="Manage granular access permissions grouped by category"
    >
        <x-slot:actions>
            @can('create permissions')
                <x-button href="{{ route('admin.permissions.create') }}" variant="primary" icon="add">
                    Add Permission
                </x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-stat-card title="Total Permissions" value="{{ $permissions->total() }}" icon="key" color="emerald" />
        <x-stat-card title="Permission Groups" value="{{ $allGroups->count() }}" icon="category" color="blue" />
        <x-stat-card title="Web Guards" value="{{ $permissions->where('guard_name', 'web')->count() }}" icon="security" color="amber" />
    </div>

    {{-- Groups Summary --}}
    <x-card>
        <div class="mb-3 text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Permission Groups</div>
        <div class="flex flex-wrap gap-2">
            @foreach($allGroups as $grp)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700">
                    <span class="material-symbols-rounded text-[14px] text-blue-500">folder</span>
                    {{ $grp->name }}
                    <span class="ml-1 bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300 px-1.5 py-0.5 rounded-full text-[10px] font-bold">{{ $grp->permissions_count }}</span>
                </span>
            @endforeach
        </div>
    </x-card>

    {{-- Permissions Table --}}
    <x-card padding="p-0">
        <x-data-table :headers="['Permission Name', 'Group', 'Guard', 'Actions']">
            @forelse($permissions as $permission)
                <tr class="hover:bg-white/80 dark:hover:bg-zinc-800/50 transition-all duration-300 group">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400 border border-emerald-100/50">
                                <span class="material-symbols-rounded text-sm">vpn_key</span>
                            </div>
                            <div>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ ucwords($permission->name) }}</span>
                                <span class="block text-[10px] font-jetbrains text-zinc-400 tracking-wide">{{ $permission->name }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <x-badge variant="info">{{ $groups[$permission->permission_group_id] ?? 'Uncategorized' }}</x-badge>
                    </td>
                    <td class="px-6 py-4">
                        <x-badge variant="secondary">{{ $permission->guard_name }}</x-badge>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            @can('manage permissions')
                                <x-button href="{{ route('admin.permissions.edit', $permission->id) }}" variant="ghost" size="sm" icon="edit" title="Edit" />
                                <form action="{{ route('admin.permissions.destroy', $permission->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete permission \'{{ $permission->name }}\'? Roles with this permission will lose access.');">
                                    @csrf @method('DELETE')
                                    <x-button type="submit" variant="ghost" size="sm" icon="delete" class="text-rose-500 hover:text-rose-600" title="Delete" />
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center">
                        <x-empty-state 
                            icon="key_off" 
                            title="No permissions found" 
                            subtitle="Create granular permissions to control access." 
                        />
                    </td>
                </tr>
            @endforelse
        </x-data-table>

        {{-- Pagination --}}
        @if($permissions->hasPages())
            <div class="px-6 py-4 border-t border-zinc-100 dark:border-zinc-800">
                {{ $permissions->links() }}
            </div>
        @endif
    </x-card>
</div>
@endsection
