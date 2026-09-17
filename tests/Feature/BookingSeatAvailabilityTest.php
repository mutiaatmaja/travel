<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\City;
use App\Models\Outlet;
use App\Models\RouteStop;
use App\Models\TravelRoute;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\VehicleSeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingSeatAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private function makeTrip(): array
    {
        $pontianak = City::create(['code' => 'PNT', 'name' => 'Pontianak', 'is_active' => true]);
        $sanggau = City::create(['code' => 'SGG', 'name' => 'Sanggau', 'is_active' => true]);
        $sintang = City::create(['code' => 'STG', 'name' => 'Sintang', 'is_active' => true]);

        $route = TravelRoute::create([
            'code' => 'PNT-STG',
            'origin_city_id' => $pontianak->id,
            'destination_city_id' => $sintang->id,
            'name' => 'Pontianak - Sintang',
            'estimated_duration_minutes' => 300,
            'cost' => 350_000,
            'is_active' => true,
        ]);

        $outletPontianak = Outlet::create(['city_id' => $pontianak->id, 'code' => 'OUT-PNT', 'name' => 'Outlet Pontianak', 'address' => 'Pontianak', 'is_active' => true]);
        $outletSanggau = Outlet::create(['city_id' => $sanggau->id, 'code' => 'OUT-SGG', 'name' => 'Outlet Sanggau', 'address' => 'Sanggau', 'is_active' => true]);
        $outletSintang = Outlet::create(['city_id' => $sintang->id, 'code' => 'OUT-STG', 'name' => 'Outlet Sintang', 'address' => 'Sintang', 'is_active' => true]);

        $stopPontianak = RouteStop::create(['travel_route_id' => $route->id, 'outlet_id' => $outletPontianak->id, 'stop_sequence' => 1]);
        $stopSanggau = RouteStop::create(['travel_route_id' => $route->id, 'outlet_id' => $outletSanggau->id, 'stop_sequence' => 2]);
        $stopSintang = RouteStop::create(['travel_route_id' => $route->id, 'outlet_id' => $outletSintang->id, 'stop_sequence' => 3]);

        $vehicle = Vehicle::create(['code' => 'B-01', 'license_plate' => 'B 1234 AB', 'type' => 'bus', 'seat_capacity' => 1, 'status' => 'active']);
        $seat = VehicleSeat::create(['vehicle_id' => $vehicle->id, 'seat_number' => '1', 'seat_row' => 1, 'seat_column' => 1, 'seat_type' => 'regular', 'is_active' => true]);

        $trip = Trip::create([
            'travel_route_id' => $route->id,
            'vehicle_id' => $vehicle->id,
            'departure_date' => now()->toDateString(),
            'departure_time' => '06:30',
            'status' => 'scheduled',
        ]);

        return compact('trip', 'stopPontianak', 'stopSanggau', 'stopSintang', 'seat');
    }

    public function test_seat_stays_reserved_only_for_overlapping_segments(): void
    {
        ['trip' => $trip, 'stopPontianak' => $stopPontianak, 'stopSanggau' => $stopSanggau, 'stopSintang' => $stopSintang, 'seat' => $seat] = $this->makeTrip();

        $bookingA = Booking::create([
            'trip_id' => $trip->id,
            'origin_stop_id' => $stopPontianak->id,
            'destination_stop_id' => $stopSanggau->id,
            'customer_name' => 'Budi',
            'passenger_count' => 1,
            'total_cost' => 250_000,
            'status' => 'pending',
        ]);
        BookingSeat::create(['booking_id' => $bookingA->id, 'vehicle_seat_id' => $seat->id]);

        // Non-overlapping segment (Sanggau -> Sintang starts where booking A ends): seat is free.
        $availableAfterA = Booking::availableSeatsForSegment($trip, $stopSanggau, $stopSintang);
        $this->assertTrue($availableAfterA->contains('id', $seat->id));

        // Overlapping segment (Pontianak -> Sintang covers booking A's segment): seat is taken.
        $overlapping = Booking::availableSeatsForSegment($trip, $stopPontianak, $stopSintang);
        $this->assertFalse($overlapping->contains('id', $seat->id));

        // Book the same seat for the non-overlapping segment.
        $bookingB = Booking::create([
            'trip_id' => $trip->id,
            'origin_stop_id' => $stopSanggau->id,
            'destination_stop_id' => $stopSintang->id,
            'customer_name' => 'Andi',
            'passenger_count' => 1,
            'total_cost' => 150_000,
            'status' => 'pending',
        ]);
        BookingSeat::create(['booking_id' => $bookingB->id, 'vehicle_seat_id' => $seat->id]);

        $this->assertSame(2, Booking::count());

        // Cancelling booking A releases the seat for its original segment.
        $bookingA->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        $availableAfterCancel = Booking::availableSeatsForSegment($trip, $stopPontianak, $stopSanggau);
        $this->assertTrue($availableAfterCancel->contains('id', $seat->id));
    }

    public function test_confirming_payment_updates_booking_status(): void
    {
        ['trip' => $trip, 'stopPontianak' => $stopPontianak, 'stopSanggau' => $stopSanggau] = $this->makeTrip();

        $booking = Booking::create([
            'trip_id' => $trip->id,
            'origin_stop_id' => $stopPontianak->id,
            'destination_stop_id' => $stopSanggau->id,
            'customer_name' => 'Sari',
            'passenger_count' => 1,
            'total_cost' => 250_000,
            'status' => 'pending',
        ]);

        $this->assertNotNull($booking->booking_code);
        $this->assertStringStartsWith('BKG-', $booking->booking_code);

        $booking->update(['status' => 'confirmed', 'confirmed_at' => now()]);

        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertNotNull($booking->fresh()->confirmed_at);
    }
}
