<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin', ['title' => 'Booking', 'section' => 'Booking'])] class extends Component {
    public string $title = 'Booking';

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
            <h2 class="mt-2 text-3xl font-extrabold text-slate-900">Daftar Booking</h2>
            <p class="mt-2 text-sm text-slate-500">Pantau dan kelola pemesanan penumpang dalam satu perjalanan.</p>
        </div>
        <button type="button"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-brand-500/20">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12h14" />
            </svg>
            Booking Baru
        </button>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['label' => 'Total Booking', 'value' => '128', 'detail' => '+12 hari ini', 'color' => 'brand'], ['label' => 'Menunggu Pembayaran', 'value' => '18', 'detail' => 'Perlu ditindaklanjuti', 'color' => 'amber'], ['label' => 'Dikonfirmasi', 'value' => '76', 'detail' => 'Kursi sudah terisi', 'color' => 'blue'], ['label' => 'Selesai', 'value' => '34', 'detail' => 'Perjalanan selesai', 'color' => 'green']] as $stat)
            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-500">{{ $stat['label'] }}</p>
                    <span class="h-2.5 w-2.5 rounded-full bg-{{ $stat['color'] }}-500"></span>
                </div>
                <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ $stat['value'] }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $stat['detail'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-100 p-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="font-extrabold text-slate-900">Booking Terbaru</h3>
                <p class="mt-1 text-xs text-slate-500">Data contoh untuk rancangan awal modul booking.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <input placeholder="Cari nomor booking atau penumpang..."
                    class="rounded-xl border px-4 py-2.5 text-sm sm:w-72">
                <select class="rounded-xl border px-4 py-2.5 text-sm">
                    <option>Semua status</option>
                    <option>Menunggu pembayaran</option>
                    <option>Dikonfirmasi</option>
                    <option>Dalam perjalanan</option>
                    <option>Selesai</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-225 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Nomor Booking</th>
                        <th class="px-6 py-4">Penumpang</th>
                        <th class="px-6 py-4">Perjalanan</th>
                        <th class="px-6 py-4">Kursi</th>
                        <th class="px-6 py-4">Jumlah</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ([['code' => 'BKG-20260914-001', 'name' => 'Budi Santoso', 'route' => 'Semitau → Sekadau', 'date' => '14 Sep 2026 · 07:00', 'seat' => 'A1', 'amount' => 'Rp180.000', 'status' => 'Dikonfirmasi', 'class' => 'bg-blue-50 text-blue-700'], ['code' => 'BKG-20260914-002', 'name' => 'Sari Melati', 'route' => 'Sintang → Pontianak', 'date' => '14 Sep 2026 · 08:30', 'seat' => 'A1, A2', 'amount' => 'Rp320.000', 'status' => 'Menunggu pembayaran', 'class' => 'bg-amber-50 text-amber-700'], ['code' => 'BKG-20260914-003', 'name' => 'Andi Wijaya', 'route' => 'Sekadau → Pontianak', 'date' => '14 Sep 2026 · 10:00', 'seat' => 'B2', 'amount' => 'Rp150.000', 'status' => 'Dalam perjalanan', 'class' => 'bg-brand-50 text-brand-700'], ['code' => 'BKG-20260913-018', 'name' => 'Rina Permata', 'route' => 'Semitau → Pontianak', 'date' => '13 Sep 2026 · 06:30', 'seat' => 'C1', 'amount' => 'Rp350.000', 'status' => 'Selesai', 'class' => 'bg-green-50 text-green-700']] as $booking)
                        <tr>
                            <td class="px-6 py-4 font-bold text-slate-900">{{ $booking['code'] }}</td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-800">{{ $booking['name'] }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $booking['date'] }}</p>
                            </td>
                            <td class="px-6 py-4">{{ $booking['route'] }}</td>
                            <td class="px-6 py-4 font-semibold">{{ $booking['seat'] }}</td>
                            <td class="px-6 py-4 font-bold text-brand-700">{{ $booking['amount'] }}</td>
                            <td class="px-6 py-4"><span
                                    class="rounded-full px-2.5 py-1 text-xs font-bold {{ $booking['class'] }}">{{ $booking['status'] }}</span>
                            </td>
                            <td class="px-6 py-4 text-right"><button type="button"
                                    class="text-xs font-bold text-brand-600">Detail</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
