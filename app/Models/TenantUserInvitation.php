<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantUserInvitation extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'email',
        'username',
        'role',
        'token_hash',
        'invited_by',
        'status',
        'expires_at',
        'accepted_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending'
            && $this->cancelled_at === null
            && $this->accepted_at === null
            && $this->expires_at->isFuture();
    }

    public static function expireStale(?int $tenantId = null, ?int $userId = null): int
    {
        return static::query()
            ->where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->update(['status' => 'expired', 'updated_at' => now()]);
    }
}
