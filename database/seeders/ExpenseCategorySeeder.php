<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['travel' => ['BBM', 'Operasional', 'Perawatan', 'Lainnya'], 'package' => ['Pengemasan', 'Transportasi', 'Operasional', 'Lainnya']] as $service => $names) {
            foreach ($names as $name) {
                ExpenseCategory::firstOrCreate(['service' => $service, 'name' => $name]);
            }
        }
    }
}
