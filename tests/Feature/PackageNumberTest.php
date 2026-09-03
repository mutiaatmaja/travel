<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\PackageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_number_is_generated_automatically(): void
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

        $this->assertMatchesRegularExpression('/^PKG-'.now()->format('Ymd').'-[A-Z0-9]{6}$/', $package->code);
        $this->assertNotSame('', $package->code);
    }
}
