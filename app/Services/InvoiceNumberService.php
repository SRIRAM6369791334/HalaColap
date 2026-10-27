<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class InvoiceNumberService
{
    /**
     * Generate a unique invoice number safely using DB transactions and SELECT FOR UPDATE.
     * Retries up to 3 times in case of deadlocks/duplicates.
     *
     * @param string $prefix
     * @param string $table
     * @return string
     */
    public function generateUnique(string $prefix, string $table, ?int $tenantId = null): string
    {
        $tenantId = $tenantId ?? app(\App\Services\Tenancy\TenantContext::class)->getTenantId();
        $dateStr = now()->format('Ymd');
        $fullPrefix = $prefix . '-' . $dateStr . '-';

        return DB::transaction(function () use ($fullPrefix, $table, $tenantId) {
            $query = DB::table($table)
                ->where('invoice_no', 'like', $fullPrefix . '%');

            if ($tenantId && \Illuminate\Support\Facades\Schema::hasColumn($table, 'tenant_id')) {
                $query->where('tenant_id', $tenantId);
            }

            // Get the latest invoice number for today within active tenant using lockForUpdate
            $latest = $query->lockForUpdate()
                ->orderBy('invoice_no', 'desc')
                ->first();

            if (!$latest) {
                return $fullPrefix . '0001';
            }

            // Extract the sequence number and increment
            $lastSequence = (int) substr($latest->invoice_no, -4);
            $newSequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);

            return $fullPrefix . $newSequence;
        }, 3); // 3 retries
    }
}
