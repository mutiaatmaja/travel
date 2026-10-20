<?php

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'package_setting_id' => PackageSettingFactory::new(),
            'customer_name' => fake()->name(),
            'weight_kg' => 2,
            'length_cm' => 30,
            'width_cm' => 20,
            'height_cm' => 10,
            'status' => 'pending',
            'category' => 'Ongoing',
            'financial_category' => 'Cash',
        ];
    }
}
