<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'PNT', 'name' => 'Pontianak', 'province' => 'Kalimantan Barat'],
            ['code' => 'SGG', 'name' => 'Sanggau', 'province' => 'Kalimantan Barat'],
            ['code' => 'SDK', 'name' => 'Sekadau', 'province' => 'Kalimantan Barat'],
            ['code' => 'STG', 'name' => 'Sintang', 'province' => 'Kalimantan Barat'],
            ['code' => 'SMT', 'name' => 'Semitau', 'province' => 'Kalimantan Barat'],
        ] as $city) {
            City::create([...$city, 'is_active' => true]);
        }
    }
}
