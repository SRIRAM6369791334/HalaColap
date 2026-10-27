<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    // Use a static array to pass data from afterMaking to afterCreating safely without touching model attributes
    private static array $tempItemData = [];

    public function definition(): array
    {
        $rate = $this->faker->randomFloat(2, 50, 100);
        $quantity = $this->faker->randomFloat(2, 10, 100);
        $subtotal = $rate * $quantity;
        $gstPercentage = 18;
        $gstAmount = $subtotal * ($gstPercentage / 100);

        return [
            'vendor_id' => Vendor::factory(),
            'vendor_name' => function (array $attributes) {
                return Vendor::find($attributes['vendor_id'])->firm_name;
            },
            'date' => today(),
            'item' => $this->faker->randomElement(['Feed', 'Medicine', 'Chicks', 'Equipments']),
            'quantity' => $quantity,
            'unit' => 'kg',
            'rate' => $rate,
            'gst_percentage' => $gstPercentage,
            'gst_amount' => $gstAmount,
            'total_amount' => $subtotal + $gstAmount,
            'payment_mode' => $this->faker->randomElement(['NEFT', 'Cheque', 'UPI', 'Cash']),
        ];
    }

    public function configure()
    {
        return $this->afterMaking(function (Purchase $purchase) {
            $quantity = $purchase->quantity;
            $rate = $purchase->rate;
            $item = $purchase->item ?? 'Feed';
            $unit = $purchase->unit ?? 'kg';
            $gstPercent = $purchase->gst_percentage ?? 18;

            if ($quantity !== null && $rate !== null) {
                $subtotal = $quantity * $rate;
                $purchase->gst_amount = $subtotal * ($gstPercent / 100);
                $purchase->total_amount = $subtotal + $purchase->gst_amount;
            }

            // Store items data temporarily using spl_object_hash to avoid setting model attributes
            $hash = spl_object_hash($purchase);
            self::$tempItemData[$hash] = [
                'item_name' => $item,
                'quantity' => $quantity ?? 10,
                'unit' => $unit,
                'rate' => $rate ?? 50,
                'tax_amount' => $purchase->gst_amount ?? 0,
                'total_amount' => $purchase->total_amount ?? 100,
            ];

            // Unset columns that are not in purchases table anymore
            unset($purchase['item']);
            unset($purchase['quantity']);
            unset($purchase['unit']);
            unset($purchase['rate']);
        })->afterCreating(function (Purchase $purchase) {
            $hash = spl_object_hash($purchase);
            $itemData = self::$tempItemData[$hash] ?? [
                'item_name' => 'Feed',
                'quantity' => 10,
                'unit' => 'kg',
                'rate' => 50,
                'tax_amount' => $purchase->gst_amount ?? 0,
                'total_amount' => $purchase->total_amount ?? 100,
            ];
            $purchase->items()->create($itemData);

            // Clean up to keep memory usage low
            unset(self::$tempItemData[$hash]);
        });
    }
}
