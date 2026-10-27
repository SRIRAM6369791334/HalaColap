<?php

namespace App\Jobs;

use App\Models\WeeklyBill;
use App\Models\WeeklyBillItem;
use App\Models\Tenant;
use App\Services\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkImportBillsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected array $billsData,
        protected ?int $tenantId = null
    ) {
        $this->tenantId = $tenantId ?? app(TenantContext::class)->getTenantId();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Asynchronously processing bulk bills import dataset: " . count($this->billsData) . " records for tenant " . ($this->tenantId ?? 'unknown') . ".");

        if ($this->tenantId) {
            $tenant = Tenant::find($this->tenantId);
            if ($tenant) {
                app(TenantContext::class)->setTenant($tenant);
            }
        }

        try {
            DB::transaction(function () {
                foreach ($this->billsData as $billRow) {
                    $billData = [
                        'customer_id'  => $billRow['customer_id'],
                        'period_start' => $billRow['period_start'],
                        'period_end'   => $billRow['period_end'],
                        'net_amount'   => $billRow['net_amount'],
                        'gst_amount'   => $billRow['gst_amount'],
                        'total_amount' => $billRow['total_amount'],
                    ];

                    if ($this->tenantId) {
                        $billData['tenant_id'] = $this->tenantId;
                    }

                    $bill = WeeklyBill::create($billData);

                    if (isset($billRow['items'])) {
                        foreach ($billRow['items'] as $itemRow) {
                            $itemData = [
                                'weekly_bill_id' => $bill->id,
                                'item_name'      => $itemRow['item_name'],
                                'quantity'       => $itemRow['quantity'],
                                'rate'           => $itemRow['rate'],
                                'total_amount'   => $itemRow['total_amount'],
                            ];

                            if ($this->tenantId) {
                                $itemData['tenant_id'] = $this->tenantId;
                            }

                            WeeklyBillItem::create($itemData);
                        }
                    }
                }
            });
            Log::info("Successfully imported bulk bill records in background.");
        } catch (\Exception $e) {
            Log::error("Failed to import bulk bill records in background: " . $e->getMessage());
        }
    }
}
