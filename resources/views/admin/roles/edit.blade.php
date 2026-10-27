@extends('layouts.app')
@section('title', 'Edit Role')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="mb-4">
        <a href="{{ route('admin.roles.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
            <span class="material-symbols-rounded text-[20px]">arrow_back</span>
            Back to Roles
        </a>
    </div>

    <x-page-header 
        title="Edit Role" 
        subtitle="Update the details of the {{ $role->name }} role."
    />

    @if($role->name === 'admin' || $role->name === 'Super Admin')
        <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 flex items-center gap-3">
            <span class="material-symbols-rounded text-amber-500">warning</span>
            <p class="text-sm text-amber-800 dark:text-amber-300 font-medium">This is a protected system role. Its name cannot be changed.</p>
        </div>
    @endif

    <x-card>
        <form action="{{ route('admin.roles.update', $role->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 gap-6">
                @if($role->name === 'admin' || $role->name === 'Super Admin')
                    <x-form.input 
                        name="name" 
                        label="Role Name" 
                        icon="badge" 
                        value="{{ $role->name }}"
                        readonly
                        class="opacity-60 cursor-not-allowed"
                        required 
                    />
                @else
                    <x-form.input 
                        name="name" 
                        label="Role Name" 
                        icon="badge" 
                        value="{{ $role->name }}"
                        required 
                    />
                @endif
            </div>

            <div class="grid grid-cols-2 gap-4 p-4 bg-zinc-50 dark:bg-zinc-800/40 rounded-xl border border-zinc-200/60 dark:border-zinc-700/60">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-zinc-400 mb-1">Guard</div>
                    <div class="font-mono text-sm text-zinc-700 dark:text-zinc-300">{{ $role->guard_name }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-zinc-400 mb-1">Permissions</div>
                    <div class="font-mono text-sm text-zinc-700 dark:text-zinc-300">{{ $role->permissions->count() }} assigned</div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <x-button href="{{ route('admin.roles.assignPermissionPage', $role->id) }}" variant="secondary" icon="vpn_key">
                    Manage Permissions
                </x-button>
                <div class="flex gap-3">
                    <x-button href="{{ route('admin.roles.index') }}" variant="ghost">Cancel</x-button>
                    @if($role->name !== 'admin' && $role->name !== 'Super Admin')
                        <x-button type="submit" variant="primary" icon="save">Update Role</x-button>
                    @endif
                </div>
            </div>
        </form>
    </x-card>
</div>
@endsection
