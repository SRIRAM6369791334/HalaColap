<?php

namespace Database\Seeders;

use App\Models\DayLoadBatch;
use App\Models\DayLoadEntry;
use Illuminate\Database\Seeder;

class DealerInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $dealerId = 1; // sriram

        $entriesByDate = [
            '2025-06-02' => [
                ['vendor_id' => 3,  'no_of_boxes' => 3,  'box_weight' => 109.4, 'empty_weight' => 34.2,  'bird_weight' => 75.5,  'billing_rate' => 113],
            ],
            '2025-06-03' => [
                ['vendor_id' => 3,  'no_of_boxes' => 3,  'box_weight' => 109.4, 'empty_weight' => 34.2,  'bird_weight' => 75.5,  'billing_rate' => 113],
                ['vendor_id' => 10, 'no_of_boxes' => 1,  'box_weight' => 36.5,  'empty_weight' => 13.75, 'bird_weight' => 22.75, 'billing_rate' => 113],
            ],
            '2025-06-04' => [
                ['vendor_id' => 6,  'no_of_boxes' => 17, 'box_weight' => 613.2, 'empty_weight' => 506.0, 'bird_weight' => 107.2, 'billing_rate' => 115],
            ],
            '2025-06-05' => [
                ['vendor_id' => 5,  'no_of_boxes' => 4,  'box_weight' => 145.8, 'empty_weight' => 65.1,  'bird_weight' => 80.7,  'billing_rate' => 117],
            ],
            '2025-06-06' => [
                ['vendor_id' => 8,  'no_of_boxes' => 5,  'box_weight' => 181.4, 'empty_weight' => 125.6, 'bird_weight' => 55.8,  'billing_rate' => 117],
                ['vendor_id' => 9,  'no_of_boxes' => 4,  'box_weight' => 136.7, 'empty_weight' => 105.0, 'bird_weight' => 31.7,  'billing_rate' => 117],
            ],
            '2025-06-07' => [
                ['vendor_id' => 7,  'no_of_boxes' => 1,  'box_weight' => 36.3,  'empty_weight' => 12.0,  'bird_weight' => 24.3,  'billing_rate' => 117],
                ['vendor_id' => 11, 'no_of_boxes' => 8,  'box_weight' => 286.2, 'empty_weight' => 233.4, 'bird_weight' => 52.8,  'billing_rate' => 117],
            ],
            '2025-06-08' => [
                ['vendor_id' => 6,  'no_of_boxes' => 17, 'box_weight' => 613.2, 'empty_weight' => 457.1, 'bird_weight' => 156.1, 'billing_rate' => 117],
            ],
        ];

        foreach ($entriesByDate as $date => $rows) {
            $batch = DayLoadBatch::create([
                'billing_date' => $date,
                'status'       => 'Open',
            ]);

            foreach ($rows as $data) {
                DayLoadEntry::create(array_merge($data, [
                    'batch_id'     => $batch->id,
                    'dealer_id'    => $dealerId,
                    'status'       => 'Active',
                    'version'      => 1,
                ]));
            }

            // Refresh batch totals
            $totals = $batch->entries()->where('status', '!=', 'Cancelled')
                ->selectRaw('
                    COALESCE(SUM(no_of_boxes), 0) as total_boxes,
                    COALESCE(SUM(box_weight), 0) as total_box_weight,
                    COALESCE(SUM(empty_weight), 0) as total_empty_weight,
                    COALESCE(SUM(bird_weight), 0) as total_bird_weight,
                    COALESCE(SUM(COALESCE(farm_weight, 0)), 0) as total_farm_weight,
                    COALESCE(SUM(COALESCE(total_weight, bird_weight - COALESCE(farm_weight, 0))), 0) as total_weight,
                    COALESCE(SUM(COALESCE(loss_weight, 0)), 0) as total_loss_weight
                ')->first();

            $batch->update($totals->toArray());
        }
    }
}
