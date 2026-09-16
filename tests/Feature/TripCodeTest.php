<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\TravelRoute;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_trip_code_is_generated_automatically_and_sequential_per_day(): void
    {
        $origin = City::create(['code' => 'PTK', 'name' => 'Pontianak', 'is_active' => true]);
        $destination = City::create(['code' => 'SMT', 'name' => 'Semitau', 'is_active' => true]);

        $route = TravelRoute::create([
            'code' => 'RUTE-001',
            'origin_city_id' => $origin->id,
            'destination_city_id' => $destination->id,
            'name' => 'Pontianak - Semitau',
            'estimated_duration_minutes' => 480,
            'cost' => 150000,
            'is_active' => true,
        ]);

        $vehicle = Vehicle::create([
            'code' => 'B-01',
            'license_plate' => 'B 1234 AB',
            'type' => 'bus',
            'seat_capacity' => 28,
            'status' => 'active',
        ]);

        $firstTrip = Trip::create([
            'travel_route_id' => $route->id,
            'vehicle_id' => $vehicle->id,
            'departure_date' => '2026-09-16',
            'departure_time' => '06:30',
            'status' => 'scheduled',
        ]);

        $secondTrip = Trip::create([
            'travel_route_id' => $route->id,
            'vehicle_id' => $vehicle->id,
            'departure_date' => '2026-09-16',
            'departure_time' => '09:00',
            'status' => 'scheduled',
        ]);

        $this->assertSame('TRP-20260916-001', $firstTrip->trip_code);
        $this->assertSame('TRP-20260916-002', $secondTrip->trip_code);
        $this->assertNotSame($firstTrip->trip_code, $secondTrip->trip_code);
    }
}
