<?php

namespace Database\Seeders;

use App\Models\DayLoadBatch;
use App\Models\DayLoadEntry;
use App\Models\Vendor;
use App\Models\Dealer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DayLoadSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = Vendor::orderBy('id')->get();
        $dealers = Dealer::orderBy('id')->get();

        $data = [
            '2025-06-02' => [
                'farm_total' => 1787.3,
                'entries' => [
                    ['vendor' => 1,  'boxes' => 1,  'box_weight' => 37.2,  'empty_weight' => 11.4],
                    ['vendor' => 2,  'boxes' => 2,  'box_weight' => 74.6,  'empty_weight' => 22.8],
                    ['vendor' => 3,  'boxes' => 3,  'box_weight' => 109.7, 'empty_weight' => 34.2],
                    ['vendor' => 4,  'boxes' => 2,  'box_weight' => 75.5,  'empty_weight' => 22.8],
                    ['vendor' => 5,  'boxes' => 1,  'box_weight' => 36.9,  'empty_weight' => 11.4],
                    ['vendor' => 6,  'boxes' => 6,  'box_weight' => 222.4, 'empty_weight' => 67.5],
                    ['vendor' => 7,  'boxes' => 23, 'box_weight' => 856.3, 'empty_weight' => 261.9],
                    ['vendor' => 8,  'boxes' => 2,  'box_weight' => 73.3,  'empty_weight' => 22.8],
                    ['vendor' => 9,  'boxes' => 6,  'box_weight' => 223.0, 'empty_weight' => 68.7],
                    ['vendor' => 10, 'boxes' => 2,  'box_weight' => 72.4,  'empty_weight' => 21.8],
                    ['vendor' => 11, 'boxes' => 6,  'box_weight' => 175.2, 'empty_weight' => 56.8],
                    ['vendor' => 12, 'boxes' => 1,  'box_weight' => 37.3,  'empty_weight' => 11.3],
                    ['vendor' => 13, 'boxes' => 8,  'box_weight' => 291.1, 'empty_weight' => 89.5],
                    ['vendor' => 14, 'boxes' => 8,  'box_weight' => 287.4, 'empty_weight' => 91.4],
                ],
            ],
            '2025-06-03' => [
                'farm_total' => 2535.7,
                'entries' => [
                    ['vendor' => 1,  'boxes' => 8,  'box_weight' => 286.8, 'empty_weight' => 89.7],
                    ['vendor' => 2,  'boxes' => 15, 'box_weight' => 532.2, 'empty_weight' => 166.8],
                    ['vendor' => 3,  'boxes' => 10, 'box_weight' => 356.7, 'empty_weight' => 113.3],
                    ['vendor' => 4,  'boxes' => 7,  'box_weight' => 242.6, 'empty_weight' => 77.3],
                    ['vendor' => 5,  'boxes' => 2,  'box_weight' => 70.2,  'empty_weight' => 22.6],
                    ['vendor' => 6,  'boxes' => 17, 'box_weight' => 579.2, 'empty_weight' => 194.1],
                    ['vendor' => 7,  'boxes' => 9,  'box_weight' => 310.2, 'empty_weight' => 100.0],
                    ['vendor' => 8,  'boxes' => 4,  'box_weight' => 139.5, 'empty_weight' => 45.0],
                    ['vendor' => 9,  'boxes' => 9,  'box_weight' => 307.2, 'empty_weight' => 101.8],
                    ['vendor' => 10, 'boxes' => 3,  'box_weight' => 103.4, 'empty_weight' => 34.2],
                    ['vendor' => 11, 'boxes' => 14, 'box_weight' => 485.9, 'empty_weight' => 158.9],
                    ['vendor' => 12, 'boxes' => 8,  'box_weight' => 268.9, 'empty_weight' => 91.0],
                    ['vendor' => 13, 'boxes' => 1,  'box_weight' => 34.1,  'empty_weight' => 11.0],
                    ['vendor' => 14, 'boxes' => 1,  'box_weight' => 36.1,  'empty_weight' => 11.3],
                ],
            ],
            '2025-06-04' => [
                'farm_total' => 1683.5,
                'entries' => [
                    ['vendor' => 1,  'boxes' => 2,  'box_weight' => 77.0,  'empty_weight' => 22.8],
                    ['vendor' => 2,  'boxes' => 4,  'box_weight' => 152.2, 'empty_weight' => 45.0],
                    ['vendor' => 3,  'boxes' => 2,  'box_weight' => 77.4,  'empty_weight' => 22.8],
                    ['vendor' => 4,  'boxes' => 2,  'box_weight' => 75.3,  'empty_weight' => 22.8],
                    ['vendor' => 5,  'boxes' => 1,  'box_weight' => 37.1,  'empty_weight' => 11.3],
                    ['vendor' => 6,  'boxes' => 2,  'box_weight' => 75.2,  'empty_weight' => 22.8],
                    ['vendor' => 7,  'boxes' => 6,  'box_weight' => 226.5, 'empty_weight' => 68.4],
                    ['vendor' => 8,  'boxes' => 15, 'box_weight' => 568.8, 'empty_weight' => 171.0],
                    ['vendor' => 9,  'boxes' => 2,  'box_weight' => 75.8,  'empty_weight' => 22.8],
                    ['vendor' => 10, 'boxes' => 3,  'box_weight' => 112.2, 'empty_weight' => 34.0],
                    ['vendor' => 11, 'boxes' => 2,  'box_weight' => 75.6,  'empty_weight' => 22.7],
                    ['vendor' => 12, 'boxes' => 2,  'box_weight' => 75.6,  'empty_weight' => 22.6],
                    ['vendor' => 13, 'boxes' => 4,  'box_weight' => 135.4, 'empty_weight' => 45.2],
                    ['vendor' => 14, 'boxes' => 1,  'box_weight' => 38.8,  'empty_weight' => 11.3],
                    ['vendor' => 15, 'boxes' => 8,  'box_weight' => 296.9, 'empty_weight' => 91.6],
                    ['vendor' => 16, 'boxes' => 7,  'box_weight' => 260.9, 'empty_weight' => 79.0],
                    ['vendor' => 17, 'boxes' => 1,  'box_weight' => 37.7,  'empty_weight' => 11.5],
                ],
            ],
            '2025-06-05' => [
                'farm_total' => 2520.1,
                'entries' => [
                    ['vendor' => 1,  'boxes' => 3,  'box_weight' => 114.8, 'empty_weight' => 34.1],
                    ['vendor' => 2,  'boxes' => 5,  'box_weight' => 193.2, 'empty_weight' => 57.3],
                    ['vendor' => 3,  'boxes' => 1,  'box_weight' => 38.1,  'empty_weight' => 11.2],
                    ['vendor' => 4,  'boxes' => 1,  'box_weight' => 37.2,  'empty_weight' => 11.4],
                    ['vendor' => 5,  'boxes' => 1,  'box_weight' => 38.5,  'empty_weight' => 11.4],
                    ['vendor' => 6,  'boxes' => 1,  'box_weight' => 36.5,  'empty_weight' => 11.4],
                    ['vendor' => 7,  'boxes' => 5,  'box_weight' => 194.5, 'empty_weight' => 57.0],
                    ['vendor' => 8,  'boxes' => 35, 'box_weight' => 1342.9,'empty_weight' => 399.0],
                    ['vendor' => 9,  'boxes' => 5,  'box_weight' => 193.4, 'empty_weight' => 57.0],
                    ['vendor' => 10, 'boxes' => 13, 'box_weight' => 504.6, 'empty_weight' => 148.7],
                    ['vendor' => 11, 'boxes' => 2,  'box_weight' => 76.4,  'empty_weight' => 22.8],
                    ['vendor' => 12, 'boxes' => 4,  'box_weight' => 146.9, 'empty_weight' => 45.2],
                    ['vendor' => 13, 'boxes' => 1,  'box_weight' => 38.9,  'empty_weight' => 11.5],
                    ['vendor' => 14, 'boxes' => 6,  'box_weight' => 222.0, 'empty_weight' => 68.3],
                    ['vendor' => 15, 'boxes' => 9,  'box_weight' => 342.8, 'empty_weight' => 102.2],
                ],
            ],
            '2025-06-06' => [
                'farm_total' => 2545.8,
                'entries' => [
                    ['vendor' => 1,  'boxes' => 1,  'box_weight' => 37.3,  'empty_weight' => 11.0],
                    ['vendor' => 2,  'boxes' => 1,  'box_weight' => 39.4,  'empty_weight' => 11.4],
                    ['vendor' => 3,  'boxes' => 1,  'box_weight' => 37.9,  'empty_weight' => 11.3],
                    ['vendor' => 4,  'boxes' => 2,  'box_weight' => 79.1,  'empty_weight' => 23.3],
                    ['vendor' => 5,  'boxes' => 1,  'box_weight' => 37.8,  'empty_weight' => 11.2],
                    ['vendor' => 6,  'boxes' => 1,  'box_weight' => 38.5,  'empty_weight' => 11.4],
                    ['vendor' => 7,  'boxes' => 4,  'box_weight' => 154.2, 'empty_weight' => 44.1],
                    ['vendor' => 8,  'boxes' => 6,  'box_weight' => 227.4, 'empty_weight' => 68.1],
                    ['vendor' => 9,  'boxes' => 41, 'box_weight' => 1552.5,'empty_weight' => 467.4],
                    ['vendor' => 10, 'boxes' => 6,  'box_weight' => 226.5, 'empty_weight' => 68.4],
                    ['vendor' => 11, 'boxes' => 14, 'box_weight' => 525.2, 'empty_weight' => 159.0],
                    ['vendor' => 12, 'boxes' => 2,  'box_weight' => 76.9,  'empty_weight' => 22.7],
                    ['vendor' => 13, 'boxes' => 5,  'box_weight' => 179.9, 'empty_weight' => 57.0],
                    ['vendor' => 14, 'boxes' => 10, 'box_weight' => 362.0, 'empty_weight' => 115.6],
                    ['vendor' => 15, 'boxes' => 1,  'box_weight' => 35.8,  'empty_weight' => 11.3],
                ],
            ],
            '2025-06-07' => [
                'farm_total' => 2299.8,
                'entries' => [
                    ['vendor' => 1,  'boxes' => 1,  'box_weight' => 36.9,  'empty_weight' => 10.3],
                    ['vendor' => 2,  'boxes' => 2,  'box_weight' => 74.9,  'empty_weight' => 22.8],
                    ['vendor' => 3,  'boxes' => 1,  'box_weight' => 35.7,  'empty_weight' => 11.4],
                    ['vendor' => 4,  'boxes' => 5,  'box_weight' => 186.3, 'empty_weight' => 57.0],
                    ['vendor' => 5,  'boxes' => 1,  'box_weight' => 36.0,  'empty_weight' => 11.4],
                    ['vendor' => 6,  'boxes' => 6,  'box_weight' => 222.1, 'empty_weight' => 68.4],
                    ['vendor' => 7,  'boxes' => 12, 'box_weight' => 438.9, 'empty_weight' => 136.0],
                    ['vendor' => 8,  'boxes' => 25, 'box_weight' => 916.0, 'empty_weight' => 293.9],
                    ['vendor' => 9,  'boxes' => 8,  'box_weight' => 297.9, 'empty_weight' => 91.2],
                    ['vendor' => 10, 'boxes' => 15, 'box_weight' => 552.9, 'empty_weight' => 171.6],
                    ['vendor' => 11, 'boxes' => 2,  'box_weight' => 75.3,  'empty_weight' => 22.8],
                    ['vendor' => 12, 'boxes' => 1,  'box_weight' => 35.8,  'empty_weight' => 11.4],
                    ['vendor' => 13, 'boxes' => 6,  'box_weight' => 206.0, 'empty_weight' => 69.9],
                    ['vendor' => 14, 'boxes' => 5,  'box_weight' => 181.9, 'empty_weight' => 55.9],
                ],
            ],
            '2025-06-08' => [
                'farm_total' => 2535.7,
                'entries' => [
                    ['vendor' => 1,  'boxes' => 8,  'box_weight' => 286.8, 'empty_weight' => 89.7],
                    ['vendor' => 2,  'boxes' => 15, 'box_weight' => 532.2, 'empty_weight' => 166.8],
                    ['vendor' => 3,  'boxes' => 10, 'box_weight' => 356.7, 'empty_weight' => 113.3],
                    ['vendor' => 4,  'boxes' => 7,  'box_weight' => 242.6, 'empty_weight' => 77.3],
                    ['vendor' => 5,  'boxes' => 2,  'box_weight' => 70.2,  'empty_weight' => 22.6],
                    ['vendor' => 6,  'boxes' => 17, 'box_weight' => 579.2, 'empty_weight' => 194.1],
                    ['vendor' => 7,  'boxes' => 9,  'box_weight' => 310.2, 'empty_weight' => 100.0],
                    ['vendor' => 8,  'boxes' => 4,  'box_weight' => 139.5, 'empty_weight' => 45.0],
                    ['vendor' => 9,  'boxes' => 9,  'box_weight' => 307.2, 'empty_weight' => 101.8],
                    ['vendor' => 10, 'boxes' => 3,  'box_weight' => 103.4, 'empty_weight' => 34.2],
                    ['vendor' => 11, 'boxes' => 14, 'box_weight' => 485.9, 'empty_weight' => 158.9],
                    ['vendor' => 12, 'boxes' => 8,  'box_weight' => 268.9, 'empty_weight' => 91.0],
                    ['vendor' => 13, 'boxes' => 1,  'box_weight' => 34.1,  'empty_weight' => 11.0],
                    ['vendor' => 14, 'boxes' => 1,  'box_weight' => 36.1,  'empty_weight' => 11.3],
                ],
            ],
        ];

        $entryCount = 0;

        foreach ($data as $date => $dayData) {
            $batch = DayLoadBatch::create([
                'billing_date' => $date,
                'status' => 'Open',
            ]);

            $entries = $dayData['entries'];
            $farmTotal = $dayData['farm_total'];

            $createdEntries = [];
            foreach ($entries as $row) {
                $vendor = $vendors[$row['vendor'] - 1];
                $dealer = $dealers[($row['vendor'] - 1) % $dealers->count()];

                $birdWeight = round($row['box_weight'] - $row['empty_weight'], 2);

                $createdEntries[] = DayLoadEntry::create([
                    'batch_id' => $batch->id,
                    'vendor_id' => $vendor->id,
                    'dealer_id' => $dealer->id,
                    'paper_rate' => 0,
                    'billing_rate' => 0,
                    'customer_rate' => 0,
                    'no_of_boxes' => $row['boxes'],
                    'box_weight' => $row['box_weight'],
                    'empty_weight' => $row['empty_weight'],
                    'bird_weight' => $birdWeight,
                    'farm_weight' => 0,
                    'loss_weight' => $birdWeight,
                    'status' => 'Active',
                ]);

                $entryCount++;
            }

            $totalBirdWeight = array_sum(array_map(fn($e) => $e->bird_weight, $createdEntries));

            foreach ($createdEntries as $entry) {
                $farmWeight = $totalBirdWeight > 0
                    ? round($farmTotal * ($entry->bird_weight / $totalBirdWeight), 1)
                    : 0;
                $lossWeight = round($farmWeight - $entry->bird_weight, 1);
                $totalWeight = $lossWeight;

                $entry->update([
                    'farm_weight' => $farmWeight,
                    'loss_weight' => $lossWeight,
                    'total_weight' => $totalWeight,
                ]);
            }

            $this->updateBatchTotals($batch);
        }

        $this->command->info("Seeded 7 batches with {$entryCount} entries (farm weights distributed)");
    }

    private function updateBatchTotals(DayLoadBatch $batch): void
    {
        $totals = DB::table('day_load_entries')
            ->where('batch_id', $batch->id)
            ->selectRaw('
                SUM(no_of_boxes) as total_boxes,
                SUM(box_weight) as total_box_weight,
                SUM(empty_weight) as total_empty_weight,
                SUM(bird_weight) as total_bird_weight,
                COALESCE(SUM(farm_weight), 0) as total_farm_weight,
                COALESCE(SUM(loss_weight), 0) as total_loss_weight
            ')
            ->first();

        $batch->update([
            'total_boxes' => $totals->total_boxes ?? 0,
            'total_box_weight' => $totals->total_box_weight ?? 0,
            'total_empty_weight' => $totals->total_empty_weight ?? 0,
            'total_bird_weight' => $totals->total_bird_weight ?? 0,
            'total_farm_weight' => $totals->total_farm_weight ?? 0,
            'total_loss_weight' => $totals->total_loss_weight ?? 0,
        ]);
    }
}
