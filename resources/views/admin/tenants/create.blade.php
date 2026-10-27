@extends('layouts.app')
@section('title', 'Onboard New Business')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold font-cabinet text-zinc-900 dark:text-white">Onboard New Business</h1>
            <p class="text-sm text-zinc-500">Create an independent tenant account and provision its Tenant Administrator</p>
        </div>
        <a href="{{ route('admin.tenants.index') }}" class="px-3 py-2 text-sm font-semibold rounded-xl border border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
            Back to Tenants
        </a>
    </div>

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

    <form action="{{ route('admin.tenants.store') }}" method="POST" class="space-y-6">
        @csrf

        {{-- Section 1: Business Details --}}
        <x-card>
            <div class="border-b border-zinc-100 dark:border-zinc-800 pb-3 mb-4">
                <h2 class="text-lg font-bold font-cabinet text-zinc-900 dark:text-white flex items-center gap-2">
                    <span class="material-symbols-rounded text-emerald-600">store</span>
                    Business Information
                </h2>
                <p class="text-xs text-zinc-500">Details of the subscriber or customer business</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Business Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. SRM Farms & Broilers"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Company Legal Name</label>
                    <input type="text" name="company_name" value="{{ old('company_name') }}" placeholder="e.g. SRM Agro Foods Pvt Ltd"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Slug / Identifier</label>
                    <input type="text" name="slug" value="{{ old('slug') }}" placeholder="e.g. srm-farms (auto-generated if empty)"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Official Business Email <span class="text-rose-500">* (Unique)</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="contact@srmfarms.com"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                    <p class="mt-1 text-xs text-zinc-500">Each business must have a unique official email.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Phone Number <span class="text-rose-500">* (Unique)</span></label>
                    <input type="text" name="phone" value="{{ old('phone') }}" required placeholder="+91 98765 43210"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                    <p class="mt-1 text-xs text-zinc-500">Each business must have a unique phone number.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">GST Number</label>
                    <input type="text" name="gst_number" value="{{ old('gst_number') }}" placeholder="33AAAAA0000A1Z5"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Reseller Partner (Optional)</label>
                    <select name="reseller_id" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                        <option value="">Direct Platform Customer</option>
                        @foreach($resellers as $reseller)
                            <option value="{{ $reseller->id }}" {{ old('reseller_id') == $reseller->id ? 'selected' : '' }}>
                                {{ $reseller->company_name }} ({{ $reseller->contact_name }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Currency</label>
                    <input type="text" name="currency" value="{{ old('currency', 'INR') }}" placeholder="INR"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Physical Address</label>
                    <textarea name="address" rows="2" placeholder="Full farm or warehouse address..."
                              class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">{{ old('address') }}</textarea>
                </div>
            </div>
        </x-card>

        {{-- Section 2: Initial Tenant Admin Credentials --}}
        <x-card>
            <div class="border-b border-zinc-100 dark:border-zinc-800 pb-3 mb-4">
                <h2 class="text-lg font-bold font-cabinet text-zinc-900 dark:text-white flex items-center gap-2">
                    <span class="material-symbols-rounded text-emerald-600">manage_accounts</span>
                    Initial Tenant Admin Account
                </h2>
                <p class="text-xs text-zinc-500">This user will have full administrative access within their own business, can create staff and set roles/permissions, but cannot access other businesses.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Admin Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="admin_name" value="{{ old('admin_name') }}" required placeholder="e.g. Ravi Kumar"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Admin Username <span class="text-rose-500">*</span></label>
                    <input type="text" name="admin_username" value="{{ old('admin_username') }}" required placeholder="e.g. ravi_admin"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Admin Email Address <span class="text-rose-500">* (Unique Account)</span></label>
                    <input type="email" name="admin_email" value="{{ old('admin_email') }}" required placeholder="e.g. admin@srmfarms.com"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                    <p class="mt-1 text-xs text-zinc-500">Must be a unique email dedicated to this tenant administrator.</p>
                </div>

                <div>
                    <label for="admin_password" class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Administrator Password <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input id="admin_password" type="password" name="admin_password" required autocomplete="new-password" placeholder="8+ letters and numbers"
                               class="min-h-11 w-full pl-3 pr-12 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                        <button type="button" data-password-toggle="admin_password" aria-controls="admin_password" aria-label="Show temporary password" aria-pressed="false" class="absolute inset-y-0 right-0 flex min-h-11 w-11 items-center justify-center rounded-r-xl text-zinc-600 hover:bg-zinc-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:text-zinc-300 dark:hover:bg-zinc-800">
                            <span class="material-symbols-rounded text-xl" aria-hidden="true">visibility</span>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-zinc-500">Set the administrator password (minimum 8 characters).</p>
                </div>

                <div>
                    <label for="admin_password_confirmation" class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Confirm Temporary Password</label>
                    <div class="relative">
                        <input id="admin_password_confirmation" type="password" name="admin_password_confirmation" autocomplete="new-password" placeholder="Repeat the password"
                               class="min-h-11 w-full pl-3 pr-12 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                        <button type="button" data-password-toggle="admin_password_confirmation" aria-controls="admin_password_confirmation" aria-label="Show password confirmation" aria-pressed="false" class="absolute inset-y-0 right-0 flex min-h-11 w-11 items-center justify-center rounded-r-xl text-zinc-600 hover:bg-zinc-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:text-zinc-300 dark:hover:bg-zinc-800">
                            <span class="material-symbols-rounded text-xl" aria-hidden="true">visibility</span>
                        </button>
                    </div>
                </div>
            </div>
        </x-card>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.tenants.index') }}" class="px-4 py-2 text-sm font-semibold text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-md transition-colors flex items-center gap-2">
                <span class="material-symbols-rounded text-lg">check</span>
                Provision & Save Business
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
