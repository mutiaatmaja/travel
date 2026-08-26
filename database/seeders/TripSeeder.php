<?php

namespace Database\Seeders;

use App\Models\TravelRoute;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class TripSeeder extends Seeder
{
    public function run(): void
    {
        $routes = TravelRoute::pluck('id', 'code');
        $vehicle = Vehicle::where('code', 'TRG-001')->firstOrFail();
        $driverId = User::where('email', 'supir@example.com')->value('id');

        Trip::updateOrCreate(
            ['travel_route_id' => $routes['PNT-SKW'], 'departure_date' => now()->toDateString(), 'departure_time' => '08:00:00'],
            ['vehicle_id' => $vehicle->id, 'driver_id' => $driverId, 'estimated_arrival_time' => '12:00:00', 'status' => 'scheduled'],
        );

        Trip::updateOrCreate(
            ['travel_route_id' => $routes['SKW-PNT'], 'departure_date' => now()->addDay()->toDateString(), 'departure_time' => '08:00:00'],
            ['vehicle_id' => $vehicle->id, 'driver_id' => $driverId, 'estimated_arrival_time' => '12:00:00', 'status' => 'scheduled'],
        );
    }
}
