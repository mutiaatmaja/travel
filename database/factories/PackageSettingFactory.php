<?php

namespace Database\Factories;

use App\Models\PackageSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PackageSetting>
 */
class PackageSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'pricing_type' => 'weight',
            'rate_per_kg' => 10000,
            'minimum_charge' => 0,
            'volumetric_divisor' => 6000,
            'is_active' => true,
        ];
    }
}
