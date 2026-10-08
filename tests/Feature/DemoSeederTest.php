<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\City;
use App\Models\Outlet;
use App\Models\Package;
use App\Models\PackageSetting;
use App\Models\Role;
use App\Models\RouteFare;
use App\Models\Trip;
use App\Models\TripPositionReport;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleSeat;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use DatabaseMigrations;

    public function test_fresh_seed_creates_complete_demo_data_with_consistent_relations(): void
    {
        $this->freezeTime();

        $this->artisan('migrate:fresh', ['--seed' => true, '--force' => true, '--no-interaction' => true])->assertSuccessful();

        $this->assertDatabaseCount('users', 10);
        $this->assertDatabaseCount('cities', 5);
        $this->assertDatabaseCount('outlets', 5);
        $this->assertDatabaseCount('vehicles', 4);
        $this->assertDatabaseCount('vehicle_seats', 48);
        $this->assertDatabaseCount('travel_routes', 2);
        $this->assertDatabaseCount('route_fares', 20);
        $this->assertDatabaseCount('trips', 7);
        $this->assertDatabaseCount('bookings', 13);
        $this->assertDatabaseCount('packages', 8);
        $this->assertDatabaseCount('package_settings', 3);
        $this->assertDatabaseCount('trip_position_reports', 8);
        $this->assertSame(['cancelled', 'completed', 'confirmed', 'pending'], Booking::distinct()->orderBy('status')->pluck('status')->all());
        $this->assertSame(['cancelled', 'delivered', 'in_transit', 'pending', 'pickup'], Package::distinct()->orderBy('status')->pluck('status')->all());

        foreach (Booking::with(['trip.vehicle', 'seats.vehicleSeat', 'routeFare'])->get() as $booking) {
            $this->assertSame($booking->passenger_count, $booking->seats->count());
            $this->assertSame($booking->routeFare->cost * $booking->passenger_count, $booking->total_cost);
            foreach ($booking->seats as $seat) {
                $this->assertSame($booking->trip->vehicle_id, $seat->vehicleSeat->vehicle_id);
            }
        }
        foreach (Package::all() as $package) {
            $this->assertSame($package->trackingEvents()->value('status'), $package->status);
            $this->assertGreaterThan(0, $package->calculateTotalCost());
        }
        foreach (Trip::whereNotNull('current_stop_id')->get() as $trip) {
            $this->assertNotNull($trip->trip_code);
            $this->assertSame($trip->current_stop_id, $trip->positionReports()->first()->route_stop_id);
        }
    }

    public function test_demo_accounts_have_permissions_and_regional_assignment_for_testing(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['superadmin@example.com' => 'dashboard', 'admin@example.com' => 'dashboard', 'pemilik@example.com' => 'dashboard', 'supir@example.com' => 'booking.fleet-condition', 'wilayah@example.com' => 'booking.fleet-condition'] as $email => $routeName) {
            $user = User::where('email', $email)->firstOrFail();
            $this->assertTrue(Hash::check('password', $user->password));
            $this->assertSame($routeName, $user->landingRouteName());
        }
        $regionalAdmin = User::where('email', 'wilayah@example.com')->firstOrFail();
        $this->assertSame('SDK', $regionalAdmin->assignedCity->code);
        $this->assertTrue($regionalAdmin->hasPermission('fleet-position.update-assigned-city'));
        $this->assertFalse($regionalAdmin->hasPermission('master-data.manage'));
        $ongoingTrip = Trip::where('status', 'on_the_way')->firstOrFail();
        $nextStop = $ongoingTrip->travelRoute->stops()->where('stop_sequence', '>', $ongoingTrip->currentStop->stop_sequence)->firstOrFail();
        $this->assertSame($regionalAdmin->assigned_city_id, $nextStop->outlet->city_id);

        $this->actingAs(User::where('email', 'superadmin@example.com')->firstOrFail());
        foreach (['dashboard', 'booking', 'booking.status', 'booking.trips', 'booking.fleet-condition', 'packages', 'packages.tracing', 'routes', 'route-fares'] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }

    public function test_demo_seat_reuse_is_valid_and_active_bookings_do_not_overlap(): void
    {
        $this->seed(DatabaseSeeder::class);
        $first = Booking::where('booking_code', 'BKG-DEMO-001')->firstOrFail();
        $second = Booking::where('booking_code', 'BKG-DEMO-002')->firstOrFail();

        $this->assertSame($first->destination_stop_id, $second->origin_stop_id);
        $this->assertSame($first->seats()->first()->vehicle_seat_id, $second->seats()->first()->vehicle_seat_id);
        $activeBookings = Booking::whereIn('status', Booking::ACTIVE_STATUSES)->with(['originStop', 'destinationStop', 'seats'])->get();
        foreach ($activeBookings as $booking) {
            foreach ($activeBookings->where('trip_id', $booking->trip_id)->where('id', '>', $booking->id) as $otherBooking) {
                $overlaps = $booking->originStop->stop_sequence < $otherBooking->destinationStop->stop_sequence
                    && $otherBooking->originStop->stop_sequence < $booking->destinationStop->stop_sequence;
                if ($overlaps) {
                    $this->assertSame([], array_values(array_intersect($booking->seats->pluck('vehicle_seat_id')->all(), $otherBooking->seats->pluck('vehicle_seat_id')->all())));
                }
            }
        }
    }

    public function test_rerunning_demo_seed_on_same_day_does_not_duplicate_records(): void
    {
        $this->freezeTime();
        $this->seed(DatabaseSeeder::class);
        $counts = [];
        foreach ([User::class, Role::class, City::class, Outlet::class, Vehicle::class, VehicleSeat::class, Trip::class, RouteFare::class, Booking::class, PackageSetting::class, Package::class, TripPositionReport::class] as $model) {
            $counts[$model] = $model::count();
        }

        $this->seed(DatabaseSeeder::class);

        foreach ($counts as $model => $count) {
            $this->assertSame($count, $model::count());
        }
    }
}
