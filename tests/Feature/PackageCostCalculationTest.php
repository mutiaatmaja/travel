<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\PackageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageCostCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_cost_is_calculated_from_weight_and_volume_settings(): void
    {
        $weightSetting = PackageSetting::create([
            'name' => 'Tarif Berat Reguler',
            'pricing_type' => 'weight',
            'rate_per_kg' => 12000,
            'minimum_charge' => 30000,
            'is_active' => true,
        ]);

        $packageByWeight = new Package([
            'package_setting_id' => $weightSetting->id,
            'code' => 'PKG-001',
            'customer_name' => 'Budi',
            'weight_kg' => 2.5,
            'length_cm' => 20,
            'width_cm' => 20,
            'height_cm' => 20,
            'status' => 'pending',
            'description' => 'Dokumen ringan',
        ]);

        $this->assertSame(30000, $packageByWeight->calculateTotalCost());

        $volumeSetting = PackageSetting::create([
            'name' => 'Tarif Volume Reguler',
            'pricing_type' => 'volume',
            'rate_per_m3' => 450000,
            'minimum_charge' => 80000,
            'is_active' => true,
        ]);

        $packageByVolume = new Package([
            'package_setting_id' => $volumeSetting->id,
            'code' => 'PKG-002',
            'customer_name' => 'Sari',
            'weight_kg' => 5,
            'length_cm' => 40,
            'width_cm' => 30,
            'height_cm' => 20,
            'status' => 'pending',
            'description' => 'Barang volume besar',
        ]);

        $this->assertSame(80000, $packageByVolume->calculateTotalCost());
    }
}
