<?php

namespace App\Http\Controllers;

use App\Models\TenantUserInvitation;
use App\Services\ActivityLogger;
use App\Services\TenantMembershipService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TenantInvitationController extends Controller
{
    public function __construct(private TenantMembershipService $memberships) {}

    public function show(string $token): View
    {
        $invitation = $this->findInvitation($token);
        if (! hash_equals(mb_strtolower($invitation->email), mb_strtolower((string) Auth::user()?->email))) {
            throw ValidationException::withMessages(['invitation' => 'This invitation belongs to a different account.']);
        }

        return view('auth.tenant-invitation', compact('invitation', 'token'));
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->findInvitation($token);
        $tenant = $this->memberships->acceptInvitation($invitation, $request->user());
        session(['active_tenant_id' => $tenant->id, 'platform_context' => false]);
        app(TenantContext::class)->setTenant($tenant);
        ActivityLogger::log('Accepted Business Invitation', 'UserManagement', $request->user()->id, $tenant->id);

        return redirect()->route('dashboard')->with('success', 'You now have access to '.$tenant->name.'.');
    }

    public function resend(TenantUserInvitation $invitation): RedirectResponse
    {
        $tenant = app(TenantContext::class)->getTenant();
        abort_unless($tenant && (int) $invitation->tenant_id === (int) $tenant->id, 403);

        $this->memberships->inviteExistingUser($tenant, $invitation->user, $invitation->username, $invitation->role, Auth::user());
        ActivityLogger::log('Resent Business Invitation', 'UserManagement', $invitation->user_id, $tenant->id);

        return back()->with('success', 'Invitation resent with a new 48-hour link.');
    }

    public function cancel(TenantUserInvitation $invitation): RedirectResponse
    {
        $tenant = app(TenantContext::class)->getTenant();
        abort_unless($tenant && (int) $invitation->tenant_id === (int) $tenant->id, 403);
        $invitation->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        ActivityLogger::log('Cancelled Business Invitation', 'UserManagement', $invitation->user_id, $tenant->id);

        return back()->with('success', 'Invitation cancelled.');
    }

    private function findInvitation(string $token): TenantUserInvitation
    {
        $invitation = TenantUserInvitation::with(['tenant', 'user'])
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (! $invitation) {
            throw ValidationException::withMessages(['invitation' => 'This invitation link is invalid.']);
        }

        return $invitation;
    }
}
