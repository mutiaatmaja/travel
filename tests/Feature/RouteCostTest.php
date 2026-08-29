<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\TravelRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_cost_is_persisted(): void
    {
        City::create([
            'code' => 'PNT',
            'name' => 'Pontianak',
            'province' => 'Kalimantan Barat',
            'is_active' => true,
        ]);

        City::create([
            'code' => 'SMT',
            'name' => 'Semitau',
            'province' => 'Kalimantan Barat',
            'is_active' => true,
        ]);

        TravelRoute::create([
            'code' => 'PNT-SMT',
            'origin_city_id' => 1,
            'destination_city_id' => 2,
            'name' => 'Pontianak - Semitau',
            'estimated_duration_minutes' => 600,
            'distance_km' => 420,
            'cost' => 245000,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('travel_routes', ['code' => 'PNT-SMT', 'cost' => 245000]);
        $this->assertSame('245000', (string) TravelRoute::first()->cost);
    }
}
