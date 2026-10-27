<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'category' => $this->faker->randomElement(['Salary', 'Fuel', 'Utilities', 'Maintenance']),
            'description' => $this->faker->sentence(),
            'amount' => $this->faker->randomFloat(2, 50, 2000),
            'date' => today(),
        ];
    }
}
