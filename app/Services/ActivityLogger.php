<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log(string $action, string $module, ?int $recordId = null, ?int $tenantId = null): void
    {
        ActivityLog::create([
            'tenant_id' => $tenantId ?? app(\App\Services\Tenancy\TenantContext::class)->getTenantId(),
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId,
            'timestamp' => now(),
        ]);
    }

    public static function logPlatform(string $action, string $module, ?int $recordId = null): void
    {
        $tenantId = ($module === 'TenantManagement' && $recordId) ? $recordId : 1;
        ActivityLog::withoutEvents(fn () => ActivityLog::withoutTenancy()->create([
            'tenant_id' => $tenantId,
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId,
            'timestamp' => now(),
        ]));
    }
}
