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

        foreach ([
            ['route' => 'PNT-SMT', 'date' => now()->toDateString()],
            ['route' => 'SMT-PNT', 'date' => now()->addDay()->toDateString()],
        ] as $tripData) {
            Trip::create([
                'travel_route_id' => $routes[$tripData['route']],
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driverId,
                'departure_date' => $tripData['date'],
                'departure_time' => '08:00:00',
                'estimated_arrival_time' => '18:00:00',
                'status' => 'scheduled',
            ]);
        }
    }
}
