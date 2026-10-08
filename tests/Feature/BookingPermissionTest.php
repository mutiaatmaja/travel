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
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BookingPermissionTest extends TestCase
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

    private function createUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $name) {
            $user->givePermission(Permission::firstOrCreate(['name' => $name], ['display_name' => $name]));
        }

        return $user;
    }

    #[TestWith(['openCreate'])]
    #[TestWith(['save'])]
    #[TestWith(['confirmPayment'])]
    #[TestWith(['confirmCancel'])]
    #[TestWith(['cancelBooking'])]
    public function test_view_only_user_cannot_perform_booking_actions(string $action): void
    {
        $booking = $this->createBooking('pending');
        $this->actingAs($this->createUserWithPermissions(['booking.view']));

        Livewire::test('pages::booking.booking')
            ->set('cancelId', $booking->id)
            ->call($action, $booking->id)
            ->assertStatus(403);

        $this->assertDatabaseCount('bookings', 1);
        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_view_only_user_does_not_see_booking_action_buttons(): void
    {
        $this->createBooking('pending');
        $this->actingAs($this->createUserWithPermissions(['booking.view']));

        Livewire::test('pages::booking.booking')
            ->assertDontSee('Booking Baru')
            ->assertDontSee('Konfirmasi Bayar')
            ->assertDontSee('wire:click="confirmCancel(', false);
    }

    public function test_user_with_create_permission_can_create_booking(): void
    {
        $booking = $this->createBooking('pending');
        $seat = VehicleSeat::create(['vehicle_id' => $booking->trip->vehicle_id, 'seat_number' => '1', 'seat_row' => 1, 'seat_column' => 1, 'seat_type' => 'regular', 'is_active' => true]);
        RouteFare::create(['travel_route_id' => $booking->trip->travel_route_id, 'origin_stop_id' => $booking->origin_stop_id, 'destination_stop_id' => $booking->destination_stop_id, 'cost' => 250000, 'is_active' => true]);
        $this->actingAs($this->createUserWithPermissions(['booking.view', 'booking.create']));

        Livewire::test('pages::booking.booking')
            ->assertSee('Booking Baru')
            ->call('openCreate')
            ->assertSet('modalOpen', true)
            ->set('tripId', $booking->trip_id)
            ->set('originStopId', $booking->origin_stop_id)
            ->set('destinationStopId', $booking->destination_stop_id)
            ->set('customerName', 'Penumpang Baru')
            ->set('selectedSeatIds', [$seat->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('bookings', ['customer_name' => 'Penumpang Baru', 'total_cost' => 250000]);
        $this->assertDatabaseCount('booking_seats', 1);
    }

    public function test_user_with_payment_permission_can_confirm_payment(): void
    {
        $booking = $this->createBooking('pending');
        $user = $this->createUserWithPermissions(['booking.view', 'booking.confirm-payment']);
        $this->actingAs($user);

        Livewire::test('pages::booking.booking')
            ->assertSee('Konfirmasi Bayar')
            ->call('confirmPayment', $booking->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed', 'confirmed_by' => $user->id]);
    }

    public function test_user_with_cancel_permission_can_cancel_booking(): void
    {
        $booking = $this->createBooking('pending');
        $this->actingAs($this->createUserWithPermissions(['booking.view', 'booking.cancel']));

        Livewire::test('pages::booking.booking')
            ->assertSee('wire:click="confirmCancel(', false)
            ->call('confirmCancel', $booking->id)
            ->assertSet('confirmCancelOpen', true)
            ->call('cancelBooking')
            ->assertHasNoErrors();

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertNotNull($booking->fresh()->cancelled_at);
    }
}
