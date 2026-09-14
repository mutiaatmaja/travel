<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin', ['title' => 'Kondisi Armada', 'section' => 'Booking'])] class extends Component {
    public string $title = 'Kondisi Armada';

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
            <p class="text-xs font-bold uppercase tracking-widest text-brand-600">Operasional Booking</p>
            <h2 class="mt-2 text-3xl font-extrabold text-slate-900">Kondisi Armada</h2>
            <p class="mt-2 text-sm text-slate-500">Pantau posisi armada, kapasitas penumpang, dan pergerakan naik-turun
                di setiap titik rute.</p>
        </div>
        <div
            class="flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-bold text-green-700">
            <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-green-500"></span>
            Data real-time · 14 Sep 2026, 10:35
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['label' => 'Armada aktif', 'value' => '12', 'detail' => '3 sedang berjalan', 'icon' => 'bus'], ['label' => 'Sedang berjalan', 'value' => '3', 'detail' => 'Menuju titik berikutnya', 'icon' => 'route'], ['label' => 'Total penumpang', 'value' => '86', 'detail' => 'Dari 132 kursi aktif', 'icon' => 'users'], ['label' => 'Kursi tersedia', 'value' => '46', 'detail' => 'Bisa dipesan di segmen berikut', 'icon' => 'seat']] as $stat)
            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                        @if ($stat['icon'] === 'bus')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M5 17h14M6 17v3m12-3v3M5 17a2 2 0 0 1-2-2V7a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v8a2 2 0 0 1-2 2M6 8h12M7 12h.01M17 12h.01" />
                            </svg>
                        @elseif ($stat['icon'] === 'route')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <circle cx="6" cy="18" r="2" />
                                <circle cx="18" cy="6" r="2" />
                                <path d="M8 18h2a4 4 0 0 0 4-4v-4a4 4 0 0 1 4-4" />
                            </svg>
                        @elseif ($stat['icon'] === 'users')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <circle cx="9" cy="8" r="3" />
                                <path d="M3 20a6 6 0 0 1 12 0M16 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 6" />
                            </svg>
                        @else
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M5 20V9a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v11M3 20h18M8 11h8M8 15h8" />
                            </svg>
                        @endif
                    </span>
                    <span class="text-xs font-bold text-green-600">Live</span>
                </div>
                <p class="mt-5 text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $stat['value'] }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $stat['detail'] }}</p>
            </div>
        @endforeach
    </div>

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <h3 class="text-xl font-extrabold text-slate-900">TRP-20260914-003</h3>
                    <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-700">Dalam
                        perjalanan</span>
                </div>
                <p class="mt-2 text-sm text-slate-500">Armada B 1234 AB · Supir: Hendra Wijaya · Berangkat 06:30</p>
            </div>
            <div class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3">
                <div class="text-right">
                    <p class="text-xs text-slate-400">Posisi saat ini</p>
                    <p class="font-extrabold text-slate-900">Sanggau</p>
                </div>
                <svg class="h-6 w-6 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2">
                    <path d="M5 12h14M13 6l6 6-6 6" />
                </svg>
                <div>
                    <p class="text-xs text-slate-400">Tujuan berikutnya</p>
                    <p class="font-extrabold text-slate-900">Sekadau</p>
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[1.5fr_0.8fr]">
            <div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-extrabold text-slate-900">Rute perjalanan</p>
                        <p class="mt-1 text-xs text-slate-500">Pontianak → Sanggau → Sekadau → Semitau</p>
                    </div>
                    <span class="text-sm font-extrabold text-brand-700">18 / 28 kursi terisi</span>
                </div>
                <div class="relative mt-10 px-2">
                    <div class="absolute left-8 right-8 top-3 h-1 rounded-full bg-slate-200 sm:left-12 sm:right-12">
                    </div>
                    <div class="absolute left-8 top-3 h-1 w-[42%] rounded-full bg-brand-500 sm:left-12"></div>
                    <div class="relative grid grid-cols-4 gap-2">
                        @foreach ([['city' => 'Pontianak', 'time' => '06:30', 'state' => 'done', 'up' => 12, 'down' => 0, 'onboard' => 28], ['city' => 'Sanggau', 'time' => '09:10', 'state' => 'current', 'up' => 3, 'down' => 5, 'onboard' => 26], ['city' => 'Sekadau', 'time' => '11:20', 'state' => 'next', 'up' => 4, 'down' => 6, 'onboard' => 24], ['city' => 'Semitau', 'time' => '14:30', 'state' => 'finish', 'up' => 0, 'down' => 24, 'onboard' => 0]] as $stop)
                            <div class="text-center">
                                <span
                                    class="mx-auto flex h-7 w-7 items-center justify-center rounded-full border-4 border-white {{ $stop['state'] === 'current' ? 'bg-brand-500 ring-4 ring-brand-100' : ($stop['state'] === 'done' ? 'bg-brand-500' : 'bg-slate-300') }}"></span>
                                <p class="mt-3 text-xs font-extrabold text-slate-800 sm:text-sm">{{ $stop['city'] }}</p>
                                <p class="mt-1 text-[10px] text-slate-400 sm:text-xs">{{ $stop['time'] }}</p>
                                <div class="mt-3 space-y-1 text-[10px] sm:text-xs">
                                    <p class="font-semibold text-green-600">+{{ $stop['up'] }} naik</p>
                                    <p class="font-semibold text-red-500">-{{ $stop['down'] }} turun</p>
                                    <p class="mt-2 font-extrabold text-slate-700">{{ $stop['onboard'] }} di armada</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-5">
                <p class="text-sm font-extrabold text-slate-900">Kondisi saat ini</p>
                <div class="mt-4 flex items-end justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Penumpang di armada</p>
                        <p class="mt-1 text-3xl font-extrabold text-slate-900">18 <span
                                class="text-base font-semibold text-slate-400">/ 28</span></p>
                    </div><span class="text-sm font-bold text-brand-700">64%</span>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                    <div class="h-full w-[64%] rounded-full bg-brand-500"></div>
                </div>
                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-green-50 p-3">
                        <p class="text-xs text-green-700">Naik di Sanggau</p>
                        <p class="mt-1 text-xl font-extrabold text-green-800">3</p>
                    </div>
                    <div class="rounded-xl bg-red-50 p-3">
                        <p class="text-xs text-red-700">Turun di Sanggau</p>
                        <p class="mt-1 text-xl font-extrabold text-red-800">5</p>
                    </div>
                </div>
                <p class="mt-5 text-xs leading-5 text-slate-500">Setelah berhenti di Sanggau, tersedia 10 kursi untuk
                    penumpang baru pada segmen menuju Sekadau.</p>
            </div>
        </div>
    </section>

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 p-5">
            <div>
                <h3 class="font-extrabold text-slate-900">Armada sedang berjalan</h3>
                <p class="mt-1 text-xs text-slate-500">Ringkasan posisi armada lain hari ini.</p>
            </div><button type="button" class="text-sm font-bold text-brand-600">Lihat semua</button>
        </div>
        <div class="grid grid-cols-1 divide-y divide-slate-100 md:grid-cols-3 md:divide-x md:divide-y-0">
            @foreach ([['code' => 'TRP-003', 'route' => 'Pontianak → Semitau', 'position' => 'Sanggau', 'passengers' => '18 / 28', 'status' => 'Berjalan'], ['code' => 'TRP-007', 'route' => 'Semitau → Pontianak', 'position' => 'Sekadau', 'passengers' => '22 / 32', 'status' => 'Berjalan'], ['code' => 'TRP-011', 'route' => 'Pontianak → Sintang', 'position' => 'Sintang', 'passengers' => '14 / 20', 'status' => 'Berhenti']] as $trip)
                <div class="p-5">
                    <div class="flex items-center justify-between">
                        <p class="font-extrabold text-slate-900">{{ $trip['code'] }}</p><span
                            class="rounded-full bg-green-50 px-2 py-1 text-[10px] font-bold text-green-700">{{ $trip['status'] }}</span>
                    </div>
                    <p class="mt-2 text-sm text-slate-500">{{ $trip['route'] }}</p>
                    <div class="mt-4 flex items-center justify-between text-xs"><span class="text-slate-400">Posisi:
                            <strong class="text-slate-700">{{ $trip['position'] }}</strong></span><strong
                            class="text-brand-700">{{ $trip['passengers'] }} kursi</strong></div>
                </div>
            @endforeach
        </div>
    </section>
</div>
