<?php

namespace Database\Seeders;

use App\Models\PackageSetting;
use Illuminate\Database\Seeder;

class PackageSettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Paket Reguler', 'pricing_type' => 'weight', 'rate_per_kg' => 15000, 'minimum_charge' => 30000, 'description' => 'Tarif berdasarkan berat aktual, minimal Rp30.000.', 'is_active' => true],
            ['name' => 'Paket Volumetrik', 'pricing_type' => 'volume', 'rate_per_kg' => 12000, 'volumetric_divisor' => 6000, 'minimum_charge' => 40000, 'description' => 'Berat volumetrik: panjang × lebar × tinggi / 6000.', 'is_active' => true],
            ['name' => 'Tarif Lama', 'pricing_type' => 'weight', 'rate_per_kg' => 10000, 'minimum_charge' => 20000, 'description' => 'Contoh tarif yang sudah dinonaktifkan.', 'is_active' => false],
        ] as $data) {
            PackageSetting::updateOrCreate(['name' => $data['name']], $data);
        }
    }
}
