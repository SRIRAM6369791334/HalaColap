<?php

namespace Database\Factories;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return [
            'firm_name' => $this->faker->company(),
            'phone' => $this->faker->phoneNumber(),
            'location' => $this->faker->city(),
            'contact_person' => $this->faker->name(),
            'route' => $this->faker->randomElement(['North', 'South', 'East', 'West']),
        ];
    }
}
