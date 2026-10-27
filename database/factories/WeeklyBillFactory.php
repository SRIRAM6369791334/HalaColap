<?php

namespace Database\Factories;

use App\Models\WeeklyBill;
use App\Models\Dealer;
use Illuminate\Database\Eloquent\Factories\Factory;

class WeeklyBillFactory extends Factory
{
    protected $model = WeeklyBill::class;

    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 1000, 5000);
        $gstPercentage = 5;
        $gstAmount = $amount * ($gstPercentage / 100);

        return [
            'dealer_id' => Dealer::factory(),
            'period_start' => today()->subDays(7),
            'period_end' => today(),
            'amount' => $amount,
            'gst_percentage' => $gstPercentage,
            'gst_amount' => $gstAmount,
            'net_amount' => $amount + $gstAmount,
            'status' => $this->faker->randomElement(['Generated', 'Pending', 'Paid']),
            'payment_mode' => $this->faker->randomElement(['cash', 'credit', 'online']),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (WeeklyBill $bill) {
            $bill->items()->create([
                'item_name' => 'Chicks & Feed',
                'quantity_kg' => $this->faker->randomFloat(2, 500, 2000),
                'rate_per_kg' => round($bill->amount / 1000, 2),
                'tax_amount' => $bill->gst_amount,
                'total_amount' => $bill->net_amount,
            ]);
        });
    }
}
