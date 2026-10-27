<?php

namespace Database\Factories;

use App\Models\DayLoadBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

class DayLoadBatchFactory extends Factory
{
    protected $model = DayLoadBatch::class;

    public function definition(): array
    {
        return [
            'billing_date' => $this->faker->unique()->date(),
            'status' => 'Open',
            'total_boxes' => 0,
            'total_box_weight' => 0,
            'total_empty_weight' => 0,
            'total_bird_weight' => 0,
            'total_farm_weight' => 0,
            'total_weight' => 0,
            'total_loss_weight' => 0,
        ];
    }
}
