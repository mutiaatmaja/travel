<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Outlet;
use App\Models\RouteStop;
use App\Models\TravelRoute;
use Illuminate\Database\Seeder;

class RouteSeeder extends Seeder
{
    public function run(): void
    {
        $cities = City::pluck('id', 'code');
        $outlets = Outlet::pluck('id', 'code');

        $routes = [
            ['code' => 'PNT-SMT', 'origin' => 'PNT', 'destination' => 'SMT', 'name' => 'Pontianak - Semitau', 'distance_km' => 420, 'duration' => 600, 'cost' => 245000, 'stops' => ['PNT-CENTER', 'SGG-CENTER', 'SDK-CENTER', 'STG-CENTER', 'SMT-CENTER']],
            ['code' => 'SMT-PNT', 'origin' => 'SMT', 'destination' => 'PNT', 'name' => 'Semitau - Pontianak', 'distance_km' => 420, 'duration' => 600, 'cost' => 245000, 'stops' => ['SMT-CENTER', 'STG-CENTER', 'SDK-CENTER', 'SGG-CENTER', 'PNT-CENTER']],
        ];

        foreach ($routes as $routeData) {
            $route = TravelRoute::create([
                'code' => $routeData['code'],
                'origin_city_id' => $cities[$routeData['origin']],
                'destination_city_id' => $cities[$routeData['destination']],
                'name' => $routeData['name'],
                'distance_km' => $routeData['distance_km'],
                'estimated_duration_minutes' => $routeData['duration'],
                'cost' => $routeData['cost'],
                'is_active' => true,
            ]);

            foreach ($routeData['stops'] as $index => $outletCode) {
                RouteStop::create([
                    'travel_route_id' => $route->id,
                    'outlet_id' => $outlets[$outletCode],
                    'stop_sequence' => $index + 1,
                    'arrival_offset_minutes' => $index * 120,
                    'departure_offset_minutes' => $index * 120 + 10,
                    'is_boarding_allowed' => true,
                    'is_dropoff_allowed' => true,
                ]);
            }
        }
    }
}
