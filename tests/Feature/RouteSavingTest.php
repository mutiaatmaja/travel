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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class RouteSavingTest extends TestCase
{
    use RefreshDatabase;

    private function createRoute(): TravelRoute
    {
        $origin = City::create(['code' => 'PNT', 'name' => 'Pontianak']);
        $destination = City::create(['code' => 'SGG', 'name' => 'Sanggau']);
        $route = TravelRoute::create(['code' => 'PNT-SGG', 'name' => 'Rute Awal', 'origin_city_id' => $origin->id, 'destination_city_id' => $destination->id, 'estimated_duration_minutes' => 180, 'cost' => 250000, 'is_active' => true]);
        foreach ([$origin, $destination] as $index => $city) {
            $outlet = Outlet::create(['city_id' => $city->id, 'code' => 'OUT-'.$city->code, 'name' => 'Outlet '.$city->name, 'address' => $city->name]);
            $route->stops()->create(['outlet_id' => $outlet->id, 'stop_sequence' => $index + 1, 'arrival_offset_minutes' => 15, 'is_boarding_allowed' => false]);
        }

        return $route;
    }

    private function editRoute(TravelRoute $route): Testable
    {
        $user = User::factory()->create();
        $user->givePermission(Permission::create(['name' => 'master-data.manage', 'display_name' => 'Master Data']));
        $this->actingAs($user);

        return Livewire::test('pages::master-data.routes')->call('openEdit', $route->id);
    }

    private function createFare(TravelRoute $route): RouteFare
    {
        $stops = $route->stops()->get();

        return RouteFare::create(['travel_route_id' => $route->id, 'origin_stop_id' => $stops[0]->id, 'destination_stop_id' => $stops[1]->id, 'cost' => 250000, 'is_active' => true]);
    }

    public function test_editing_route_details_preserves_stops_fares_and_bookings(): void
    {
        $route = $this->createRoute();
        $fare = $this->createFare($route);
        $vehicle = Vehicle::create(['code' => 'BUS-01', 'license_plate' => 'KB 1001 AA', 'type' => 'Bus', 'seat_capacity' => 10, 'status' => 'active']);
        $trip = Trip::create(['travel_route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'departure_date' => now()->toDateString(), 'departure_time' => '08:00', 'status' => 'scheduled']);
        $booking = Booking::create(['trip_id' => $trip->id, 'origin_stop_id' => $fare->origin_stop_id, 'destination_stop_id' => $fare->destination_stop_id, 'route_fare_id' => $fare->id, 'customer_name' => 'Budi', 'status' => 'pending']);
        $stopsBefore = $route->stops()->get()->toArray();

        $this->editRoute($route)->set('name', 'Rute Diperbarui')->set('cost', 300000)->call('save')->assertHasNoErrors();

        $this->assertSame('Rute Diperbarui', $route->fresh()->name);
        $this->assertSame($stopsBefore, $route->stops()->get()->toArray());
        $this->assertModelExists($fare);
        $this->assertSame($fare->id, $booking->fresh()->route_fare_id);
    }

    #[TestWith(['reorder'])]
    #[TestWith(['remove'])]
    #[TestWith(['cities'])]
    public function test_route_structure_with_existing_fares_cannot_be_changed(string $change): void
    {
        $route = $this->createRoute();
        $fare = $this->createFare($route);
        if ($change === 'remove') {
            $outlet = Outlet::create(['city_id' => $route->destination_city_id, 'code' => 'OUT-EXTRA', 'name' => 'Outlet Tambahan', 'address' => 'Sanggau']);
            $route->stops()->create(['outlet_id' => $outlet->id, 'stop_sequence' => 3]);
        }
        $stopsBefore = $route->stops()->get()->toArray();
        $component = $this->editRoute($route)->set('name', 'Tidak Boleh Tersimpan');
        $outletIds = $route->stops()->pluck('outlet_id')->all();
        if ($change === 'cities') {
            $component->set('originCityId', $route->destination_city_id)->set('destinationCityId', $route->origin_city_id);
        } else {
            $component->set('selectedStops', $change === 'reorder' ? array_reverse($outletIds) : array_slice($outletIds, 0, 2));
        }

        $component->call('save')->assertHasErrors('selectedStops')->assertSet('modalOpen', true);

        $this->assertSame('Rute Awal', $route->fresh()->name);
        $this->assertSame($stopsBefore, $route->stops()->get()->toArray());
        $this->assertModelExists($fare);
    }

    public function test_route_structure_with_existing_trip_cannot_be_changed(): void
    {
        $route = $this->createRoute();
        $vehicle = Vehicle::create(['code' => 'BUS-01', 'license_plate' => 'KB 1001 AA', 'type' => 'Bus', 'seat_capacity' => 10, 'status' => 'active']);
        $trip = Trip::create(['travel_route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'departure_date' => now()->toDateString(), 'departure_time' => '08:00', 'status' => 'scheduled']);
        $stopsBefore = $route->stops()->get()->toArray();

        $this->editRoute($route)->set('selectedStops', array_reverse($route->stops()->pluck('outlet_id')->all()))->call('save')->assertHasErrors('selectedStops');

        $this->assertSame($stopsBefore, $route->stops()->get()->toArray());
        $this->assertModelExists($trip);
    }

    public function test_unused_route_stop_order_can_be_changed(): void
    {
        $route = $this->createRoute();
        $outletIds = array_reverse($route->stops()->pluck('outlet_id')->all());

        $this->editRoute($route)->set('selectedStops', $outletIds)->call('save')->assertHasNoErrors();

        $this->assertSame($outletIds, $route->stops()->pluck('outlet_id')->all());
        $this->assertSame([1, 2], $route->stops()->pluck('stop_sequence')->all());
    }

    public function test_failed_stop_creation_rolls_back_route_and_stop_changes(): void
    {
        $route = $this->createRoute();
        $stopsBefore = $route->stops()->get()->toArray();
        $component = $this->editRoute($route)->set('name', 'Tidak Boleh Tersimpan')->set('selectedStops', array_reverse($route->stops()->pluck('outlet_id')->all()));
        $dispatcher = RouteStop::getEventDispatcher();
        RouteStop::setEventDispatcher(clone $dispatcher);
        RouteStop::creating(function (RouteStop $stop): void {
            throw new RuntimeException('Simulasi kegagalan penyimpanan stop');
        });

        try {
            try {
                $component->call('save');
                $this->fail('Penyimpanan stop seharusnya gagal.');
            } catch (RuntimeException $exception) {
                $this->assertSame('Simulasi kegagalan penyimpanan stop', $exception->getMessage());
            }
        } finally {
            RouteStop::setEventDispatcher($dispatcher);
        }

        $this->assertSame('Rute Awal', $route->fresh()->name);
        $this->assertSame($stopsBefore, $route->stops()->get()->toArray());
    }
}
