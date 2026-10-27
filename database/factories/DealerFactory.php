<?php

namespace Database\Factories;

use App\Models\Dealer;
use Illuminate\Database\Eloquent\Factories\Factory;

class DealerFactory extends Factory
{
    protected $model = Dealer::class;

    public function definition(): array
    {
        return [
            'firm_name' => $this->faker->company(),
            'phone' => $this->faker->phoneNumber(),
            'location' => $this->faker->city(),
            'contact_person' => $this->faker->name(),
            'route' => $this->faker->randomElement(['North', 'South', 'East', 'West']),
            'pending_amount' => $this->faker->randomFloat(2, 0, 50000),
        ];
    }
}
