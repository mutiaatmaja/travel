<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use App\Models\VehicleSeat;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $vehicle = Vehicle::create([
            'code' => 'TRG-001',
            'license_plate' => 'KB 1234 XX',
            'type' => 'Minibus',
            'brand' => 'Toyota',
            'model' => 'HiAce',
            'seat_capacity' => 10,
            'status' => 'active',
        ]);

        for ($seatNumber = 1; $seatNumber <= 10; $seatNumber++) {
            VehicleSeat::create([
                'vehicle_id' => $vehicle->id,
                'seat_number' => (string) $seatNumber,
                'seat_row' => (int) ceil($seatNumber / 2),
                'seat_column' => $seatNumber % 2 === 0 ? 2 : 1,
                'seat_type' => 'regular',
                'is_active' => true,
            ]);
        }
    }
}
