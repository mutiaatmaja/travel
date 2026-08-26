<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CitySeeder::class,
            OutletSeeder::class,
            VehicleSeeder::class,
            RouteSeeder::class,
            TripSeeder::class,
        ]);
    }
}
