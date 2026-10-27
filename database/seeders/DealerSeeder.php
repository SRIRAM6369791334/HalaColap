<?php

namespace Database\Seeders;

use App\Models\Dealer;
use Illuminate\Database\Seeder;

class DealerSeeder extends Seeder
{
    public function run(): void
    {
        $dealers = [
            ['firm_name' => 'dealer1', 'phone' => '9800000001', 'location' => 'Location 1', 'contact_person' => 'Contact 1', 'route' => 'Route A', 'pending_amount' => 0],
            ['firm_name' => 'dealer2', 'phone' => '9800000002', 'location' => 'Location 2', 'contact_person' => 'Contact 2', 'route' => 'Route A', 'pending_amount' => 0],
            ['firm_name' => 'dealer3', 'phone' => '9800000003', 'location' => 'Location 3', 'contact_person' => 'Contact 3', 'route' => 'Route B', 'pending_amount' => 0],
            ['firm_name' => 'dealer4', 'phone' => '9800000004', 'location' => 'Location 4', 'contact_person' => 'Contact 4', 'route' => 'Route B', 'pending_amount' => 0],
            ['firm_name' => 'dealer5', 'phone' => '9800000005', 'location' => 'Location 5', 'contact_person' => 'Contact 5', 'route' => 'Route C', 'pending_amount' => 0],
            ['firm_name' => 'dealer6', 'phone' => '9800000006', 'location' => 'Location 6', 'contact_person' => 'Contact 6', 'route' => 'Route C', 'pending_amount' => 0],
        ];

        foreach ($dealers as $dealer) {
            Dealer::create($dealer);
        }
    }
}
