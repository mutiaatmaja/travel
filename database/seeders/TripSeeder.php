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
        foreach ([
            ['route' => 'PNT-SMT', 'days' => 1, 'time' => '08:00', 'status' => 'scheduled', 'vehicle' => 'TRG-001', 'driver' => 'supir@example.com', 'position' => null],
            ['route' => 'SMT-PNT', 'days' => 2, 'time' => '08:00', 'status' => 'scheduled', 'vehicle' => 'TRG-002', 'driver' => 'supir2@example.com', 'position' => null],
            ['route' => 'PNT-SMT', 'days' => 0, 'time' => '08:00', 'status' => 'on_the_way', 'vehicle' => 'TRG-001', 'driver' => 'supir@example.com', 'position' => 2],
            ['route' => 'SMT-PNT', 'days' => 0, 'time' => '16:00', 'status' => 'boarding', 'vehicle' => 'TRG-002', 'driver' => 'supir2@example.com', 'position' => 1],
            ['route' => 'PNT-SMT', 'days' => -1, 'time' => '08:00', 'status' => 'completed', 'vehicle' => 'TRG-001', 'driver' => 'supir@example.com', 'position' => 5],
            ['route' => 'SMT-PNT', 'days' => -1, 'time' => '08:00', 'status' => 'cancelled', 'vehicle' => 'TRG-002', 'driver' => 'supir2@example.com', 'position' => null],
            ['route' => 'PNT-SMT', 'days' => 3, 'time' => '08:00', 'status' => 'draft', 'vehicle' => 'TRG-003', 'driver' => 'supir3@example.com', 'position' => null],
        ] as $data) {
            $route = TravelRoute::where('code', $data['route'])->firstOrFail();
            $driver = User::where('email', $data['driver'])->firstOrFail();
            $date = now()->addDays($data['days'])->toDateString();
            $trip = Trip::where('travel_route_id', $route->id)
                ->whereDate('departure_date', $date)
                ->where('departure_time', $data['time'].':00')
                ->first() ?? new Trip;
            $trip->fill([
                'travel_route_id' => $route->id, 'departure_date' => $date, 'departure_time' => $data['time'].':00',
                'vehicle_id' => Vehicle::where('code', $data['vehicle'])->firstOrFail()->id,
                'driver_id' => $driver->id, 'estimated_arrival_time' => $data['time'] === '16:00' ? '23:00:00' : '18:00:00',
                'status' => $data['status'],
            ]);
            $trip->save();
            if ($data['position'] === null) {
                continue;
            }
            foreach ($route->stops()->where('stop_sequence', '<=', $data['position'])->get() as $stop) {
                $reportedAt = now()->addDays($data['days'])->subMinutes(($data['position'] - $stop->stop_sequence) * 120 + 10);
                $trip->positionReports()->updateOrCreate(['route_stop_id' => $stop->id], [
                    'reported_by' => $driver->id, 'reported_at' => $reportedAt,
                ]);
                $trip->update(['current_stop_id' => $stop->id, 'position_updated_at' => $reportedAt, 'position_updated_by' => $driver->id]);
            }
        }
    }
}
