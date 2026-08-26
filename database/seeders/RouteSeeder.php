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
            ['code' => 'PNT-SKW', 'origin' => 'PNT', 'destination' => 'SKW', 'name' => 'Pontianak - Singkawang', 'distance_km' => 145, 'duration' => 240, 'stops' => ['PNT-CENTER', 'SPY-CENTER', 'MMP-CENTER', 'PMK-CENTER', 'SKW-CENTER']],
            ['code' => 'SKW-PNT', 'origin' => 'SKW', 'destination' => 'PNT', 'name' => 'Singkawang - Pontianak', 'distance_km' => 145, 'duration' => 240, 'stops' => ['SKW-CENTER', 'PMK-CENTER', 'MMP-CENTER', 'SPY-CENTER', 'PNT-CENTER']],
        ];

        foreach ($routes as $routeData) {
            $route = TravelRoute::updateOrCreate(
                ['code' => $routeData['code']],
                ['origin_city_id' => $cities[$routeData['origin']], 'destination_city_id' => $cities[$routeData['destination']], 'name' => $routeData['name'], 'distance_km' => $routeData['distance_km'], 'estimated_duration_minutes' => $routeData['duration'], 'is_active' => true],
            );

            foreach ($routeData['stops'] as $index => $outletCode) {
                RouteStop::updateOrCreate(
                    ['travel_route_id' => $route->id, 'stop_sequence' => $index + 1],
                    ['outlet_id' => $outlets[$outletCode], 'arrival_offset_minutes' => $index * 60, 'departure_offset_minutes' => $index * 60 + 10, 'is_boarding_allowed' => true, 'is_dropoff_allowed' => true],
                );
            }
        }
    }
}
