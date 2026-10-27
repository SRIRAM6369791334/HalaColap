<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word() . ' Feed',
            'code' => 'ITM-' . $this->faker->unique()->numerify('####'),
            'type' => $this->faker->randomElement(['Feed', 'Chick', 'Medicine', 'Vaccine', 'Equipment', 'Other']),
            'category' => $this->faker->randomElement(['Starter', 'Grower', 'Finisher', 'Broiler', 'Layer']),
            'brand' => $this->faker->company(),
            'base_unit' => $this->faker->randomElement(['kg', 'nos', 'bag', 'ml']),
            'conversion_rate' => $this->faker->randomFloat(2, 1, 100),
            'is_active' => true,
        ];
    }
}
