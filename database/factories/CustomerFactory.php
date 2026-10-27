<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'route' => $this->faker->randomElement(['Route A', 'Route B', 'Route C']),
            'balance' => $this->faker->randomFloat(2, 0, 10000),
            'type' => $this->faker->randomElement(['Retail', 'Wholesale']),
        ];
    }
}
