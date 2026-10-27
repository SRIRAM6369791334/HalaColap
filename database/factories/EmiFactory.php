<?php

namespace Database\Factories;

use App\Models\Emi;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmiFactory extends Factory
{
    protected $model = Emi::class;

    public function definition(): array
    {
        return [
            'loan_name' => $this->faker->sentence(2),
            'amount' => $this->faker->randomFloat(2, 1000, 5000),
            'due_date' => $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'status' => 'Upcoming',
            'bank_name' => $this->faker->company(),
        ];
    }
}
