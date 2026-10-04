<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard.view' => ['Lihat dashboard', 'Melihat ringkasan dashboard operasional.'],
            'master-data.manage' => ['Kelola master data', 'Mengelola wilayah, outlet, armada, supir, rute, jadwal, dan tarif.'],
            'packages.manage' => ['Kelola paket', 'Mengelola data dan pengaturan paket.'],
            'booking.view' => ['Lihat booking', 'Melihat daftar dan detail booking.'],
            'booking.create' => ['Buat booking', 'Mendaftarkan penumpang pada Trip.'],
            'booking.confirm-payment' => ['Konfirmasi pembayaran', 'Mengonfirmasi pembayaran booking.'],
            'booking.cancel' => ['Batalkan booking', 'Membatalkan booking sesuai kebijakan.'],
            'booking.settings.manage' => ['Kelola pengaturan booking', 'Mengatur kebijakan booking.'],
            'trip.view' => ['Lihat Trip', 'Melihat jadwal dan perjalanan armada.'],
            'trip.manage' => ['Kelola Trip', 'Membuat dan mengubah jadwal Trip.'],
            'route-fare.manage' => ['Kelola tarif antar titik', 'Mengatur tarif untuk pasangan titik pada rute.'],
            'fleet-condition.view-all' => ['Lihat semua kondisi armada', 'Melihat kondisi seluruh Trip operasional.'],
            'fleet-condition.view-assigned-trips' => ['Lihat Trip wilayah tugas', 'Melihat kondisi Trip yang melewati wilayah tugas.'],
            'fleet-condition.view-own-trips' => ['Lihat Trip yang ditugaskan', 'Melihat kondisi Trip yang ditugaskan kepada Supir.'],
            'fleet-position.update-any' => ['Laporkan posisi Trip', 'Melaporkan stop berikutnya untuk Trip mana pun.'],
            'fleet-position.update-assigned-city' => ['Laporkan posisi wilayah tugas', 'Melaporkan posisi hanya saat Trip tiba di kota tugas user.'],
            'fleet-position.report-own-trip' => ['Laporkan posisi Trip sendiri', 'Supir hanya dapat melaporkan stop berikutnya pada Trip yang ditugaskan.'],
            'fleet-position.view-history' => ['Lihat riwayat posisi', 'Melihat siapa dan kapan posisi Trip dilaporkan.'],
            'users.manage' => ['Kelola pengguna', 'Mengelola akun, peran, dan wilayah tugas user.'],
            'roles-permissions.manage' => ['Kelola peran dan permission', 'Mengelola katalog akses sistem.'],
        ];

        $permissionIds = [];

        foreach ($permissions as $name => [$displayName, $description]) {
            $permission = Permission::updateOrCreate(
                ['name' => $name],
                ['display_name' => $displayName, 'description' => $description],
            );
            $permissionIds[$name] = $permission->id;
        }

        Role::firstOrCreate(
            ['name' => 'admin_wilayah'],
            ['display_name' => 'Admin Wilayah', 'description' => 'Admin operasional yang dibatasi pada kota tugasnya.'],
        );

        $grants = [
            'superadmin' => array_keys($permissions),
            'admin' => [
                'dashboard.view', 'master-data.manage', 'packages.manage',
                'booking.view', 'booking.create', 'booking.confirm-payment', 'booking.cancel', 'booking.settings.manage',
                'trip.view', 'trip.manage', 'route-fare.manage',
                'fleet-condition.view-all', 'fleet-position.update-assigned-city', 'fleet-position.view-history',
            ],
            'admin_wilayah' => [
                'fleet-condition.view-assigned-trips',
                'fleet-position.update-assigned-city', 'fleet-position.view-history',
            ],
            'supir' => ['fleet-condition.view-own-trips', 'fleet-position.report-own-trip'],
            'pemilik' => ['dashboard.view', 'booking.view', 'trip.view', 'fleet-condition.view-all', 'fleet-position.view-history'],
        ];

        foreach ($grants as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->first();

            if ($role) {
                $role->permissions()->sync(array_map(
                    fn (string $name): int => $permissionIds[$name],
                    $permissionNames,
                ));
            }
        }

        $sekadauId = City::where('code', 'SDK')->value('id');

        if ($sekadauId) {
            User::where('email', 'admin@example.com')->whereNull('assigned_city_id')->update(['assigned_city_id' => $sekadauId]);
        }
    }
}
