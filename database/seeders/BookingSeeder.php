<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        foreach ([
            ['trip' => 'scheduled', 'route' => 'PNT-SMT', 'name' => 'Budi Santoso', 'origin' => 1, 'destination' => 2, 'seats' => ['1', '2'], 'status' => 'confirmed'],
            ['trip' => 'scheduled', 'route' => 'PNT-SMT', 'name' => 'Siti Aminah', 'origin' => 2, 'destination' => 5, 'seats' => ['1'], 'status' => 'pending'],
            ['trip' => 'scheduled', 'route' => 'PNT-SMT', 'name' => 'Rina Marlina', 'origin' => 1, 'destination' => 5, 'seats' => ['3', '4'], 'status' => 'pending'],
            ['trip' => 'scheduled', 'route' => 'PNT-SMT', 'name' => 'Dedi Saputra', 'origin' => 1, 'destination' => 3, 'seats' => ['5'], 'status' => 'cancelled'],
            ['trip' => 'scheduled', 'route' => 'SMT-PNT', 'name' => 'Agus Salim', 'origin' => 1, 'destination' => 5, 'seats' => ['1', '2', '3'], 'status' => 'confirmed'],
            ['trip' => 'scheduled', 'route' => 'SMT-PNT', 'name' => 'Dewi Lestari', 'origin' => 2, 'destination' => 4, 'seats' => ['4'], 'status' => 'pending'],
            ['trip' => 'on_the_way', 'route' => 'PNT-SMT', 'name' => 'Hendra Wijaya', 'origin' => 1, 'destination' => 5, 'seats' => ['1', '2'], 'status' => 'confirmed'],
            ['trip' => 'on_the_way', 'route' => 'PNT-SMT', 'name' => 'Ratna Sari', 'origin' => 2, 'destination' => 4, 'seats' => ['3'], 'status' => 'confirmed'],
            ['trip' => 'on_the_way', 'route' => 'PNT-SMT', 'name' => 'Yuni Astuti', 'origin' => 3, 'destination' => 5, 'seats' => ['4'], 'status' => 'confirmed'],
            ['trip' => 'boarding', 'route' => 'SMT-PNT', 'name' => 'Fajar Pratama', 'origin' => 1, 'destination' => 3, 'seats' => ['1', '2'], 'status' => 'confirmed'],
            ['trip' => 'boarding', 'route' => 'SMT-PNT', 'name' => 'Lina Susanti', 'origin' => 2, 'destination' => 5, 'seats' => ['3'], 'status' => 'pending'],
            ['trip' => 'completed', 'route' => 'PNT-SMT', 'name' => 'Rahmat Hidayat', 'origin' => 1, 'destination' => 5, 'seats' => ['1', '2'], 'status' => 'completed'],
            ['trip' => 'cancelled', 'route' => 'SMT-PNT', 'name' => 'Indah Permata', 'origin' => 1, 'destination' => 5, 'seats' => ['1'], 'status' => 'cancelled'],
        ] as $index => $data) {
            $trip = Trip::where('status', $data['trip'])->whereHas('travelRoute', fn ($query) => $query->where('code', $data['route']))->firstOrFail();
            $stops = $trip->travelRoute->stops->keyBy('stop_sequence');
            $fare = $trip->travelRoute->fares()->where('origin_stop_id', $stops[$data['origin']]->id)->where('destination_stop_id', $stops[$data['destination']]->id)->firstOrFail();
            $isPaid = in_array($data['status'], ['confirmed', 'completed'], true);
            $booking = Booking::updateOrCreate(['booking_code' => 'BKG-DEMO-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)], [
                'trip_id' => $trip->id, 'origin_stop_id' => $fare->origin_stop_id, 'destination_stop_id' => $fare->destination_stop_id,
                'route_fare_id' => $fare->id, 'customer_name' => $data['name'], 'phone' => '08123456'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'passenger_count' => count($data['seats']), 'total_cost' => $fare->cost * count($data['seats']),
                'status' => $data['status'], 'confirmed_by' => $isPaid ? $admin->id : null,
                'confirmed_at' => $isPaid ? now()->subDays(2) : null,
                'cancelled_at' => $data['status'] === 'cancelled' ? now()->subDay() : null,
                'notes' => 'Data demo untuk pengujian booking dan kursi per segmen.',
                'created_at' => now()->subDays(3),
            ]);
            foreach ($data['seats'] as $seatNumber) {
                $seat = $trip->vehicle->seats()->where('seat_number', $seatNumber)->firstOrFail();
                $booking->seats()->firstOrCreate(['vehicle_seat_id' => $seat->id]);
            }
        }
    }
}
