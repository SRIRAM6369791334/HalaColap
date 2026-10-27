<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\Tenancy\TenantContext;

class ExportReportCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $exportType,
        protected array $headers,
        protected array $rows,
        protected string $fileName
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Asynchronously exporting report: {$this->exportType}");

        try {
            $handle = fopen('php://temp', 'r+');
            
            // Add UTF-8 BOM for Excel compatibility
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            fputcsv($handle, $this->headers);

            foreach ($this->rows as $row) {
                fputcsv($handle, $row);
            }

            rewind($handle);
            $csvContent = stream_get_contents($handle);
            fclose($handle);

            $tenantId = app(TenantContext::class)->getTenantId();
            if ($tenantId) {
                $tenantPath = "tenants/{$tenantId}/exports/{$this->fileName}";
                Storage::disk('public')->put($tenantPath, $csvContent);
            }

            // Also keep standard exports path
            $path = "exports/{$this->fileName}";
            Storage::disk('public')->put($path, $csvContent);

            Log::info("Successfully stored CSV export to: {$path}");
        } catch (\Exception $e) {
            Log::error("Failed to generate CSV export for {$this->exportType}: " . $e->getMessage());
        }
    }
}
