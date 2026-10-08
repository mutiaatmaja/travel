<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingSeat;
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

class BookingSeatReservationTest extends TestCase
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
        foreach (['booking.view', 'booking.create'] as $permission) {
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

    public function test_stale_seat_selection_is_rejected_after_another_booking_reserves_it(): void
    {
        ['trip' => $trip, 'stopPontianak' => $origin, 'stopSanggau' => $destination, 'stopSintang' => $lastStop, 'seat' => $seat] = $this->makeTrip();
        $firstForm = $this->bookingForm($trip, $origin, $destination, $seat, 'Petugas Pertama');
        $secondForm = $this->bookingForm($trip, $origin, $lastStop, $seat, 'Petugas Kedua');
        $firstForm->call('save')->assertHasNoErrors();

        $secondForm->call('save')
            ->assertHasErrors('selectedSeatIds')
            ->assertSee('Kursi yang dipilih sudah tidak tersedia pada segmen perjalanan ini. Silakan pilih kursi lain.')
            ->assertSet('modalOpen', true);

        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('booking_seats', 1);
        $this->assertDatabaseMissing('bookings', ['customer_name' => 'Petugas Kedua']);
    }

    public function test_same_seat_can_be_reserved_for_adjacent_segments(): void
    {
        ['trip' => $trip, 'stopPontianak' => $origin, 'stopSanggau' => $middle, 'stopSintang' => $destination, 'seat' => $seat] = $this->makeTrip();
        $firstForm = $this->bookingForm($trip, $origin, $middle, $seat, 'Penumpang Pertama');
        $secondForm = $this->bookingForm($trip, $middle, $destination, $seat, 'Penumpang Kedua');

        $firstForm->call('save')->assertHasNoErrors();
        $secondForm->call('save')->assertHasNoErrors();

        $this->assertDatabaseCount('bookings', 2);
        $this->assertDatabaseCount('booking_seats', 2);
        $this->assertDatabaseHas('bookings', ['customer_name' => 'Penumpang Kedua', 'origin_stop_id' => $middle->id, 'destination_stop_id' => $destination->id]);
    }

    public function test_cancelled_booking_releases_seat_for_a_new_booking(): void
    {
        ['trip' => $trip, 'stopPontianak' => $origin, 'stopSanggau' => $destination, 'seat' => $seat] = $this->makeTrip();
        $booking = Booking::create(['trip_id' => $trip->id, 'origin_stop_id' => $origin->id, 'destination_stop_id' => $destination->id, 'customer_name' => 'Booking Dibatalkan', 'passenger_count' => 1, 'status' => 'cancelled']);
        BookingSeat::create(['booking_id' => $booking->id, 'vehicle_seat_id' => $seat->id]);

        $this->bookingForm($trip, $origin, $destination, $seat, 'Penumpang Baru')->call('save')->assertHasNoErrors();

        $this->assertDatabaseCount('bookings', 2);
        $this->assertDatabaseHas('bookings', ['customer_name' => 'Penumpang Baru', 'status' => 'pending']);
    }

    #[TestWith(['duplicate'])]
    #[TestWith(['inactive'])]
    #[TestWith(['other_vehicle'])]
    public function test_invalid_seat_selection_does_not_create_booking(string $selection): void
    {
        ['trip' => $trip, 'stopPontianak' => $origin, 'stopSanggau' => $destination, 'seat' => $seat] = $this->makeTrip();
        $form = $this->bookingForm($trip, $origin, $destination, $seat, 'Penumpang Baru');
        if ($selection === 'duplicate') {
            $form->set('passengerCount', 2)->set('selectedSeatIds', [$seat->id, $seat->id]);
        } elseif ($selection === 'inactive') {
            $seat->update(['is_active' => false]);
        } else {
            $vehicle = Vehicle::create(['code' => 'B-02', 'license_plate' => 'B 2345 AB', 'type' => 'bus', 'seat_capacity' => 1, 'status' => 'active']);
            $seat->update(['vehicle_id' => $vehicle->id]);
        }

        $form->call('save')->assertHasErrors()->assertSet('modalOpen', true);

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_seats', 0);
    }
}
