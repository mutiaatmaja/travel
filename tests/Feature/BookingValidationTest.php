<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\City;
use App\Models\Outlet;
use App\Models\Permission;
use App\Models\RouteFare;
use App\Models\RouteStop;
use App\Models\TravelRoute;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleSeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BookingValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{trip: Trip, stopPontianak: RouteStop, stopSanggau: RouteStop, stopSintang: RouteStop, seat: VehicleSeat} */
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

    private function bookingForm(Trip $trip, RouteStop $origin, RouteStop $destination, VehicleSeat $seat, string $name): Testable
    {
        RouteFare::firstOrCreate(['travel_route_id' => $trip->travel_route_id, 'origin_stop_id' => $origin->id, 'destination_stop_id' => $destination->id], ['cost' => 250000, 'is_active' => true]);
        $user = User::factory()->create();
        foreach (['booking.view', 'booking.create', 'booking.cancel'] as $permission) {
            $user->givePermission(Permission::firstOrCreate(['name' => $permission], ['display_name' => $permission]));
        }
        $this->actingAs($user);

        return Livewire::test('pages::booking.booking')
            ->call('openCreate')
            ->set('tripId', $trip->id)
            ->set('originStopId', $origin->id)
            ->set('destinationStopId', $destination->id)
            ->set('customerName', $name)
            ->set('selectedSeatIds', [$seat->id]);
    }

    #[TestWith(['draft'])]
    #[TestWith(['on_the_way'])]
    #[TestWith(['completed'])]
    #[TestWith(['cancelled'])]
    public function test_booking_is_rejected_if_trip_stops_accepting_reservations(string $status): void
    {
        ['trip' => $trip, 'stopPontianak' => $origin, 'stopSanggau' => $destination, 'seat' => $seat] = $this->makeTrip();
        $form = $this->bookingForm($trip, $origin, $destination, $seat, 'Penumpang Baru');
        $trip->update(['status' => $status]);

        $form->call('save')->assertHasErrors('tripId')
            ->assertSee('Trip ini sudah tidak menerima pemesanan. Silakan pilih Trip lain.')
            ->assertSet('modalOpen', true);

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_seats', 0);
    }

    #[TestWith(['scheduled'])]
    #[TestWith(['boarding'])]
    public function test_booking_can_be_created_for_trip_accepting_reservations(string $status): void
    {
        ['trip' => $trip, 'stopPontianak' => $origin, 'stopSanggau' => $destination, 'seat' => $seat] = $this->makeTrip();
        $trip->update(['status' => $status]);

        $this->bookingForm($trip, $origin, $destination, $seat, 'Penumpang Baru')->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('bookings', ['trip_id' => $trip->id, 'customer_name' => 'Penumpang Baru', 'total_cost' => 250000]);
        $this->assertDatabaseCount('booking_seats', 1);
    }

    #[TestWith(['missing'])]
    #[TestWith(['inactive'])]
    public function test_booking_without_active_segment_fare_is_rejected(string $fareState): void
    {
        ['trip' => $trip, 'stopPontianak' => $origin, 'stopSanggau' => $destination, 'seat' => $seat] = $this->makeTrip();
        $form = $this->bookingForm($trip, $origin, $destination, $seat, 'Penumpang Baru');
        $fare = RouteFare::firstOrFail();
        if ($fareState === 'missing') {
            $fare->delete();
        } else {
            $fare->update(['is_active' => false]);
        }

        $form->call('save')->assertHasErrors('destinationStopId')
            ->assertSee('Tarif aktif untuk titik naik dan turun ini belum tersedia. Hubungi admin untuk mengatur tarif.');

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_seats', 0);
    }

    #[TestWith(['pending'])]
    #[TestWith(['confirmed'])]
    public function test_active_booking_can_be_cancelled(string $status): void
    {
        ['trip' => $trip, 'stopPontianak' => $origin, 'stopSanggau' => $destination, 'seat' => $seat] = $this->makeTrip();
        $form = $this->bookingForm($trip, $origin, $destination, $seat, 'Penumpang Baru');
        $booking = Booking::create(['trip_id' => $trip->id, 'origin_stop_id' => $origin->id, 'destination_stop_id' => $destination->id, 'customer_name' => 'Budi', 'status' => $status]);

        $form->call('confirmCancel', $booking->id)->assertSet('confirmCancelOpen', true)
            ->call('cancelBooking')->assertHasNoErrors()->assertSet('confirmCancelOpen', false);

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertNotNull($booking->fresh()->cancelled_at);
    }

    #[TestWith(['completed'])]
    #[TestWith(['cancelled'])]
    public function test_inactive_booking_cannot_be_cancelled_by_direct_action(string $status): void
    {
        ['trip' => $trip, 'stopPontianak' => $origin, 'stopSanggau' => $destination, 'seat' => $seat] = $this->makeTrip();
        $form = $this->bookingForm($trip, $origin, $destination, $seat, 'Penumpang Baru');
        $booking = Booking::create(['trip_id' => $trip->id, 'origin_stop_id' => $origin->id, 'destination_stop_id' => $destination->id, 'customer_name' => 'Budi', 'status' => $status]);
        $before = $booking->fresh()->toArray();

        $form->call('confirmCancel', $booking->id)->assertHasErrors('cancelId')->assertSet('confirmCancelOpen', false)
            ->set('cancelId', $booking->id)->call('cancelBooking')->assertHasErrors('cancelId')
            ->assertSee('Hanya booking menunggu pembayaran atau dikonfirmasi yang dapat dibatalkan.');

        $this->assertSame($before, $booking->fresh()->toArray());
    }

    public function test_booking_completed_after_confirmation_dialog_opens_cannot_be_cancelled(): void
    {
        ['trip' => $trip, 'stopPontianak' => $origin, 'stopSanggau' => $destination, 'seat' => $seat] = $this->makeTrip();
        $form = $this->bookingForm($trip, $origin, $destination, $seat, 'Penumpang Baru');
        $booking = Booking::create(['trip_id' => $trip->id, 'origin_stop_id' => $origin->id, 'destination_stop_id' => $destination->id, 'customer_name' => 'Budi', 'status' => 'confirmed']);
        $form->call('confirmCancel', $booking->id);
        $booking->update(['status' => 'completed']);

        $form->call('cancelBooking')->assertHasErrors('cancelId');

        $this->assertSame('completed', $booking->fresh()->status);
        $this->assertNull($booking->fresh()->cancelled_at);
    }
}
