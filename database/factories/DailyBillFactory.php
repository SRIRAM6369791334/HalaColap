<?php

namespace Database\Factories;

use App\Models\DailyBill;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class DailyBillFactory extends Factory
{
    protected $model = DailyBill::class;

    // Use a static array to pass data from afterMaking to afterCreating safely without touching model attributes
    private static array $tempItemData = [];

    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 100, 2000);
        $gstPercentage = $this->faker->randomElement([0, 5, 12, 18]);
        $gstAmount = $amount * ($gstPercentage / 100);

        return [
            'customer_id' => Customer::factory(),
            'date' => today(),
            'items_description' => $this->faker->sentence(),
            'quantity_kg' => $this->faker->randomFloat(2, 1, 50),
            'rate_per_kg' => $this->faker->randomFloat(2, 50, 100),
            'amount' => $amount,
            'gst_percentage' => $gstPercentage,
            'gst_amount' => $gstAmount,
            'net_amount' => $amount + $gstAmount,
            'payment_mode' => $this->faker->randomElement(['cash', 'credit']),
            'status' => 'Paid',
        ];
    }

    public function configure()
    {
        return $this->afterMaking(function (DailyBill $bill) {
            $itemsDescription = $bill->items_description ?? $this->faker->sentence();
            $quantityKg = $bill->quantity_kg ?? $this->faker->randomFloat(2, 1, 50);
            $ratePerKg = $bill->rate_per_kg ?? $this->faker->randomFloat(2, 50, 100);

            // Compute subtotal and taxes if needed
            $amount = $quantityKg * $ratePerKg;
            $gstPercentage = $bill->gst_percentage ?? 0;
            $gstAmount = $amount * ($gstPercentage / 100);

            // Set computed fields on parent DailyBill model if not already set
            if ($bill->amount === null) {
                $bill->amount = $amount;
            }
            if ($bill->gst_amount === null) {
                $bill->gst_amount = $gstAmount;
            }
            if ($bill->net_amount === null) {
                $bill->net_amount = $bill->amount + $bill->gst_amount;
            }

            // Store items data temporarily using spl_object_hash
            $hash = spl_object_hash($bill);
            self::$tempItemData[$hash] = [
                'item_name' => $itemsDescription,
                'quantity_kg' => $quantityKg,
                'rate_per_kg' => $ratePerKg,
                'tax_amount' => $bill->gst_amount,
                'total_amount' => $bill->amount,
            ];

            // Unset columns that are not in daily_bills table anymore
            unset($bill['items_description']);
            unset($bill['quantity_kg']);
            unset($bill['rate_per_kg']);
        })->afterCreating(function (DailyBill $bill) {
            $hash = spl_object_hash($bill);
            $itemData = self::$tempItemData[$hash] ?? [
                'item_name' => 'Poultry Birds',
                'quantity_kg' => 10,
                'rate_per_kg' => 50,
                'tax_amount' => 0,
                'total_amount' => 500,
            ];
            $bill->items()->create($itemData);

            // Clean up to keep memory usage low
            unset(self::$tempItemData[$hash]);
        });
    }
}

