<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = [
            ['firm_name' => 'Vendor1',  'phone' => '9800000001', 'location' => 'Location 1',  'contact_person' => 'Contact 1',  'route' => 'Route A'],
            ['firm_name' => 'Vendor2',  'phone' => '9800000002', 'location' => 'Location 2',  'contact_person' => 'Contact 2',  'route' => 'Route A'],
            ['firm_name' => 'Vendor3',  'phone' => '9800000003', 'location' => 'Location 3',  'contact_person' => 'Contact 3',  'route' => 'Route A'],
            ['firm_name' => 'Vendor4',  'phone' => '9800000004', 'location' => 'Location 4',  'contact_person' => 'Contact 4',  'route' => 'Route B'],
            ['firm_name' => 'Vendor5',  'phone' => '9800000005', 'location' => 'Location 5',  'contact_person' => 'Contact 5',  'route' => 'Route B'],
            ['firm_name' => 'Vendor6',  'phone' => '9800000006', 'location' => 'Location 6',  'contact_person' => 'Contact 6',  'route' => 'Route B'],
            ['firm_name' => 'Vendor7',  'phone' => '9800000007', 'location' => 'Location 7',  'contact_person' => 'Contact 7',  'route' => 'Route C'],
            ['firm_name' => 'Vendor8',  'phone' => '9800000008', 'location' => 'Location 8',  'contact_person' => 'Contact 8',  'route' => 'Route C'],
            ['firm_name' => 'Vendor9',  'phone' => '9800000009', 'location' => 'Location 9',  'contact_person' => 'Contact 9',  'route' => 'Route C'],
            ['firm_name' => 'Vendor10', 'phone' => '9800000010', 'location' => 'Location 10', 'contact_person' => 'Contact 10', 'route' => 'Route D'],
            ['firm_name' => 'Vendor11', 'phone' => '9800000011', 'location' => 'Location 11', 'contact_person' => 'Contact 11', 'route' => 'Route D'],
            ['firm_name' => 'Vendor12', 'phone' => '9800000012', 'location' => 'Location 12', 'contact_person' => 'Contact 12', 'route' => 'Route D'],
            ['firm_name' => 'Vendor13', 'phone' => '9800000013', 'location' => 'Location 13', 'contact_person' => 'Contact 13', 'route' => 'Route E'],
            ['firm_name' => 'Vendor14', 'phone' => '9800000014', 'location' => 'Location 14', 'contact_person' => 'Contact 14', 'route' => 'Route E'],
            ['firm_name' => 'Vendor15', 'phone' => '9800000015', 'location' => 'Location 15', 'contact_person' => 'Contact 15', 'route' => 'Route E'],
            ['firm_name' => 'Vendor16', 'phone' => '9800000016', 'location' => 'Location 16', 'contact_person' => 'Contact 16', 'route' => 'Route F'],
            ['firm_name' => 'Vendor17', 'phone' => '9800000017', 'location' => 'Location 17', 'contact_person' => 'Contact 17', 'route' => 'Route F'],
        ];

        foreach ($vendors as $vendor) {
            Vendor::create($vendor);
        }
    }
}
