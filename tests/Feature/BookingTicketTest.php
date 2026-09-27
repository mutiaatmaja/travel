<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\City;
use App\Models\Outlet;
use App\Models\RouteStop;
use App\Models\TravelRoute;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTicketTest extends TestCase
{
    use RefreshDatabase;

    private function createBooking(string $status): Booking
    {
        $originCity = City::create(['code' => 'PNT', 'name' => 'Pontianak', 'is_active' => true]);
        $destinationCity = City::create(['code' => 'SGG', 'name' => 'Sanggau', 'is_active' => true]);
        $route = TravelRoute::create([
            'code' => 'PNT-SGG',
            'origin_city_id' => $originCity->id,
            'destination_city_id' => $destinationCity->id,
            'name' => 'Pontianak - Sanggau',
            'estimated_duration_minutes' => 180,
            'cost' => 250000,
            'is_active' => true,
        ]);
        $originOutlet = Outlet::create(['city_id' => $originCity->id, 'code' => 'OUT-PNT', 'name' => 'Outlet Pontianak', 'address' => 'Pontianak', 'is_active' => true]);
        $destinationOutlet = Outlet::create(['city_id' => $destinationCity->id, 'code' => 'OUT-SGG', 'name' => 'Outlet Sanggau', 'address' => 'Sanggau', 'is_active' => true]);
        $originStop = RouteStop::create(['travel_route_id' => $route->id, 'outlet_id' => $originOutlet->id, 'stop_sequence' => 1]);
        $destinationStop = RouteStop::create(['travel_route_id' => $route->id, 'outlet_id' => $destinationOutlet->id, 'stop_sequence' => 2]);
        $vehicle = Vehicle::create(['code' => 'BUS-01', 'license_plate' => 'KB 1001 AA', 'type' => 'Bus', 'seat_capacity' => 10, 'status' => 'active']);
        $trip = Trip::create(['travel_route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'departure_date' => now()->toDateString(), 'departure_time' => '08:00', 'status' => 'scheduled']);

        return Booking::create([
            'trip_id' => $trip->id,
            'origin_stop_id' => $originStop->id,
            'destination_stop_id' => $destinationStop->id,
            'customer_name' => 'Budi Santoso',
            'passenger_count' => 1,
            'total_cost' => 250000,
            'status' => $status,
            'confirmed_at' => $status === 'confirmed' ? now() : null,
        ]);
    }

    public function test_confirmed_booking_can_be_rendered_as_inline_pdf(): void
    {
        $this->actingAs(User::factory()->create());
        $booking = $this->createBooking('confirmed');

        $response = $this->get(route('booking.ticket', $booking));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_pending_booking_cannot_be_printed_as_a_ticket(): void
    {
        $this->actingAs(User::factory()->create());
        $booking = $this->createBooking('pending');

        $this->get(route('booking.ticket', $booking))->assertNotFound();
    }
}
