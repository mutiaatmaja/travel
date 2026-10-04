<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Outlet;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RouteStop;
use App\Models\TravelRoute;
use App\Models\Trip;
use App\Models\TripPositionReport;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FleetPositionPermissionTest extends TestCase
{
    use RefreshDatabase;

    private function createRegionalAdmin(City $city): User
    {
        $viewPermission = Permission::create([
            'name' => 'fleet-condition.view-assigned-trips',
            'display_name' => 'Lihat Trip Wilayah Tugas',
        ]);
        $reportPermission = Permission::create([
            'name' => 'fleet-position.update-assigned-city',
            'display_name' => 'Laporkan Posisi Wilayah Tugas',
        ]);
        $role = Role::create([
            'name' => 'admin_wilayah',
            'display_name' => 'Admin Wilayah',
        ]);
        $role->permissions()->attach([$viewPermission->id, $reportPermission->id]);

        $user = User::factory()->create(['assigned_city_id' => $city->id]);
        $user->addRole($role);

        return $user;
    }

    private function createTripAtStop(int $currentStopSequence): array
    {
        $cities = collect([
            ['code' => 'PNT', 'name' => 'Pontianak'],
            ['code' => 'SGG', 'name' => 'Sanggau'],
            ['code' => 'SDK', 'name' => 'Sekadau'],
        ])->map(fn (array $data) => City::create([...$data, 'is_active' => true]));

        $route = TravelRoute::create([
            'code' => 'PNT-SDK',
            'origin_city_id' => $cities[0]->id,
            'destination_city_id' => $cities[2]->id,
            'name' => 'Pontianak - Sekadau',
            'estimated_duration_minutes' => 240,
            'cost' => 300000,
            'is_active' => true,
        ]);

        $stops = $cities->values()->map(function (City $city, int $index) use ($route): RouteStop {
            $outlet = Outlet::create([
                'city_id' => $city->id,
                'code' => 'OUT-'.$city->code,
                'name' => 'Outlet '.$city->name,
                'address' => $city->name,
                'is_active' => true,
            ]);

            return RouteStop::create([
                'travel_route_id' => $route->id,
                'outlet_id' => $outlet->id,
                'stop_sequence' => $index + 1,
            ]);
        });

        $vehicle = Vehicle::create([
            'code' => 'BUS-01',
            'license_plate' => 'KB 1001 AA',
            'type' => 'Bus',
            'seat_capacity' => 20,
            'status' => 'active',
        ]);
        $driver = User::factory()->create();
        $trip = Trip::create([
            'travel_route_id' => $route->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'departure_date' => now()->toDateString(),
            'departure_time' => '08:00',
            'status' => 'on_the_way',
            'current_stop_id' => $stops[$currentStopSequence - 1]->id,
        ]);

        return ['trip' => $trip, 'driver' => $driver, 'sekadau' => $cities[2], 'stops' => $stops];
    }

    public function test_regional_admin_can_report_only_when_next_stop_is_assigned_city(): void
    {
        ['trip' => $trip, 'sekadau' => $sekadau, 'stops' => $stops] = $this->createTripAtStop(2);
        $admin = $this->createRegionalAdmin($sekadau);
        $this->actingAs($admin);

        Livewire::test('pages::booking.fleet-condition')
            ->assertSet('selectedTripId', $trip->id)
            ->call('reportNextStop')
            ->assertHasNoErrors();

        $this->assertSame($stops[2]->id, $trip->fresh()->current_stop_id);
        $this->assertSame($admin->id, $trip->fresh()->position_updated_by);
        $this->assertDatabaseHas('trip_position_reports', [
            'trip_id' => $trip->id,
            'route_stop_id' => $stops[2]->id,
            'reported_by' => $admin->id,
        ]);
    }

    public function test_regional_admin_cannot_report_before_trip_reaches_assigned_city(): void
    {
        ['trip' => $trip, 'sekadau' => $sekadau, 'stops' => $stops] = $this->createTripAtStop(1);
        $this->actingAs($this->createRegionalAdmin($sekadau));

        Livewire::test('pages::booking.fleet-condition')
            ->assertSet('selectedTripId', $trip->id)
            ->call('reportNextStop')
            ->assertHasErrors('selectedTripId');

        $this->assertSame($stops[0]->id, $trip->fresh()->current_stop_id);
        $this->assertSame(0, TripPositionReport::where('trip_id', $trip->id)->count());
    }

    public function test_driver_can_report_only_the_trip_assigned_to_them(): void
    {
        ['trip' => $trip, 'driver' => $driver, 'stops' => $stops] = $this->createTripAtStop(1);
        $viewPermission = Permission::create(['name' => 'fleet-condition.view-own-trips', 'display_name' => 'Lihat Trip Sendiri']);
        $reportPermission = Permission::create(['name' => 'fleet-position.report-own-trip', 'display_name' => 'Laporkan Trip Sendiri']);
        $driverRole = Role::create(['name' => 'supir', 'display_name' => 'Supir']);
        $driverRole->permissions()->attach([$viewPermission->id, $reportPermission->id]);
        $driver->addRole($driverRole);

        $otherDriver = User::factory()->create();
        $otherVehicle = Vehicle::create(['code' => 'BUS-02', 'license_plate' => 'KB 2002 BB', 'type' => 'Bus', 'seat_capacity' => 20, 'status' => 'active']);
        $otherTrip = Trip::create([
            'travel_route_id' => $trip->travel_route_id,
            'vehicle_id' => $otherVehicle->id,
            'driver_id' => $otherDriver->id,
            'departure_date' => now()->toDateString(),
            'departure_time' => '09:00',
            'status' => 'on_the_way',
            'current_stop_id' => $stops[0]->id,
        ]);

        $this->actingAs($driver);

        Livewire::test('pages::booking.fleet-condition')
            ->assertSet('selectedTripId', $trip->id)
            ->assertSee($trip->trip_code)
            ->assertDontSee($otherTrip->trip_code)
            ->call('reportNextStop')
            ->assertHasNoErrors();

        $this->assertSame($stops[1]->id, $trip->fresh()->current_stop_id);
        $this->assertSame($driver->id, $trip->fresh()->position_updated_by);
        $this->assertDatabaseHas('trip_position_reports', [
            'trip_id' => $trip->id,
            'route_stop_id' => $stops[1]->id,
            'reported_by' => $driver->id,
        ]);
    }

    public function test_driver_cannot_open_admin_crud_pages_by_url(): void
    {
        $viewOwnTrips = Permission::create(['name' => 'fleet-condition.view-own-trips', 'display_name' => 'Lihat Trip Sendiri']);
        $reportOwnTrip = Permission::create(['name' => 'fleet-position.report-own-trip', 'display_name' => 'Laporkan Trip Sendiri']);
        $driverRole = Role::create(['name' => 'supir', 'display_name' => 'Supir']);
        $driverRole->permissions()->attach([$viewOwnTrips->id, $reportOwnTrip->id]);

        $this->actingAs(User::factory()->create()->addRole($driverRole));

        $this->get('/users')->assertForbidden();
        $this->get('/trips')->assertForbidden();
        $this->get('/booking')->assertForbidden();
    }

    public function test_driver_sidebar_only_displays_permitted_fleet_menu(): void
    {
        $viewPermission = Permission::create(['name' => 'fleet-condition.view-own-trips', 'display_name' => 'Lihat Trip Sendiri']);
        $reportPermission = Permission::create(['name' => 'fleet-position.report-own-trip', 'display_name' => 'Laporkan Trip Sendiri']);
        $driverRole = Role::create(['name' => 'supir', 'display_name' => 'Supir']);
        $driverRole->permissions()->attach([$viewPermission->id, $reportPermission->id]);
        $this->actingAs(User::factory()->create()->addRole($driverRole));

        $this->get(route('booking.fleet-condition'))
            ->assertOk()
            ->assertSee(route('booking.fleet-condition'))
            ->assertDontSee('href="'.route('dashboard').'"')
            ->assertDontSee('href="'.route('users').'"')
            ->assertDontSee('href="'.route('packages').'"')
            ->assertDontSee('href="'.route('trips').'"');
    }
}
