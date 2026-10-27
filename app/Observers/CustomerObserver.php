<?php

namespace App\Observers;

use App\Models\Customer;
use Illuminate\Support\Facades\Cache;

class CustomerObserver
{
    private function clearCache(?Customer $customer = null): void
    {
        $tenantId = $customer?->tenant_id ?? (app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? 'global');
        Cache::forget("tenant_{$tenantId}.masters.customers.all");
        Cache::forget("tenant_{$tenantId}.masters.customers.paginated.default");
        Cache::forget('masters.customers.all');
        Cache::forget('masters.customers.paginated.default');
    }

    public function created(Customer $customer): void
    {
        $this->clearCache($customer);
    }

    public function saved(Customer $customer): void
    {
        $this->clearCache($customer);
    }

    public function updated(Customer $customer): void
    {
        $this->clearCache($customer);
    }

    public function deleted(Customer $customer): void
    {
        $this->clearCache($customer);
    }

    public function restored(Customer $customer): void
    {
        $this->clearCache($customer);
    }

    public function forceDeleted(Customer $customer): void
    {
        $this->clearCache($customer);
    }
}
