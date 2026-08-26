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
            ['city' => 'SPY', 'code' => 'SPY-CENTER', 'name' => 'Outlet Sungai Pinyuh', 'address' => 'Jl. Raya Sungai Pinyuh No. 8'],
            ['city' => 'MMP', 'code' => 'MMP-CENTER', 'name' => 'Outlet Mempawah Center', 'address' => 'Jl. Gusti M. Taufik No. 12, Mempawah'],
            ['city' => 'PMK', 'code' => 'PMK-CENTER', 'name' => 'Outlet Pemangkat', 'address' => 'Jl. Pembangunan No. 5, Pemangkat'],
            ['city' => 'SKW', 'code' => 'SKW-CENTER', 'name' => 'Outlet Singkawang Center', 'address' => 'Jl. Diponegoro No. 21, Singkawang'],
        ] as $outlet) {
            Outlet::updateOrCreate(
                ['code' => $outlet['code']],
                ['city_id' => $cities[$outlet['city']], 'name' => $outlet['name'], 'address' => $outlet['address'], 'phone' => '0562-000000', 'is_active' => true],
            );
        }
    }
}
