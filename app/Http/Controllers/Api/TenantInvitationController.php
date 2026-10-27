<?php

namespace App\Http\Controllers\Api;

use App\Models\TenantUserInvitation;
use App\Services\ActivityLogger;
use App\Services\TenantMembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantInvitationController extends BaseApiController
{
    public function __construct(private TenantMembershipService $memberships) {}

    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = TenantUserInvitation::with('tenant')
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();
        $tenant = $this->memberships->acceptInvitation($invitation, $request->user());
        ActivityLogger::log('Accepted Business Invitation', 'UserManagement', $request->user()->id, $tenant->id);

        return $this->sendResponse([
            'tenant' => ['id' => $tenant->id, 'name' => $tenant->name, 'slug' => $tenant->slug],
        ], 'Invitation accepted');
    }
}
