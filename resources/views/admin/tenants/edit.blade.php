@extends('layouts.app')
@section('title', 'Edit Business Tenant')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold font-cabinet text-zinc-900 dark:text-white">Edit Business: {{ $tenant->name }}</h1>
            <p class="text-sm text-zinc-500">Update configuration, reseller association, or subscription status</p>
        </div>
        <a href="{{ route('admin.tenants.index') }}" class="px-3 py-2 text-sm font-semibold rounded-xl border border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
            Back to Tenants
        </a>
    </div>

    @if (session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 text-sm font-semibold flex items-center gap-2">
            <span class="material-symbols-rounded text-lg text-emerald-600">check_circle</span>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-200 text-sm">
            <div class="font-bold mb-1">Please fix the following errors:</div>
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.tenants.update', $tenant) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <x-card>
            <div class="border-b border-zinc-100 dark:border-zinc-800 pb-3 mb-4">
                <h2 class="text-lg font-bold font-cabinet text-zinc-900 dark:text-white flex items-center gap-2">
                    <span class="material-symbols-rounded text-emerald-600">store</span>
                    Business Settings
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Business Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $tenant->name) }}" required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Company Legal Name</label>
                    <input type="text" name="company_name" value="{{ old('company_name', $tenant->company_name) }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Email <span class="text-rose-500">* (Unique)</span></label>
                    <input type="email" name="email" value="{{ old('email', $tenant->email) }}" required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Phone <span class="text-rose-500">* (Unique)</span></label>
                    <input type="text" name="phone" value="{{ old('phone', $tenant->phone) }}" required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">GST Number</label>
                    <input type="text" name="gst_number" value="{{ old('gst_number', $tenant->gst_number) }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Reseller Partner</label>
                    <select name="reseller_id" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                        <option value="">Direct Platform Customer</option>
                        @foreach($resellers as $reseller)
                            <option value="{{ $reseller->id }}" {{ old('reseller_id', $tenant->reseller_id) == $reseller->id ? 'selected' : '' }}>
                                {{ $reseller->company_name }} ({{ $reseller->contact_name }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Subscription Status</label>
                    <select name="is_active" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20" {{ $tenant->id === 1 ? 'disabled' : '' }}>
                        <option value="1" {{ old('is_active', $tenant->is_active) ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ !old('is_active', $tenant->is_active) ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Currency</label>
                    <input type="text" name="currency" value="{{ old('currency', $tenant->currency) }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Physical Address</label>
                    <textarea name="address" rows="2"
                              class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">{{ old('address', $tenant->address) }}</textarea>
                </div>
            </div>
        </x-card>

        {{-- Section 2: Tenant Administrator & Change Password --}}
        <x-card>
            <div class="border-b border-zinc-100 dark:border-zinc-800 pb-3 mb-4">
                <h2 class="text-lg font-bold font-cabinet text-zinc-900 dark:text-white flex items-center gap-2">
                    <span class="material-symbols-rounded text-emerald-600">lock_reset</span>
                    Tenant Administrator and Change Password
                </h2>
                <p class="text-xs text-zinc-500">View administrator account details and set a new password</p>
            </div>

            @if($adminUser)
                <input type="hidden" name="admin_user_id" value="{{ $adminUser->id }}">

                <div class="p-4 mb-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700/60 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-600/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                            <span class="material-symbols-rounded">admin_panel_settings</span>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-zinc-900 dark:text-white">{{ $adminUser->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $adminUser->email }}</div>
                        </div>
                    </div>
                    <div class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/40">
                        Role: {{ ucfirst($adminUser->pivot->role ?? 'admin') }}
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Admin Username</label>
                        <input type="text" name="admin_username" value="{{ old('admin_username', $adminUser->pivot->username ?? '') }}"
                               placeholder="e.g. demo"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                        <p class="mt-1 text-xs text-zinc-500">The username used to sign in to this business tenant.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Admin Email (Account)</label>
                        <input type="text" value="{{ $adminUser->email }}" disabled
                               class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-800 text-zinc-500 cursor-not-allowed">
                        <p class="mt-1 text-xs text-zinc-500">Email associated with this administrator.</p>
                    </div>

                    <div>
                        <label for="admin_password" class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">New Password</label>
                        <div class="relative">
                            <input id="admin_password" type="password" name="admin_password" autocomplete="new-password" placeholder="Enter new password (8+ characters)"
                                   class="min-h-11 w-full pl-3 pr-12 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                            <button type="button" data-password-toggle="admin_password" aria-controls="admin_password" aria-label="Show new password" aria-pressed="false" class="absolute inset-y-0 right-0 flex min-h-11 w-11 items-center justify-center rounded-r-xl text-zinc-600 hover:bg-zinc-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:text-zinc-300 dark:hover:bg-zinc-800">
                                <span class="material-symbols-rounded text-xl" aria-hidden="true">visibility</span>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-zinc-500">Minimum 8 characters. Leave blank to keep current password.</p>
                    </div>

                    <div>
                        <label for="admin_password_confirmation" class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Confirm New Password</label>
                        <div class="relative">
                            <input id="admin_password_confirmation" type="password" name="admin_password_confirmation" autocomplete="new-password" placeholder="Repeat the new password"
                                   class="min-h-11 w-full pl-3 pr-12 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                            <button type="button" data-password-toggle="admin_password_confirmation" aria-controls="admin_password_confirmation" aria-label="Show password confirmation" aria-pressed="false" class="absolute inset-y-0 right-0 flex min-h-11 w-11 items-center justify-center rounded-r-xl text-zinc-600 hover:bg-zinc-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:text-zinc-300 dark:hover:bg-zinc-800">
                                <span class="material-symbols-rounded text-xl" aria-hidden="true">visibility</span>
                            </button>
                        </div>
                    </div>
                </div>
            @else
                <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200 text-sm">
                    No administrator is currently linked to this business tenant.
                </div>
            @endif
        </x-card>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.tenants.index') }}" class="px-4 py-2 text-sm font-semibold text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-md transition-colors flex items-center gap-2">
                <span class="material-symbols-rounded text-lg">save</span>
                Update Business
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const hiddenLabel = button.getAttribute('aria-label');
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', visible ? hiddenLabel.replace('Show', 'Hide') : hiddenLabel);
        button.querySelector('span').textContent = visible ? 'visibility_off' : 'visibility';
    });
});
</script>
@endpush
