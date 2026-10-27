<?php

namespace App\Observers;

use App\Models\Item;
use Illuminate\Support\Facades\Cache;

class ItemObserver
{
    private function clearCache(?Item $item = null): void
    {
        $tenantId = $item?->tenant_id ?? (app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? 'global');
        Cache::forget("tenant_{$tenantId}.masters.items.all");
        Cache::forget("tenant_{$tenantId}.masters.items.paginated.default");
        Cache::forget('masters.items.all');
        Cache::forget('masters.items.paginated.default');
    }

    public function created(Item $item): void
    {
        $this->clearCache($item);
    }

    public function saved(Item $item): void
    {
        $this->clearCache($item);
    }

    public function updated(Item $item): void
    {
        $this->clearCache($item);
    }

    public function deleted(Item $item): void
    {
        $this->clearCache($item);
    }

    public function restored(Item $item): void
    {
        $this->clearCache($item);
    }

    public function forceDeleted(Item $item): void
    {
        $this->clearCache($item);
    }
}
