<?php

namespace Database\Seeders;

use App\Models\TravelRoute;
use Illuminate\Database\Seeder;

class RouteFareSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TravelRoute::with('stops.outlet.city')->get() as $route) {
            $stops = $route->stops->values();
            foreach ($stops as $originIndex => $origin) {
                foreach ($stops as $destinationIndex => $destination) {
                    if ($destinationIndex <= $originIndex) {
                        continue;
                    }
                    $cost = match ($destinationIndex - $originIndex) {
                        1 => 150000, 2 => 250000, 3 => 350000, default => 450000,
                    };
                    if ($origin->outlet->city->code === 'PNT') {
                        $cost = match ($destination->outlet->city->code) {
                            'SGG' => 250000, 'SDK' => 300000, 'STG' => 350000, default => 450000,
                        };
                    }
                    $route->fares()->updateOrCreate([
                        'origin_stop_id' => $origin->id, 'destination_stop_id' => $destination->id,
                    ], ['cost' => $cost, 'is_active' => true]);
                }
            }
        }
    }
}
