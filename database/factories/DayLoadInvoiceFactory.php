<?php

namespace Database\Factories;

use App\Models\DayLoadBatch;
use App\Models\DayLoadInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class DayLoadInvoiceFactory extends Factory
{
    protected $model = DayLoadInvoice::class;

    public function definition(): array
    {
        return [
            'batch_id' => DayLoadBatch::factory(),
            'invoice_no' => 'DL-' . $this->faker->unique()->numerify('########'),
            'invoice_date' => today(),
            'status' => 'Draft',
            'version' => 1,
        ];
    }
}
