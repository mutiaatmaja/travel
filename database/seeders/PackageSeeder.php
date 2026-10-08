<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\PackageSetting;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = ['pending', 'pickup', 'in_transit', 'delivered'];
        foreach ([
            ['name' => 'Toko Sumber Rezeki', 'setting' => 'Paket Reguler', 'weight' => 2, 'dimensions' => [20, 15, 10], 'status' => 'pending', 'description' => 'Dokumen usaha, menunggu penjemputan.'],
            ['name' => 'Maria Natalia', 'setting' => 'Paket Reguler', 'weight' => 3.5, 'dimensions' => [30, 20, 15], 'status' => 'pickup', 'description' => 'Pakaian, sudah dijemput kurir.'],
            ['name' => 'CV Borneo Mandiri', 'setting' => 'Paket Volumetrik', 'weight' => 5, 'dimensions' => [60, 40, 30], 'status' => 'in_transit', 'description' => 'Peralatan rumah tangga dalam perjalanan.'],
            ['name' => 'Apotek Sehat', 'setting' => 'Paket Reguler', 'weight' => 1.5, 'dimensions' => [25, 20, 15], 'status' => 'delivered', 'description' => 'Persediaan apotek sudah diterima.'],
            ['name' => 'Rudi Hartono', 'setting' => 'Paket Reguler', 'weight' => 1, 'dimensions' => [20, 20, 10], 'status' => 'cancelled', 'description' => 'Pengiriman dibatalkan oleh pengirim.'],
            ['name' => 'Toko Elektronik Jaya', 'setting' => 'Paket Volumetrik', 'weight' => 4, 'dimensions' => [50, 40, 40], 'status' => 'delivered', 'description' => 'Peralatan elektronik sudah diterima.'],
            ['name' => 'Novi Anggraini', 'setting' => 'Paket Reguler', 'weight' => 0.5, 'dimensions' => [15, 10, 5], 'status' => 'pending', 'description' => 'Dokumen kecil untuk menguji tarif minimum.'],
            ['name' => 'UD Kapuas Makmur', 'setting' => 'Paket Volumetrik', 'weight' => 8, 'dimensions' => [80, 50, 40], 'status' => 'in_transit', 'description' => 'Barang berukuran besar dengan tarif volumetrik.'],
        ] as $index => $data) {
            $package = Package::updateOrCreate(['code' => 'PKG-DEMO-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)], [
                'package_setting_id' => PackageSetting::where('name', $data['setting'])->firstOrFail()->id,
                'customer_name' => $data['name'], 'weight_kg' => $data['weight'],
                'length_cm' => $data['dimensions'][0], 'width_cm' => $data['dimensions'][1], 'height_cm' => $data['dimensions'][2],
                'status' => 'pending', 'description' => $data['description'], 'created_at' => now()->subDays(3),
            ]);
            $eventStatuses = $data['status'] === 'cancelled'
                ? ['pending', 'pickup', 'cancelled']
                : array_slice($statuses, 0, array_search($data['status'], $statuses, true) + 1);
            foreach ($eventStatuses as $eventIndex => $status) {
                $package->trackingEvents()->updateOrCreate(['status' => $status], [
                    'location' => match ($status) {
                        'pending', 'pickup' => 'Outlet Pontianak Center',
                        'in_transit' => 'Outlet Sanggau Center',
                        default => 'Outlet Sintang Center',
                    },
                    'description' => match ($status) {
                        'pending' => 'Paket terdaftar dan menunggu penjemputan.',
                        'pickup' => 'Paket dijemput dan diterima petugas.',
                        'in_transit' => 'Paket diberangkatkan menuju outlet tujuan.',
                        'delivered' => 'Paket diterima penerima dalam kondisi baik.',
                        default => 'Pengiriman dibatalkan atas permintaan pengirim.',
                    },
                    'occurred_at' => now()->subDays(2)->addHours($eventIndex * 6 + $index),
                ]);
            }
            $package->syncTrackingStatus();
        }
    }
}
