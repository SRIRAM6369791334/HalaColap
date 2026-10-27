<?php

namespace Database\Factories;

use App\Models\DayLoadBatch;
use App\Models\DayLoadEntry;
use App\Models\Dealer;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class DayLoadEntryFactory extends Factory
{
    protected $model = DayLoadEntry::class;

    public function definition(): array
    {
        $noOfBoxes = $this->faker->numberBetween(1, 10);
        $boxWeight = $this->faker->randomFloat(2, 15, 25);
        $emptyWeight = $this->faker->randomFloat(2, 1, 3);
        $birdWeight = round($boxWeight - $emptyWeight, 2);
        $customerRate = $this->faker->randomFloat(2, 100, 200);

        return [
            'batch_id' => DayLoadBatch::factory(),
            'vendor_id' => Vendor::factory(),
            'dealer_id' => Dealer::factory(),
            'paper_rate' => $this->faker->randomFloat(2, 80, 150),
            'billing_rate' => $this->faker->randomFloat(2, 100, 180),
            'customer_rate' => $customerRate,
            'amount' => round($birdWeight * $customerRate, 2),
            'no_of_boxes' => $noOfBoxes,
            'box_weight' => $boxWeight,
            'empty_weight' => $emptyWeight,
            'bird_weight' => $birdWeight,
            'farm_weight' => $this->faker->randomFloat(2, 10, 20),
            'status' => 'Active',
            'version' => 1,
        ];
    }
}
