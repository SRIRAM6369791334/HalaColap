<?php

namespace Database\Factories;

use App\Models\DealerPayment;
use App\Models\Dealer;
use Illuminate\Database\Eloquent\Factories\Factory;

class DealerPaymentFactory extends Factory
{
    protected $model = DealerPayment::class;

    public function definition(): array
    {
        return [
            'dealer_id' => Dealer::factory(),
            'amount' => $this->faker->randomFloat(2, 100, 5000),
            'date' => today(),
            'payment_mode' => $this->faker->randomElement(['Cash', 'UPI', 'NEFT', 'Cheque']),
            'notes' => $this->faker->sentence(),
        ];
    }
}
