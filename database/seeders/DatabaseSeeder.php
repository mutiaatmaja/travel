<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // membuat Peran
        $peran = [
            [
                'name' => 'superadmin',
                'display_name' => 'Super Admin',
                'description' => 'Tingkatan Admin tertinggi yang memiliki akses penuh ke semua fitur dan pengaturan sistem.',
            ],
            [
                'name' => 'admin',
                'display_name' => 'Admin',
                'description' => 'Tingkatan Admin yang memiliki akses terbatas ke fitur dan pengaturan sistem, biasanya untuk mengelola konten dan pengguna.',
            ],
            [
                'name' => 'supir',
                'display_name' => 'Supir',
                'description' => 'Ayo pak supir.',
            ],
            [
                'name' => 'kurir',
                'display_name' => 'Kurir',
                'description' => 'Ayo pak kurir.',
            ],
            [
                'name' => 'penumpang',
                'display_name' => 'Penumpang',
                'description' => 'Ayo pak penumpang.',
            ],
            [
                'name' => 'pemilik',
                'display_name' => 'Pemilik',
                'description' => 'Akun boss.',
            ],
        ];
        foreach ($peran as $key => $value) {
            Role::create($value);
        }
        // membuat user
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'role' => 'superadmin',
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'role' => 'admin',
            ],
            [
                'name' => 'Supir',
                'email' => 'supir@example.com',
                'role' => 'supir',
            ],
            [
                'name' => 'Kurir',
                'email' => 'kurir@example.com',
                'role' => 'kurir',
            ],
            [
                'name' => 'Penumpang',
                'email' => 'penumpang@example.com',
                'role' => 'penumpang',
            ],
            [
                'name' => 'Pemilik',
                'email' => 'pemilik@example.com',
                'role' => 'pemilik',
            ],
        ];
        foreach ($users as $value) {
            $user = User::create([
                'name' => $value['name'],
                'email' => $value['email'],
                'password' => bcrypt('password'),
            ])->addRole($value['role']);

        }

        $this->call(MasterDataSeeder::class);
    }
}
