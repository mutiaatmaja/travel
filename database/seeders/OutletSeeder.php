<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Outlet;
use Illuminate\Database\Seeder;

class OutletSeeder extends Seeder
{
    public function run(): void
    {
        $cities = City::pluck('id', 'code');

        foreach ([
            ['city' => 'PNT', 'code' => 'PNT-CENTER', 'name' => 'Outlet Pontianak Center', 'address' => 'Jl. Gajah Mada No. 10, Pontianak'],
            ['city' => 'SGG', 'code' => 'SGG-CENTER', 'name' => 'Outlet Sanggau Center', 'address' => 'Jl. Jenderal Sudirman No. 8, Sanggau'],
            ['city' => 'SDK', 'code' => 'SDK-CENTER', 'name' => 'Outlet Sekadau Center', 'address' => 'Jl. Merdeka No. 12, Sekadau'],
            ['city' => 'STG', 'code' => 'STG-CENTER', 'name' => 'Outlet Sintang Center', 'address' => 'Jl. Lintas Melawi No. 5, Sintang'],
            ['city' => 'SMT', 'code' => 'SMT-CENTER', 'name' => 'Outlet Semitau Center', 'address' => 'Jl. Lintas Kapuas No. 21, Semitau'],
        ] as $outlet) {
            Outlet::create(['city_id' => $cities[$outlet['city']], 'code' => $outlet['code'], 'name' => $outlet['name'], 'address' => $outlet['address'], 'phone' => '0562-000000', 'is_active' => true]);
        }
    }
}
