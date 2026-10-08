<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'TRG-001', 'license_plate' => 'KB 1234 XX', 'brand' => 'Toyota', 'model' => 'HiAce', 'capacity' => 10, 'status' => 'active'],
            ['code' => 'TRG-002', 'license_plate' => 'KB 2345 XX', 'brand' => 'Toyota', 'model' => 'HiAce Premio', 'capacity' => 12, 'status' => 'active'],
            ['code' => 'TRG-003', 'license_plate' => 'KB 3456 XX', 'brand' => 'Isuzu', 'model' => 'Elf', 'capacity' => 16, 'status' => 'active'],
            ['code' => 'TRG-004', 'license_plate' => 'KB 4567 XX', 'brand' => 'Toyota', 'model' => 'HiAce', 'capacity' => 10, 'status' => 'maintenance'],
        ] as $data) {
            $vehicle = Vehicle::updateOrCreate(['code' => $data['code']], [
                'license_plate' => $data['license_plate'], 'type' => 'Minibus',
                'brand' => $data['brand'], 'model' => $data['model'],
                'seat_capacity' => $data['capacity'], 'status' => $data['status'],
            ]);
            for ($seatNumber = 1; $seatNumber <= $data['capacity']; $seatNumber++) {
                $vehicle->seats()->updateOrCreate(['seat_number' => (string) $seatNumber], [
                    'seat_row' => (int) ceil($seatNumber / 2),
                    'seat_column' => $seatNumber % 2 === 0 ? 2 : 1,
                    'seat_type' => 'regular', 'is_active' => true,
                ]);
            }
        }
    }
}
