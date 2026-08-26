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
            ['code' => 'SKW', 'name' => 'Singkawang', 'province' => 'Kalimantan Barat'],
            ['code' => 'MMP', 'name' => 'Mempawah', 'province' => 'Kalimantan Barat'],
            ['code' => 'SPY', 'name' => 'Sungai Pinyuh', 'province' => 'Kalimantan Barat'],
            ['code' => 'PMK', 'name' => 'Pemangkat', 'province' => 'Kalimantan Barat'],
        ] as $city) {
            City::updateOrCreate(['code' => $city['code']], $city);
        }
    }
}
