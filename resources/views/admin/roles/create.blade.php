@extends('layouts.app')
@section('title', 'Add Role')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="mb-4">
        <a href="{{ route('admin.roles.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
            <span class="material-symbols-rounded text-[20px]">arrow_back</span>
            Back to Roles
        </a>
    </div>

    <x-page-header 
        title="Create New Role" 
        subtitle="Add a new system role to assign to users."
    />

    <x-card>
        <form action="{{ route('admin.roles.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 gap-6">
                <x-form.input 
                    name="name" 
                    label="Role Name" 
                    icon="badge" 
                    placeholder="e.g. Manager, Accountant, Sales Agent" 
                    required 
                />
                <p class="text-xs text-zinc-400 dark:text-zinc-500 -mt-4">
                    Use lowercase with underscores for system roles (e.g. <code class="font-jetbrains">store_keeper</code>). 
                    After creating, use <strong>Assign Permissions</strong> to configure access.
                </p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <x-button href="{{ route('admin.roles.index') }}" variant="ghost">Cancel</x-button>
                <x-button type="submit" variant="primary" icon="save">Save Role</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
