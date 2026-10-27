<?php

namespace App\Console\Commands;

use App\Models\DayLoadEntry;
use Illuminate\Console\Command;

class FixDayLoadWeights extends Command
{
    protected $signature = 'app:fix-day-load-weights';
    protected $description = 'Fix loss_weight and total_weight for existing day_load_entries based on old calculation logic';

    public function handle(): int
    {
        $entries = DayLoadEntry::whereNotNull('loss_weight')->get();

        $this->info("Found {$entries->count()} entries to fix.");

        foreach ($entries as $entry) {
            $oldLossWeight = (float) $entry->loss_weight;
            $birdWeight = (float) $entry->bird_weight;

            // Old logic: loss_weight = bird_weight - farm_weight
            // So: farm_weight = bird_weight - old_loss_weight
            $farmWeight = $birdWeight - $oldLossWeight;

            if ($farmWeight == 0) {
                $farmWeight = null;
            }

            // New logic: loss_weight = farm_weight - bird_weight, total_weight = bird_weight
            $newLossWeight = $farmWeight === null ? null : round($farmWeight - $birdWeight, 2);
            $newTotalWeight = $farmWeight === null ? null : $birdWeight;

            $entry->updateQuietly([
                'farm_weight'  => $farmWeight,
                'loss_weight'  => $newLossWeight,
                'total_weight' => $newTotalWeight,
            ]);

            $this->line("Entry #{$entry->id}: farm={$farmWeight}, loss={$newLossWeight}, total={$newTotalWeight}");
        }

        // Refresh batch totals
        $batchIds = $entries->pluck('batch_id')->unique();
        foreach ($batchIds as $batchId) {
            $batch = \App\Models\DayLoadBatch::find($batchId);
            if ($batch) {
                app(\App\Services\DayLoadBillingService::class, [
                    'invoiceService' => app(\App\Services\InvoiceNumberService::class),
                ])->refreshBatchTotals($batch);
                $this->line("Refreshed batch #{$batch->id} totals.");
            }
        }

        $this->info("Done.");
        return 0;
    }
}
