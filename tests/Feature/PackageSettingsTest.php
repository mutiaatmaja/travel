<?php

namespace Tests\Feature;

use App\Models\PackageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_setting_can_store_weight_and_volume_rates(): void
    {
        PackageSetting::create([
            'name' => 'Tarif Reguler',
            'pricing_type' => 'weight',
            'rate_per_kg' => 12000,
            'rate_per_m3' => null,
            'minimum_charge' => 30000,
            'description' => 'Tarif pengiriman reguler berdasarkan berat',
            'is_active' => true,
        ]);

        PackageSetting::create([
            'name' => 'Volume Express',
            'pricing_type' => 'volume',
            'rate_per_kg' => null,
            'rate_per_m3' => 450000,
            'volumetric_divisor' => 6000,
            'minimum_charge' => 80000,
            'description' => 'Tarif volume untuk pengiriman ekspres',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('package_settings', ['name' => 'Tarif Reguler', 'pricing_type' => 'weight', 'rate_per_kg' => 12000]);
        $this->assertDatabaseHas('package_settings', ['name' => 'Volume Express', 'pricing_type' => 'volume', 'volumetric_divisor' => 6000]);
        $this->assertSame(2, PackageSetting::count());
    }
}
