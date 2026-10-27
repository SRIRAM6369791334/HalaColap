@extends('layouts.app')
@section('title', 'Edit Permission')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="mb-4">
        <a href="{{ route('admin.permissions.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
            <span class="material-symbols-rounded text-[20px]">arrow_back</span>
            Back to Permissions
        </a>
    </div>

    <x-page-header 
        title="Edit Permission" 
        subtitle="Update the access control permission details."
    />

    <x-card>
        <form action="{{ route('admin.permissions.update', $permission->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-form.input 
                    name="name" 
                    label="Permission Name" 
                    icon="vpn_key" 
                    value="{{ $permission->name }}" 
                    required 
                />

                <x-form.select 
                    name="permission_group_id" 
                    label="Permission Group" 
                    :options="$permissionGroups->pluck('name', 'id')->toArray()" 
                    :selected="$permission->permission_group_id"
                    placeholder="Select Group"
                    required
                />
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <x-button href="{{ route('admin.permissions.index') }}" variant="ghost">Cancel</x-button>
                <x-button type="submit" variant="primary" icon="save">Update Permission</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
