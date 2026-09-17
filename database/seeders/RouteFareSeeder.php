<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\RouteFare;
use App\Models\RouteStop;
use App\Models\TravelRoute;
use Illuminate\Database\Seeder;

class RouteFareSeeder extends Seeder
{
    public function run(): void
    {
        $outlets = Outlet::pluck('id', 'code');

        $fares = [
            ['route' => 'PNT-SMT', 'origin' => 'PNT-CENTER', 'destination' => 'SGG-CENTER', 'cost' => 250_000],
            ['route' => 'PNT-SMT', 'origin' => 'PNT-CENTER', 'destination' => 'SDK-CENTER', 'cost' => 300_000],
            ['route' => 'PNT-SMT', 'origin' => 'PNT-CENTER', 'destination' => 'STG-CENTER', 'cost' => 350_000],
            ['route' => 'PNT-SMT', 'origin' => 'PNT-CENTER', 'destination' => 'SMT-CENTER', 'cost' => 450_000],
            ['route' => 'SMT-PNT', 'origin' => 'SMT-CENTER', 'destination' => 'STG-CENTER', 'cost' => 150_000],
            ['route' => 'SMT-PNT', 'origin' => 'SMT-CENTER', 'destination' => 'SDK-CENTER', 'cost' => 250_000],
            ['route' => 'SMT-PNT', 'origin' => 'SMT-CENTER', 'destination' => 'SGG-CENTER', 'cost' => 350_000],
            ['route' => 'SMT-PNT', 'origin' => 'SMT-CENTER', 'destination' => 'PNT-CENTER', 'cost' => 450_000],
        ];

        foreach ($fares as $fare) {
            $route = TravelRoute::where('code', $fare['route'])->first();

            if (! $route) {
                continue;
            }

            $originStop = RouteStop::where('travel_route_id', $route->id)->where('outlet_id', $outlets[$fare['origin']])->first();
            $destinationStop = RouteStop::where('travel_route_id', $route->id)->where('outlet_id', $outlets[$fare['destination']])->first();

            if (! $originStop || ! $destinationStop) {
                continue;
            }

            RouteFare::updateOrCreate(
                [
                    'travel_route_id' => $route->id,
                    'origin_stop_id' => $originStop->id,
                    'destination_stop_id' => $destinationStop->id,
                ],
                [
                    'cost' => $fare['cost'],
                    'is_active' => true,
                ],
            );
        }
    }
}
