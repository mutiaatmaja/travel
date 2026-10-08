<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
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
                'name' => 'admin_wilayah',
                'display_name' => 'Admin Wilayah',
                'description' => 'Admin operasional yang hanya dapat melaporkan posisi pada kota tugasnya.',
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
        foreach ($peran as $value) {
            Role::updateOrCreate(['name' => $value['name']], $value);
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
        $users = [...$users,
            ['name' => 'Admin Wilayah Sekadau', 'email' => 'wilayah@example.com', 'role' => 'admin_wilayah'],
            ['name' => 'Admin Wilayah Sintang', 'email' => 'wilayah.sintang@example.com', 'role' => 'admin_wilayah'],
            ['name' => 'Supir Budi', 'email' => 'supir2@example.com', 'role' => 'supir'],
            ['name' => 'Supir Andi', 'email' => 'supir3@example.com', 'role' => 'supir'],
        ];
        foreach ($users as $value) {
            $user = User::updateOrCreate(['email' => $value['email']], [
                'name' => $value['name'],
                'email' => $value['email'],
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
            $user->syncRoles([$value['role']]);

        }

        $this->call([
            MasterDataSeeder::class,
            PermissionSeeder::class,
            BookingSeeder::class,
            PackageSettingSeeder::class,
            PackageSeeder::class,
        ]);

        foreach (['admin@example.com' => 'SDK', 'wilayah@example.com' => 'SDK', 'wilayah.sintang@example.com' => 'STG'] as $email => $cityCode) {
            User::where('email', $email)->update(['assigned_city_id' => City::where('code', $cityCode)->value('id')]);
        }
    }
}
