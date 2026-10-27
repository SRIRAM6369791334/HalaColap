<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ShareInvoiceWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $billId,
        protected string $phoneNumber,
        protected string $message
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Asynchronously preparing WhatsApp payload for Bill ID: {$this->billId}");
        
        // Simulating heavy payload/API generation or logging
        Log::info("WhatsApp message prepared for {$this->phoneNumber}: '{$this->message}'");
    }
}
