<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\PackageSetting;
use App\Models\PackageTrackingEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageTracingTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_tracking_event_is_stored_for_a_package(): void
    {
        $setting = PackageSetting::create([
            'name' => 'Tarif Reguler',
            'pricing_type' => 'weight',
            'rate_per_kg' => 30000,
            'minimum_charge' => 30000,
            'is_active' => true,
        ]);

        $package = Package::create([
            'package_setting_id' => $setting->id,
            'customer_name' => 'Budi',
            'weight_kg' => 1,
            'length_cm' => 10,
            'width_cm' => 10,
            'height_cm' => 10,
            'status' => 'pending',
        ]);

        $event = PackageTrackingEvent::create([
            'package_id' => $package->id,
            'status' => 'in_transit',
            'location' => 'Outlet Pontianak Center',
            'description' => 'Paket diberangkatkan menuju tujuan.',
            'occurred_at' => now(),
        ]);

        $this->assertModelExists($event);
        $this->assertTrue($package->trackingEvents()->whereKey($event->id)->exists());
        $this->assertSame('in_transit', $package->fresh()->status);
    }
}
