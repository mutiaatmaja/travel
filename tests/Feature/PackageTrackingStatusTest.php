<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\PackageSetting;
use App\Models\PackageTrackingEvent;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class PackageTrackingStatusTest extends TestCase
{
    use RefreshDatabase;

    private function createPackage(): Package
    {
        $setting = PackageSetting::create(['name' => 'Tarif Reguler', 'pricing_type' => 'weight', 'rate_per_kg' => 30000, 'minimum_charge' => 30000, 'is_active' => true]);

        return Package::create(['package_setting_id' => $setting->id, 'customer_name' => 'Budi', 'weight_kg' => 1, 'status' => 'pending']);
    }

    private function tracingForm(Package $package): Testable
    {
        $user = User::factory()->create();
        $user->givePermission(Permission::create(['name' => 'packages.manage', 'display_name' => 'Kelola Paket']));
        $this->actingAs($user);

        return Livewire::test('pages::packages.tracing')->call('selectPackage', $package->id);
    }

    private function createEvent(Package $package, string $status, string $occurredAt): PackageTrackingEvent
    {
        return $package->trackingEvents()->create(['status' => $status, 'location' => 'Outlet Pontianak', 'occurred_at' => $occurredAt]);
    }

    public function test_adding_older_event_does_not_replace_latest_package_status(): void
    {
        $package = $this->createPackage();
        $this->createEvent($package, 'delivered', '2026-10-08 12:00:00');

        $this->tracingForm($package)->set('status', 'pickup')->set('location', 'Outlet Asal')
            ->set('occurredAt', '2026-10-08T08:00')->call('addEvent')->assertHasNoErrors()->assertSet('status', 'delivered');

        $this->assertSame('delivered', $package->fresh()->status);
        $this->assertDatabaseCount('package_tracking_events', 2);
    }

    public function test_adding_newer_event_updates_package_status(): void
    {
        $package = $this->createPackage();
        $this->createEvent($package, 'pickup', '2026-10-08 08:00:00');

        $this->tracingForm($package)->set('status', 'in_transit')->set('location', 'Outlet Tujuan')
            ->set('occurredAt', '2026-10-08T10:00')->call('addEvent')->assertHasNoErrors()->assertSet('status', 'in_transit');

        $this->assertSame('in_transit', $package->fresh()->status);
        $this->assertDatabaseCount('package_tracking_events', 2);
    }

    public function test_deleting_latest_event_restores_previous_package_status(): void
    {
        $package = $this->createPackage();
        $previous = $this->createEvent($package, 'pickup', '2026-10-08 08:00:00');
        $latest = $this->createEvent($package, 'delivered', '2026-10-08 12:00:00');

        $this->tracingForm($package)->call('deleteEvent', $latest->id)->assertHasNoErrors()->assertSet('status', 'pickup');

        $this->assertSame('pickup', $package->fresh()->status);
        $this->assertModelExists($previous);
        $this->assertModelMissing($latest);
    }

    public function test_deleting_older_event_preserves_latest_package_status(): void
    {
        $package = $this->createPackage();
        $previous = $this->createEvent($package, 'pickup', '2026-10-08 08:00:00');
        $latest = $this->createEvent($package, 'delivered', '2026-10-08 12:00:00');

        $this->tracingForm($package)->call('deleteEvent', $previous->id)->assertHasNoErrors()->assertSet('status', 'delivered');

        $this->assertSame('delivered', $package->fresh()->status);
        $this->assertModelExists($latest);
    }

    public function test_deleting_last_event_resets_package_to_pending(): void
    {
        $package = $this->createPackage();
        $event = $this->createEvent($package, 'delivered', '2026-10-08 12:00:00');

        $this->tracingForm($package)->call('deleteEvent', $event->id)->assertHasNoErrors()->assertSet('status', 'pending');

        $this->assertSame('pending', $package->fresh()->status);
        $this->assertDatabaseCount('package_tracking_events', 0);
    }

    public function test_latest_inserted_event_wins_when_occurrence_times_match(): void
    {
        $package = $this->createPackage();
        $first = $this->createEvent($package, 'pickup', '2026-10-08 08:00:00');
        $latest = $this->createEvent($package, 'in_transit', '2026-10-08 08:00:00');

        $this->assertSame('in_transit', $package->fresh()->status);
        $this->assertSame([$latest->id, $first->id], $package->trackingEvents()->pluck('id')->all());

        $latest->delete();

        $this->assertSame('pickup', $package->fresh()->status);
    }

    public function test_changing_event_time_recalculates_status_from_latest_event(): void
    {
        $package = $this->createPackage();
        $older = $this->createEvent($package, 'pickup', '2026-10-08 08:00:00');
        $this->createEvent($package, 'delivered', '2026-10-08 12:00:00');

        $older->update(['occurred_at' => '2026-10-08 13:00:00']);

        $this->assertSame('pickup', $package->fresh()->status);
    }

    public function test_event_of_another_package_cannot_be_deleted(): void
    {
        $package = $this->createPackage();
        $otherPackage = Package::create(['package_setting_id' => $package->package_setting_id, 'customer_name' => 'Andi', 'status' => 'pending']);
        $event = $this->createEvent($otherPackage, 'delivered', '2026-10-08 12:00:00');

        $this->tracingForm($package)->call('deleteEvent', $event->id)->assertStatus(404);

        $this->assertModelExists($event);
        $this->assertSame('pending', $package->fresh()->status);
        $this->assertSame('delivered', $otherPackage->fresh()->status);
    }

    public function test_failed_status_update_rolls_back_new_tracking_event(): void
    {
        $package = $this->createPackage();
        $form = $this->tracingForm($package)->set('status', 'pickup')->set('location', 'Outlet Asal')->set('occurredAt', '2026-10-08T08:00');
        $dispatcher = Package::getEventDispatcher();
        Package::setEventDispatcher(clone $dispatcher);
        Package::updating(function (Package $package): void {
            throw new RuntimeException('Simulasi gagal memperbarui status paket');
        });

        try {
            try {
                $form->call('addEvent');
                $this->fail('Pembaruan status seharusnya gagal.');
            } catch (RuntimeException $exception) {
                $this->assertSame('Simulasi gagal memperbarui status paket', $exception->getMessage());
            }
        } finally {
            Package::setEventDispatcher($dispatcher);
        }

        $this->assertDatabaseCount('package_tracking_events', 0);
        $this->assertSame('pending', $package->fresh()->status);
    }
}
