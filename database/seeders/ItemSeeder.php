<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Broiler Starter Feed',  'code' => 'BSF001', 'type' => 'Feed', 'category' => 'Starter',  'brand' => 'CP',        'base_unit' => 'kg', 'conversion_rate' => 1.00],
            ['name' => 'Broiler Grower Feed',   'code' => 'BGF002', 'type' => 'Feed', 'category' => 'Grower',   'brand' => 'CP',        'base_unit' => 'kg', 'conversion_rate' => 1.00],
            ['name' => 'Broiler Finisher Feed', 'code' => 'BFF003', 'type' => 'Feed', 'category' => 'Finisher', 'brand' => 'CP',        'base_unit' => 'kg', 'conversion_rate' => 1.00],
            ['name' => 'Layer Starter Feed',    'code' => 'LSF004', 'type' => 'Feed', 'category' => 'Starter',  'brand' => 'Suguna',    'base_unit' => 'kg', 'conversion_rate' => 1.00],
            ['name' => 'Layer Developer Feed',  'code' => 'LDF005', 'type' => 'Feed', 'category' => 'Developer','brand' => 'Suguna',    'base_unit' => 'kg', 'conversion_rate' => 1.00],
            ['name' => 'Layer Layer Feed',      'code' => 'LLF006', 'type' => 'Feed', 'category' => 'Layer',    'brand' => 'Suguna',    'base_unit' => 'kg', 'conversion_rate' => 1.00],
            ['name' => 'Broiler Chick Day Old','code' => 'BCD007', 'type' => 'Chick', 'category' => null,       'brand' => 'CP',        'base_unit' => 'nos','conversion_rate' => 1.00],
            ['name' => 'Layer Chick Day Old',   'code' => 'LCD008', 'type' => 'Chick', 'category' => null,       'brand' => 'Suguna',    'base_unit' => 'nos','conversion_rate' => 1.00],
            ['name' => 'Lasixox 10% Premix',   'code' => 'MED009', 'type' => 'Medicine','category' => null,     'brand' => 'Virbac',    'base_unit' => 'kg', 'conversion_rate' => 1.00],
            ['name' => 'Mycotoxin Binder',      'code' => 'MED010', 'type' => 'Medicine','category' => null,     'brand' => 'Cipla',     'base_unit' => 'kg', 'conversion_rate' => 1.00],
            ['name' => 'Newcastle Disease Vaccine','code' => 'VAC011','type' => 'Vaccine','category' => null,    'brand' => 'IAB',       'base_unit' => 'nos','conversion_rate' => 1.00],
            ['name' => 'Gumboro Vaccine',       'code' => 'VAC012', 'type' => 'Vaccine','category' => null,      'brand' => 'IAB',       'base_unit' => 'nos','conversion_rate' => 1.00],
            ['name' => 'Drinkers (Nipple)',      'code' => 'EQP013', 'type' => 'Equipment','category' => null,    'brand' => null,        'base_unit' => 'nos','conversion_rate' => 1.00],
            ['name' => 'Feeders (Round)',        'code' => 'EQP014', 'type' => 'Equipment','category' => null,    'brand' => null,        'base_unit' => 'nos','conversion_rate' => 1.00],
        ];

        foreach ($items as $item) {
            Item::create($item);
        }
    }
}
