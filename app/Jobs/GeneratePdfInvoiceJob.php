<?php

namespace App\Jobs;

use App\Models\WeeklyBill;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\Tenancy\TenantContext;

class GeneratePdfInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected int $billId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Generating PDF Invoice in the background for Bill ID: {$this->billId}");

        $bill = WeeklyBill::with(['dealer', 'items'])->find($this->billId);

        if (!$bill) {
            Log::warning("Bill ID {$this->billId} not found. Aborting PDF compilation.");
            return;
        }

        try {
            // Compile using the billing.invoice template or a basic data structure
            $pdf = Pdf::loadView('billing.invoice', ['bill' => $bill]);
            
            $tenantId = $bill->tenant_id ?? (app(TenantContext::class)->getTenantId() ?? 1);
            $tenantFilename = "tenants/{$tenantId}/invoices/invoice_{$bill->id}.pdf";
            
            // Save inside tenant partitioned folder
            Storage::disk('public')->put($tenantFilename, $pdf->output());

            // Also keep legacy path for backward compatibility
            $legacyFilename = "invoices/invoice_{$bill->id}.pdf";
            Storage::disk('public')->put($legacyFilename, $pdf->output());

            Log::info("Successfully compiled and saved PDF to tenant storage: {$tenantFilename}");
        } catch (\Exception $e) {
            Log::error("Failed to generate PDF for Bill ID {$this->billId}: " . $e->getMessage());
        }
    }
}
