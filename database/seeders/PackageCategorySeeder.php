<?php

namespace Database\Seeders;

use App\Models\PackageCategory;
use Illuminate\Database\Seeder;

class PackageCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['package' => ['Ongoing', 'Ingoing'], 'financial' => ['Cash', 'Kredit']] as $type => $names) {
            foreach ($names as $name) {
                PackageCategory::firstOrCreate(['type' => $type, 'name' => $name]);
            }
        }
    }
}
