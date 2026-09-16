<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Outlet;
use App\Models\RouteFare;
use App\Models\RouteStop;
use App\Models\TravelRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteFareTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_fare_stores_cost_between_ordered_route_stops(): void
    {
        $originCity = City::create(['code' => 'PTK', 'name' => 'Pontianak', 'is_active' => true]);
        $destinationCity = City::create(['code' => 'SMT', 'name' => 'Semitau', 'is_active' => true]);
        $route = TravelRoute::create([
            'code' => 'PTK-SMT',
            'origin_city_id' => $originCity->id,
            'destination_city_id' => $destinationCity->id,
            'name' => 'Pontianak - Semitau',
            'estimated_duration_minutes' => 480,
            'cost' => 250000,
            'is_active' => true,
        ]);

        $originOutlet = Outlet::create(['city_id' => $originCity->id, 'code' => 'OUT-PTK', 'name' => 'Outlet Pontianak', 'address' => 'Pontianak Center', 'is_active' => true]);
        $destinationOutlet = Outlet::create(['city_id' => $destinationCity->id, 'code' => 'OUT-SMT', 'name' => 'Outlet Semitau', 'address' => 'Semitau Center', 'is_active' => true]);
        $originStop = RouteStop::create(['travel_route_id' => $route->id, 'outlet_id' => $originOutlet->id, 'stop_sequence' => 1]);
        $destinationStop = RouteStop::create(['travel_route_id' => $route->id, 'outlet_id' => $destinationOutlet->id, 'stop_sequence' => 2]);
        $fare = RouteFare::create(['travel_route_id' => $route->id, 'origin_stop_id' => $originStop->id, 'destination_stop_id' => $destinationStop->id, 'cost' => 250000, 'is_active' => true]);

        $this->assertSame(250000, $fare->fresh()->cost);
        $this->assertSame($originStop->id, $fare->originStop->id);
        $this->assertSame($destinationStop->id, $fare->destinationStop->id);
    }
}
