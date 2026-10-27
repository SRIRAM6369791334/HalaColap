@extends('layouts.app')
@section('title', 'User Management')

@section('content')
<div class="space-y-6">
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200">
            <p class="font-semibold">User access was not saved. Please correct these details:</p>
            <ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        </div>
    @endif

    <x-page-header title="User Management" subtitle="Manage business-specific usernames, roles and access">
        <x-slot:actions>
            @if($tenant)
                @canany(['manage users', 'create users'])
                    <x-button onclick="openUserModal()" variant="primary" icon="person_add">Add User</x-button>
                @endcanany
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card title="Memberships" value="{{ $users->count() }}" icon="group" color="emerald" />
        <x-stat-card title="Active Access" value="{{ $users->filter(fn($u) => (bool) ($tenant ? $u->pivot?->is_active : $u->membership_active))->count() }}" icon="monitor_heart" color="blue" />
        <x-stat-card title="Admins" value="{{ $users->filter(fn($u) => ($tenant ? $u->pivot?->role : $u->membership_role) === 'admin')->count() }}" icon="admin_panel_settings" color="amber" />
        <x-stat-card title="Pending Invites" value="{{ $invitations->count() }}" icon="mail" color="rose" />
    </div>

    <x-card padding="p-0">
        @php
            $headers = $tenant
                ? ['User', 'Email', 'Role', 'Access', ['label' => 'Actions', 'class' => 'sticky right-0 z-20 bg-zinc-50 shadow-[-8px_0_12px_-12px_rgba(0,0,0,0.45)] dark:bg-zinc-950']]
                : ['Business', 'User', 'Email', 'Role', 'Access'];
        @endphp
        <x-data-table :headers="$headers">
            @forelse($users as $user)
                @php
                    $membershipUsername = $tenant ? $user->pivot?->username : $user->membership_username;
                    $membershipRole = $tenant ? $user->pivot?->role : $user->membership_role;
                    $membershipActive = (bool) ($tenant ? $user->pivot?->is_active : $user->membership_active);
                @endphp
                <tr class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                    @if(!$tenant)
                        <td class="px-6 py-4 text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ $user->tenant_name }}</td>
                    @endif
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-emerald-100 bg-emerald-50 font-bold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                            <div class="min-w-0">
                                <div class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $user->name }}</div>
                                <div class="truncate font-jetbrains text-xs text-zinc-500 dark:text-zinc-400">{{ $membershipUsername }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">{{ $user->email }}</td>
                    <td class="px-6 py-4"><x-badge variant="{{ $membershipRole === 'admin' ? 'info' : 'secondary' }}">{{ Str::title(str_replace('_', ' ', $membershipRole ?: 'No Role')) }}</x-badge></td>
                    <td class="px-6 py-4">
                        @if($tenant)
                            @canany(['manage users', 'edit users'])
                                <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST">
                                    @csrf
                                    <button type="submit" role="switch" aria-checked="{{ $membershipActive ? 'true' : 'false' }}" aria-label="{{ $membershipActive ? 'Deactivate' : 'Activate' }} access for {{ $user->name }}" data-custom-loading class="inline-flex min-h-11 items-center gap-2 rounded-lg px-1 text-xs font-semibold text-zinc-700 dark:text-zinc-200">
                                        <span class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full {{ $membershipActive ? 'bg-emerald-600' : 'bg-zinc-300 dark:bg-zinc-700' }}" aria-hidden="true"><span class="absolute left-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform {{ $membershipActive ? 'translate-x-5' : 'translate-x-0' }}"></span></span>
                                        <span>{{ $membershipActive ? 'Active' : 'Inactive' }}</span>
                                    </button>
                                </form>
                            @endcanany
                        @else
                            <x-badge variant="{{ $membershipActive ? 'success' : 'secondary' }}">{{ $membershipActive ? 'Active' : 'Inactive' }}</x-badge>
                        @endif
                    </td>
                    @if($tenant)
                        <td class="sticky right-0 z-10 bg-white px-6 py-4 shadow-[-8px_0_12px_-12px_rgba(0,0,0,0.45)] dark:bg-zinc-900">
                            <div class="flex items-center gap-2">
                                @canany(['manage users', 'edit users'])
                                    @php
                                        $membershipPayload = base64_encode(json_encode([
                                            'id' => $user->id,
                                            'name' => $user->name,
                                            'email' => $user->email,
                                            'username' => $membershipUsername,
                                            'role' => $membershipRole,
                                            'is_active' => $membershipActive,
                                        ], JSON_THROW_ON_ERROR));
                                    @endphp
                                    <x-button onclick="editMembership(JSON.parse(atob('{{ $membershipPayload }}')))" variant="ghost" size="sm" icon="settings" class="min-h-11 min-w-11" title="Edit business access" />
                                @endcanany
                                @if(auth()->id() !== $user->id)
                                    @canany(['manage users', 'delete users'])
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Remove this user from {{ addslashes($tenant->name) }}? Their other business access will remain unchanged.');">
                                            @csrf @method('DELETE')
                                            <x-button type="submit" variant="ghost" size="sm" icon="person_remove" class="min-h-11 min-w-11 text-rose-600 hover:text-rose-700" title="Remove business access" />
                                        </form>
                                    @endcanany
                                @endif
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ count($headers) }}" class="px-6 py-12 text-center"><x-empty-state icon="person_off" title="No memberships found" subtitle="Add the first user for this business." /></td></tr>
            @endforelse
        </x-data-table>
    </x-card>

    @if($tenant && $invitations->isNotEmpty())
        <x-card>
            <div class="mb-4">
                <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Pending Invitations</h2>
                <p class="mt-1 text-sm text-zinc-500">Existing account owners must accept before access becomes active.</p>
            </div>
            <div class="space-y-3">
                @foreach($invitations as $invitation)
                    <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-zinc-900 dark:text-white">{{ $invitation->email }}</p>
                            <p class="mt-1 text-xs text-zinc-500">{{ $invitation->username }} · {{ Str::title(str_replace('_', ' ', $invitation->role)) }} · expires {{ $invitation->expires_at->diffForHumans() }}</p>
                        </div>
                        <div class="flex gap-2">
                            <form action="{{ route('admin.user-invitations.resend', $invitation) }}" method="POST">@csrf <x-button type="submit" variant="secondary" size="sm">Resend</x-button></form>
                            <form action="{{ route('admin.user-invitations.cancel', $invitation) }}" method="POST">@csrf @method('DELETE') <x-button type="submit" variant="ghost" size="sm" class="text-rose-600">Cancel</x-button></form>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif
</div>

@if($tenant)
@push('modals')
<div id="userModal" class="fixed inset-0 z-[100] hidden" aria-labelledby="modalTitle" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-zinc-950/50 backdrop-blur-sm"></div>
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
            <div class="relative w-full max-w-xl overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
                <form id="userForm" action="{{ route('admin.users.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">
                    <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-5 py-4 dark:border-zinc-800 sm:px-6">
                        <div><h3 id="modalTitle" class="text-lg font-semibold text-zinc-900 dark:text-white">Add User</h3><p id="modalSubtitle" class="mt-0.5 text-xs text-zinc-500">Create an account or invite an existing account.</p></div>
                        <button type="button" onclick="closeUserModal()" class="flex h-11 w-11 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Close user dialog"><span class="material-symbols-rounded" aria-hidden="true">close</span></button>
                    </div>
                    <div class="max-h-[calc(100dvh-220px)] space-y-4 overflow-y-auto px-5 py-5 sm:px-6">
                        <div id="identityFields" class="space-y-4">
                            <x-form.input type="email" name="email" id="userEmail" label="Email Address" icon="mail" autocomplete="email" required />
                            <div id="identityStatus" aria-live="polite" class="hidden rounded-xl border p-3 text-sm"></div>
                            <div id="newAccountFields" class="space-y-4">
                                <x-form.input name="name" id="userName" label="Full Name" icon="person" required />
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div class="relative"><x-form.input type="password" name="password" id="userPassword" label="Temporary Password" icon="lock" autocomplete="new-password" class="pr-14" required /><button type="button" id="userPasswordToggle" onclick="toggleUserPassword()" aria-label="Show password" aria-controls="userPassword" aria-pressed="false" class="absolute bottom-0.5 right-1 flex h-10 w-10 items-center justify-center rounded-lg text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"><span class="material-symbols-rounded" aria-hidden="true">visibility</span></button></div>
                                    <x-form.input type="password" name="password_confirmation" id="userPasswordConfirmation" label="Confirm Password" icon="lock_reset" autocomplete="new-password" required />
                                </div>
                                <p class="text-xs text-zinc-500">Use at least 8 characters with letters and numbers.</p>
                            </div>
                        </div>
                        <div id="membershipIdentity" class="hidden space-y-4">
                            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-800 dark:bg-zinc-950">
                                <p id="membershipName" class="font-semibold text-zinc-900 dark:text-white"></p>
                                <p id="membershipEmail" class="mt-1 text-zinc-500"></p>
                            </div>
                            <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 space-y-3 dark:border-zinc-800 dark:bg-zinc-950/50">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Reset Password (Optional)</span>
                                <x-form.input type="password" name="password" id="editPassword" label="New Password (leave blank to keep current)" icon="lock" autocomplete="new-password" />
                            </div>
                        </div>
                        <x-form.input name="username" id="userUsername" label="Business Username" icon="badge" required />
                        <x-form.select name="role" id="role" label="Business Role" required><option value="">Select a role...</option>@foreach($roles as $r)<option value="{{ $r->name }}">{{ Str::title(str_replace('_', ' ', $r->name)) }}</option>@endforeach</x-form.select>
                    </div>
                    <div class="flex items-center justify-end gap-2 border-t border-zinc-200 bg-zinc-50 px-5 py-3 dark:border-zinc-800 dark:bg-zinc-950 sm:px-6">
                        <x-button type="button" variant="secondary" onclick="closeUserModal()" class="max-sm:flex-1">Cancel</x-button>
                        <x-button id="saveUserButton" type="submit" variant="primary" icon="check" class="max-sm:flex-1">Save User</x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
let userModalTrigger = null;
let identityTimer = null;

function setUserPasswordVisible(visible) {
    const input = document.getElementById('userPassword');
    const toggle = document.getElementById('userPasswordToggle');
    input.type = visible ? 'text' : 'password';
    toggle.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
    toggle.setAttribute('aria-pressed', String(visible));
    toggle.querySelector('span').textContent = visible ? 'visibility_off' : 'visibility';
}

function toggleUserPassword() { setUserPasswordVisible(document.getElementById('userPassword').type === 'password'); }

function showUserModal() {
    setUserPasswordVisible(false);
    userModalTrigger = document.activeElement;
    document.getElementById('userModal').classList.remove('hidden');
    (document.getElementById('identityFields').classList.contains('hidden') ? document.getElementById('userUsername') : document.getElementById('userEmail')).focus();
}

function openUserModal() {
    const form = document.getElementById('userForm');
    form.reset();
    form.action = @json(route('admin.users.store'));
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('modalTitle').textContent = 'Add User';
    document.getElementById('modalSubtitle').textContent = 'Create an account or invite an existing account.';
    document.getElementById('identityFields').classList.remove('hidden');
    document.getElementById('membershipIdentity').classList.add('hidden');
    const editPw = document.getElementById('editPassword');
    if (editPw) editPw.value = '';
    setExistingIdentity(false, false);
    showUserModal();
}

function editMembership(user) {
    const form = document.getElementById('userForm');
    form.reset();
    form.action = `/admin/users/${user.id}`;
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('modalTitle').textContent = 'Edit Business Access';
    document.getElementById('modalSubtitle').textContent = 'Changes apply only to {{ addslashes($tenant->name) }}.';
    document.getElementById('identityFields').classList.add('hidden');
    document.getElementById('membershipIdentity').classList.remove('hidden');
    document.getElementById('membershipName').textContent = user.name;
    document.getElementById('membershipEmail').textContent = user.email;
    document.getElementById('userUsername').value = user.username;
    document.getElementById('role').value = user.role;
    const editPw = document.getElementById('editPassword');
    if (editPw) editPw.value = '';
    showUserModal();
}

function closeUserModal() {
    document.getElementById('userModal').classList.add('hidden');
    setUserPasswordVisible(false);
    userModalTrigger?.focus();
}

function setExistingIdentity(existing, alreadyMember) {
    const fields = document.getElementById('newAccountFields');
    const status = document.getElementById('identityStatus');
    const name = document.getElementById('userName');
    const password = document.getElementById('userPassword');
    const confirmation = document.getElementById('userPasswordConfirmation');
    fields.classList.toggle('hidden', existing);
    name.required = !existing;
    password.required = !existing;
    confirmation.required = !existing;
    status.className = `rounded-xl border p-3 text-sm ${alreadyMember ? 'border-rose-200 bg-rose-50 text-rose-700' : existing ? 'border-sky-200 bg-sky-50 text-sky-800' : 'hidden'}`;
    status.textContent = alreadyMember ? 'This account already belongs to this business.' : existing ? 'Existing account found. An invitation will be sent; the current password will not change.' : '';
    document.getElementById('saveUserButton').disabled = alreadyMember;
}

document.getElementById('userEmail').addEventListener('input', function () {
    clearTimeout(identityTimer);
    setExistingIdentity(false, false);
    if (!this.validity.valid || !this.value.trim()) return;
    identityTimer = setTimeout(async () => {
        try {
            const response = await fetch(`{{ route('admin.users.identity-status') }}?email=${encodeURIComponent(this.value.trim())}`, {headers: {'Accept': 'application/json'}});
            if (!response.ok) return;
            const state = await response.json();
            setExistingIdentity(state.existing_account, state.already_member);
        } catch (_) {}
    }, 350);
});

document.getElementById('userModal').addEventListener('keydown', function(event) {
    if (event.key === 'Escape') closeUserModal();
    if (event.key !== 'Tab') return;
    const controls = Array.from(this.querySelectorAll('button, input:not([type="hidden"]), select, a[href]')).filter(el => !el.disabled && el.getClientRects().length);
    const first = controls[0], last = controls[controls.length - 1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
});
</script>
@endpush
@endif
@endsection
