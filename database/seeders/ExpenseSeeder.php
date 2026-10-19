<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ExpenseCategorySeeder::class);

        $permission = Permission::firstOrCreate(['name' => 'expenses.manage'], [
            'display_name' => 'Kelola pengeluaran',
            'description' => 'Mengelola kategori dan pengeluaran Travel serta Paket.',
        ]);

        foreach (Role::whereIn('name', ['superadmin', 'admin'])->get() as $role) {
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }
}
