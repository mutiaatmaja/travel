<?php

namespace Database\Factories;

use App\Models\PackageCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PackageCategory>
 */
class PackageCategoryFactory extends Factory
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
            'type' => 'package',
        ];
    }
}
