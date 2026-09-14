<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin', ['title' => 'Perjalanan / Trip', 'section' => 'Booking'])] class extends Component {
    public string $title = 'Perjalanan / Trip';

    public string $section = 'Booking';

    public function logout(): void
    {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        $this->redirectRoute('login', navigate: true);
    }
};
?>

<div>
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-brand-600">Booking</p>
            <h2 class="mt-2 text-3xl font-extrabold text-slate-900">Perjalanan / Trip</h2>
            <p class="mt-2 text-sm text-slate-500">Setiap keberangkatan memiliki kode trip sebagai referensi booking dan
                kondisi armada.</p>
        </div>
        <button type="button"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-brand-500/20">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12h14" />
            </svg>
            Buat Trip
        </button>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['label' => 'Trip hari ini', 'value' => '24', 'detail' => '8 sedang berjalan', 'color' => 'brand'], ['label' => 'Menunggu berangkat', 'value' => '9', 'detail' => 'Siap diberangkatkan', 'color' => 'amber'], ['label' => 'Sedang berjalan', 'value' => '8', 'detail' => 'Dipantau live', 'color' => 'blue'], ['label' => 'Selesai', 'value' => '7', 'detail' => 'Perjalanan selesai', 'color' => 'green']] as $stat)
            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-500">{{ $stat['label'] }}</p><span
                        class="h-2.5 w-2.5 rounded-full bg-{{ $stat['color'] }}-500"></span>
                </div>
                <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ $stat['value'] }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $stat['detail'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-100 p-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="font-extrabold text-slate-900">Daftar Trip</h3>
                <p class="mt-1 text-xs text-slate-500">Data contoh untuk rancangan awal perjalanan armada.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row"><input placeholder="Cari kode trip atau rute..."
                    class="rounded-xl border px-4 py-2.5 text-sm sm:w-64"><select
                    class="rounded-xl border px-4 py-2.5 text-sm">
                    <option>Semua status</option>
                    <option>Menunggu berangkat</option>
                    <option>Berjalan</option>
                    <option>Selesai</option>
                </select></div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-250 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Kode Trip</th>
                        <th class="px-6 py-4">Rute</th>
                        <th class="px-6 py-4">Jadwal</th>
                        <th class="px-6 py-4">Armada</th>
                        <th class="px-6 py-4">Penumpang</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ([['code' => 'TRP-20260914-001', 'route' => 'Pontianak → Semitau', 'schedule' => '14 Sep · 06:30', 'vehicle' => 'B 1234 AB', 'driver' => 'Hendra Wijaya', 'passengers' => '18 / 28', 'status' => 'Berjalan', 'class' => 'bg-brand-50 text-brand-700'], ['code' => 'TRP-20260914-002', 'route' => 'Semitau → Pontianak', 'schedule' => '14 Sep · 07:00', 'vehicle' => 'KB 5678 CD', 'driver' => 'Dedi Irawan', 'passengers' => '24 / 32', 'status' => 'Berjalan', 'class' => 'bg-brand-50 text-brand-700'], ['code' => 'TRP-20260914-003', 'route' => 'Pontianak → Sintang', 'schedule' => '14 Sep · 08:30', 'vehicle' => 'B 9012 EF', 'driver' => 'Maya Putra', 'passengers' => '12 / 20', 'status' => 'Menunggu berangkat', 'class' => 'bg-amber-50 text-amber-700'], ['code' => 'TRP-20260913-018', 'route' => 'Sekadau → Pontianak', 'schedule' => '13 Sep · 10:00', 'vehicle' => 'KB 3456 GH', 'driver' => 'Rudi Hartono', 'passengers' => '28 / 28', 'status' => 'Selesai', 'class' => 'bg-green-50 text-green-700']] as $trip)
                        <tr>
                            <td class="px-6 py-4 font-bold text-slate-900">{{ $trip['code'] }}</td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-800">{{ $trip['route'] }}</p>
                                <p class="mt-1 text-xs text-slate-400">Perjalanan antar titik</p>
                            </td>
                            <td class="px-6 py-4">{{ $trip['schedule'] }}</td>
                            <td class="px-6 py-4">
                                <p class="font-semibold">{{ $trip['vehicle'] }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $trip['driver'] }}</p>
                            </td>
                            <td class="px-6 py-4 font-bold text-brand-700">{{ $trip['passengers'] }} kursi</td>
                            <td class="px-6 py-4"><span
                                    class="rounded-full px-2.5 py-1 text-xs font-bold {{ $trip['class'] }}">{{ $trip['status'] }}</span>
                            </td>
                            <td class="px-6 py-4 text-right"><button type="button"
                                    class="text-xs font-bold text-brand-600">Detail</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-slate-900">Contoh referensi trip</h3>
                    <p class="mt-1 text-xs text-slate-500">Satu trip menjadi induk untuk booking dan monitoring armada.
                    </p>
                </div><span
                    class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">TRP-20260914-001</span>
            </div>
            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs text-slate-400">Rute</p>
                    <p class="mt-1 font-bold text-slate-800">Pontianak → Semitau</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs text-slate-400">Armada</p>
                    <p class="mt-1 font-bold text-slate-800">B 1234 AB</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs text-slate-400">Referensi</p>
                    <p class="mt-1 font-bold text-slate-800">Booking & Armada</p>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-brand-100 bg-brand-50 p-5">
            <p class="text-sm font-extrabold text-brand-800">Alur data</p>
            <div class="mt-4 space-y-3 text-sm font-semibold text-brand-900">
                <p>1. Jadwal dibuat</p>
                <p>2. Trip mendapatkan kode</p>
                <p>3. Booking memilih trip</p>
                <p>4. Armada dipantau</p>
            </div>
        </div>
    </div>
</div>
