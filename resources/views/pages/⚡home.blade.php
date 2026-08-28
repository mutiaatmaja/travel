<?php

use Livewire\Component;

new class extends Component {
    public string $origin = '';

    public string $destination = '';

    public string $date = '';

    public int $passengers = 1;

    public bool $mobileMenuOpen = false;

    /** @var array<int, string> */
    public array $cities = ['Pontinak', 'Jakarta', 'Bandung', 'Bogor', 'Bekasi', 'Tangerang', 'Serang', 'Semarang', 'Solo', 'Yogyakarta', 'Surabaya', 'Malang'];

    /** @var array<int, array{from: string, to: string, price: string, duration: string, image: string}> */
    public array $popularRoutes = [['from' => 'Jakarta', 'to' => 'Bandung', 'price' => '135.000', 'duration' => '3 jam', 'image' => 'https://picsum.photos/seed/jakarta-bandung/480/320'], ['from' => 'Bandung', 'to' => 'Yogyakarta', 'price' => '210.000', 'duration' => '7 jam', 'image' => 'https://picsum.photos/seed/bandung-jogja/480/320'], ['from' => 'Jakarta', 'to' => 'Semarang', 'price' => '190.000', 'duration' => '8 jam', 'image' => 'https://picsum.photos/seed/jakarta-semarang/480/320'], ['from' => 'Surabaya', 'to' => 'Malang', 'price' => '95.000', 'duration' => '2.5 jam', 'image' => 'https://picsum.photos/seed/surabaya-malang/480/320']];

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    public function swapCities(): void
    {
        [$this->origin, $this->destination] = [$this->destination, $this->origin];
    }

    public function search(): void
    {
        $this->validate(
            [
                'origin' => ['required', 'string'],
                'destination' => ['required', 'string', 'different:origin'],
                'date' => ['required', 'date'],
                'passengers' => ['required', 'integer', 'min:1', 'max:8'],
            ],
            [
                'origin.required' => 'Kota keberangkatan harus diisi.',
                'destination.required' => 'Kota tujuan harus diisi.',
                'destination.different' => 'Kota tujuan harus berbeda dengan kota keberangkatan.',
                'date.required' => 'Tanggal keberangkatan harus diisi.',
                'date.date' => 'Tanggal keberangkatan tidak valid.',
                'passengers.required' => 'Jumlah penumpang harus diisi.',
                'passengers.integer' => 'Jumlah penumpang harus berupa angka.',
                'passengers.min' => 'Jumlah penumpang minimal 1 orang.',
                'passengers.max' => 'Jumlah penumpang maksimal 8 orang.',
            ],
        );

        session()->flash('search-notice', 'Fitur pencarian jadwal segera hadir — data rute masih kami siapkan.');
    }
};
?>

<div class="flex min-h-screen flex-col">
    {{-- Navbar --}}
    <header class="sticky top-0 z-50 border-b border-slate-100 bg-white/80 backdrop-blur">
        <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-500 text-white">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12h18M3 12l4-4M3 12l4 4M21 12l-4-4M21 12l-4 4" />
                    </svg>
                </span>
                <span class="text-lg font-extrabold tracking-tight text-slate-900">Trans<span
                        class="text-brand-500">Go</span></span>
            </a>

            <div class="hidden items-center gap-8 text-sm font-semibold text-slate-600 lg:flex">
                <a href="#rute" class="hover:text-brand-600">Rute Populer</a>
                <a href="#kenapa" class="hover:text-brand-600">Kenapa Kami</a>
                <a href="#cara-pesan" class="hover:text-brand-600">Cara Pesan</a>
                <a href="#outlet" class="hover:text-brand-600">Outlet</a>
            </div>

            <div class="hidden items-center gap-3 lg:flex">
                <a href="/login"
                    class="rounded-full px-4 py-2 text-sm font-semibold text-slate-700 hover:text-brand-600">Masuk</a>
                <a href="#"
                    class="rounded-full bg-brand-500 px-5 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-500/30 hover:bg-brand-600">Daftar</a>
            </div>

            <button type="button" wire:click="$toggle('mobileMenuOpen')"
                class="inline-flex items-center justify-center rounded-lg border border-slate-200 p-2 text-slate-700 lg:hidden"
                aria-label="Buka menu">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    @if ($mobileMenuOpen)
                        <path d="M6 6l12 12M18 6L6 18" />
                    @else
                        <path d="M4 7h16M4 12h16M4 17h16" />
                    @endif
                </svg>
            </button>
        </nav>

        @if ($mobileMenuOpen)
            <div class="border-t border-slate-100 bg-white px-4 py-4 lg:hidden">
                <div class="flex flex-col gap-3 text-sm font-semibold text-slate-700">
                    <a href="#rute" wire:click="$set('mobileMenuOpen', false)"
                        class="rounded-lg px-3 py-2 hover:bg-brand-50 hover:text-brand-600">Rute Populer</a>
                    <a href="#kenapa" wire:click="$set('mobileMenuOpen', false)"
                        class="rounded-lg px-3 py-2 hover:bg-brand-50 hover:text-brand-600">Kenapa Kami</a>
                    <a href="#cara-pesan" wire:click="$set('mobileMenuOpen', false)"
                        class="rounded-lg px-3 py-2 hover:bg-brand-50 hover:text-brand-600">Cara Pesan</a>
                    <a href="#outlet" wire:click="$set('mobileMenuOpen', false)"
                        class="rounded-lg px-3 py-2 hover:bg-brand-50 hover:text-brand-600">Outlet</a>
                    <div class="mt-2 flex flex-col gap-2 border-t border-slate-100 pt-3">
                        <a href="/login" class="rounded-lg px-3 py-2 text-center hover:bg-slate-50">Masuk</a>
                        <a href="#"
                            class="rounded-lg bg-brand-500 px-3 py-2 text-center text-white hover:bg-brand-600">Daftar</a>
                    </div>
                </div>
            </div>
        @endif
    </header>

    <main class="flex-1">
        {{-- Hero --}}
        <section
            class="relative overflow-hidden bg-gradient-to-br from-brand-600 via-brand-500 to-orange-400 pb-28 pt-14 sm:pb-32 sm:pt-20">
            <img src="https://picsum.photos/seed/transgo-hero/1600/900" alt=""
                class="absolute inset-0 h-full w-full object-cover opacity-25 mix-blend-overlay">
            <div class="pointer-events-none absolute -top-24 -right-24 h-72 w-72 rounded-full bg-white/10 blur-3xl">
            </div>
            <div
                class="pointer-events-none absolute bottom-0 left-1/2 h-64 w-64 -translate-x-1/2 rounded-full bg-white/10 blur-3xl">
            </div>

            <div class="relative mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <span
                    class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-xs font-semibold text-white ring-1 ring-white/30">
                    Shuttle antar kota, aman &amp; tepat waktu
                </span>
                <h1 class="mx-auto mt-6 max-w-3xl text-3xl font-extrabold leading-tight text-white sm:text-5xl">
                    Jarak Antar Kota Terasa Lebih Dekat
                </h1>
                <p class="mx-auto mt-4 max-w-xl text-sm text-white/90 sm:text-base">
                    Pesan tiket shuttle dalam hitungan menit. Armada nyaman, kursi bisa dipilih sendiri, dan perjalanan
                    pool to pool ke kotamu.
                </p>
            </div>

            {{-- Search card --}}
            <div class="relative mx-auto mt-10 max-w-5xl px-4 sm:px-6 lg:px-8">
                @if (session('search-notice'))
                    <div class="mb-4 rounded-xl bg-white/95 px-4 py-3 text-sm font-medium text-brand-700 shadow-lg">
                        {{ session('search-notice') }}
                    </div>
                @endif

                <form wire:submit="search" class="rounded-2xl bg-white p-4 shadow-xl shadow-brand-900/20 sm:p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-12 lg:items-end">
                        <div class="lg:col-span-4">
                            <label
                                class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Keberangkatan</label>
                            <div class="relative">
                                <span
                                    class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-brand-500">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z" />
                                        <circle cx="12" cy="10" r="2.5" />
                                    </svg>
                                </span>
                                <select wire:model="origin"
                                    class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-3 text-sm font-medium text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-100">
                                    <option value="">Pilih kota asal</option>
                                    @foreach ($cities as $city)
                                        <option value="{{ $city }}">{{ $city }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('origin')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-center lg:col-span-1">
                            <button type="button" wire:click="swapCities"
                                class="mt-5 flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-brand-500 transition hover:bg-brand-50 lg:mt-0"
                                aria-label="Tukar kota">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 3l4 4-4 4M20 7H4M8 21l-4-4 4-4M4 17h16" />
                                </svg>
                            </button>
                        </div>

                        <div class="lg:col-span-4">
                            <label
                                class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tujuan</label>
                            <div class="relative">
                                <span
                                    class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-brand-500">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M5 12h14M13 6l6 6-6 6" />
                                    </svg>
                                </span>
                                <select wire:model="destination"
                                    class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-3 text-sm font-medium text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-100">
                                    <option value="">Pilih kota tujuan</option>
                                    @foreach ($cities as $city)
                                        <option value="{{ $city }}">{{ $city }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('destination')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-1 lg:col-span-3">
                            <label
                                class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal</label>
                            <input type="date" wire:model="date"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 px-3 text-sm font-medium text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-100">
                            @error('date')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-12 lg:items-end">
                        <div class="sm:col-span-1 lg:col-span-4">
                            <label
                                class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Penumpang</label>
                            <select wire:model="passengers"
                                class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 py-3 px-3 text-sm font-medium text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-100">
                                @for ($i = 1; $i <= 8; $i++)
                                    <option value="{{ $i }}">{{ $i }} Orang</option>
                                @endfor
                            </select>
                        </div>

                        <div class="lg:col-span-8">
                            <button type="submit"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-500 py-3.5 text-sm font-bold text-white shadow-lg shadow-brand-500/30 transition hover:bg-brand-600">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="7" />
                                    <path d="m21 21-4.3-4.3" />
                                </svg>
                                Cari Jadwal
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        {{-- Trust badges --}}
        <section class="relative -mt-14 sm:-mt-16">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div
                    class="grid grid-cols-2 gap-4 rounded-2xl bg-white p-4 shadow-xl shadow-slate-200/60 sm:grid-cols-4 sm:p-6">
                    @foreach ([['icon' => 'shield', 'label' => 'Aman & Terpercaya'], ['icon' => 'map', 'label' => 'GPS Tracking'], ['icon' => 'seat', 'label' => 'Kursi Nyaman'], ['icon' => 'clock', 'label' => 'Tepat Waktu']] as $badge)
                        <div class="flex flex-col items-center gap-2 text-center sm:flex-row sm:text-left">
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                                @switch($badge['icon'])
                                    @case('shield')
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3Z" />
                                        </svg>
                                    @break

                                    @case('map')
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="10" r="3" />
                                            <path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z" />
                                        </svg>
                                    @break

                                    @case('seat')
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M6 4v9a3 3 0 0 0 3 3h6M6 13H4a2 2 0 0 0-2 2v4h18v-3a2 2 0 0 0-2-2h-2" />
                                        </svg>
                                    @break

                                    @case('clock')
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="9" />
                                            <path d="M12 7v5l3 3" />
                                        </svg>
                                    @endswitch
                                </span>
                                <span class="text-sm font-semibold text-slate-700">{{ $badge['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Layanan Lainnya --}}
            <section id="layanan" class="bg-orange-50/70 py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <span class="text-sm font-bold uppercase tracking-wide text-brand-600">Layanan Lainnya</span>
                        <h2 class="mt-2 text-2xl font-extrabold text-slate-900 sm:text-3xl">Lebih dari Sekadar Perjalanan
                        </h2>
                        <p class="mt-3 text-sm text-slate-500 sm:text-base">Berbagai kebutuhan perjalanan dan pengiriman
                            tersedia dalam satu layanan.</p>
                    </div>

                    <div class="mt-12 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ([['title' => 'Bisa Kirim Paket', 'desc' => 'Kirim dokumen dan paket antar kota dengan mudah dan aman.', 'icon' => 'package'], ['title' => 'Beli Tiket Pesawat', 'desc' => 'Temukan dan pesan tiket pesawat untuk perjalanan lanjutanmu.', 'icon' => 'plane'], ['title' => 'Akomodasi Perjalanan', 'desc' => 'Siapkan penginapan nyaman di kota tujuan tanpa ribet.', 'icon' => 'hotel'], ['title' => 'Asuransi Penumpang', 'desc' => 'Perjalanan lebih tenang dengan perlindungan untuk setiap penumpang.', 'icon' => 'shield']] as $service)
                            <div
                                class="rounded-2xl border border-orange-100 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-brand-200 hover:shadow-lg">
                                <span
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-500 text-white shadow-md shadow-brand-500/20">
                                    @if ($service['icon'] === 'package')
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="m21 8-9-5-9 5 9 5 9-5Zm0 0v8l-9 5m0-8v8m0-8L3 8" />
                                        </svg>
                                    @elseif ($service['icon'] === 'plane')
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="m2 16 20-5-8-3-3-5-2 1 1 6-6 2-2-2-1 1 3 5Z" />
                                            <path d="M9 18h8" />
                                        </svg>
                                    @elseif ($service['icon'] === 'hotel')
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path
                                                d="M3 21V8l9-5 9 5v13M3 10h18M7 21v-5h10v5M7 12h.01M11 12h.01M15 12h.01" />
                                        </svg>
                                    @else
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3Z" />
                                            <path d="m9 12 2 2 4-4" />
                                        </svg>
                                    @endif
                                </span>
                                <h3 class="mt-5 text-base font-bold text-slate-900">{{ $service['title'] }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-500">{{ $service['desc'] }}</p>
                                <a href="#"
                                    class="mt-5 inline-flex items-center gap-1 text-sm font-bold text-brand-600 hover:text-brand-700">Pelajari
                                    lebih lanjut <span aria-hidden="true">&rarr;</span></a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Kenapa Kami --}}
            <section id="kenapa" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="text-sm font-bold uppercase tracking-wide text-brand-600">Kenapa Kami</span>
                    <h2 class="mt-2 text-2xl font-extrabold text-slate-900 sm:text-3xl">Perjalanan Lebih Mudah &amp;
                        Menyenangkan</h2>
                    <p class="mt-3 text-sm text-slate-500 sm:text-base">Kami hadir untuk membuat perjalanan antar kotamu
                        lebih nyaman, aman, dan bebas ribet.</p>
                </div>

                <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([['title' => 'Harga Terjangkau', 'desc' => 'Tiket murah dengan banyak promo dan jadwal keberangkatan setiap hari.'], ['title' => 'Armada Ergonomis', 'desc' => 'Reclining seat, jarak kaki lega, dan kabin bersih di setiap perjalanan.'], ['title' => 'Pool to Pool', 'desc' => 'Naik dan turun tepat di outlet pilihanmu, dekat dengan tujuan.'], ['title' => 'Pantau Real-time', 'desc' => 'Ikuti posisi armada dan estimasi waktu tiba lewat GPS tracking.'], ['title' => 'CCTV & Keamanan', 'desc' => 'Setiap armada dilengkapi CCTV demi rasa aman selama perjalanan.'], ['title' => 'Dukungan 24 Jam', 'desc' => 'Tim layanan pelanggan siap membantu kapan pun kamu butuh.']] as $feature)
                        <div
                            class="group rounded-2xl border border-slate-100 p-6 shadow-sm transition hover:-translate-y-1 hover:border-brand-200 hover:shadow-lg">
                            <span
                                class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-500/10 text-brand-600 group-hover:bg-brand-500 group-hover:text-white">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                            <h3 class="mt-4 text-base font-bold text-slate-900">{{ $feature['title'] }}</h3>
                            <p class="mt-2 text-sm text-slate-500">{{ $feature['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Rute Populer --}}
            <section id="rute" class="bg-slate-50 py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                        <div>
                            <span class="text-sm font-bold uppercase tracking-wide text-brand-600">Rute Populer</span>
                            <h2 class="mt-2 text-2xl font-extrabold text-slate-900 sm:text-3xl">Pilihan Rute Favorit</h2>
                        </div>
                        <a href="#" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Lihat semua
                            rute &rarr;</a>
                    </div>

                    <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($popularRoutes as $route)
                            <div
                                class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-100 transition hover:shadow-lg">
                                <img src="{{ $route['image'] }}" alt="Rute {{ $route['from'] }} - {{ $route['to'] }}"
                                    class="h-36 w-full object-cover">
                                <div class="p-5">
                                    <div class="flex items-center gap-2 text-sm font-bold text-slate-900">
                                        <span>{{ $route['from'] }}</span>
                                        <svg class="h-4 w-4 text-brand-500" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path d="M5 12h14M13 6l6 6-6 6" />
                                        </svg>
                                        <span>{{ $route['to'] }}</span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-400">Estimasi {{ $route['duration'] }}</p>
                                    <div class="mt-4 flex items-center justify-between">
                                        <div>
                                            <span class="text-xs text-slate-400">Mulai dari</span>
                                            <p class="text-lg font-extrabold text-brand-600">Rp{{ $route['price'] }}</p>
                                        </div>
                                        <button type="button"
                                            class="rounded-full bg-brand-50 px-4 py-2 text-xs font-bold text-brand-600 hover:bg-brand-500 hover:text-white">Pesan</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Cara Pesan --}}
            <section id="cara-pesan" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="text-sm font-bold uppercase tracking-wide text-brand-600">Cara Pesan</span>
                    <h2 class="mt-2 text-2xl font-extrabold text-slate-900 sm:text-3xl">Pesan Tiket Hanya 3 Langkah</h2>
                </div>

                <div class="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-3">
                    @foreach ([['step' => '01', 'title' => 'Cari Jadwal', 'desc' => 'Pilih kota asal, tujuan, tanggal, dan jumlah penumpang.'], ['step' => '02', 'title' => 'Pilih Kursi', 'desc' => 'Tentukan kursi favoritmu dan lengkapi data penumpang.'], ['step' => '03', 'title' => 'Bayar & Berangkat', 'desc' => 'Selesaikan pembayaran dan tunjukkan e-tiket saat keberangkatan.']] as $step)
                        <div class="relative rounded-2xl border border-slate-100 p-6 text-center shadow-sm">
                            <span
                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-500 text-sm font-extrabold text-white">{{ $step['step'] }}</span>
                            <h3 class="mt-4 text-base font-bold text-slate-900">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-sm text-slate-500">{{ $step['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Galeri / Slider --}}
            <section id="outlet" class="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8">
                <div class="mx-auto mb-10 max-w-2xl text-center">
                    <span class="text-sm font-bold uppercase tracking-wide text-brand-600">Galeri Perjalanan</span>
                    <h2 class="mt-2 text-2xl font-extrabold text-slate-900 sm:text-3xl">Momen Perjalanan Bersama Kami</h2>
                </div>

                <div x-data="{
                    slides: [
                        { image: 'https://picsum.photos/seed/transgo-slide-1/1200/600', caption: 'Armada nyaman siap mengantar perjalananmu' },
                        { image: 'https://picsum.photos/seed/transgo-slide-2/1200/600', caption: 'Pool to pool, dekat dengan tujuanmu' },
                        { image: 'https://picsum.photos/seed/transgo-slide-3/1200/600', caption: 'Kursi reclining untuk perjalanan panjang' },
                        { image: 'https://picsum.photos/seed/transgo-slide-4/1200/600', caption: 'Pantau perjalanan lewat GPS tracking' },
                    ],
                    active: 0,
                    timer: null,
                    start() { this.timer = setInterval(() => this.next(), 5000) },
                    stop() { clearInterval(this.timer) },
                    next() { this.active = (this.active + 1) % this.slides.length },
                    prev() { this.active = (this.active - 1 + this.slides.length) % this.slides.length },
                }" x-init="start()" @mouseenter="stop()" @mouseleave="start()"
                    class="relative overflow-hidden rounded-3xl shadow-xl">
                    <div class="relative h-64 sm:h-80 lg:h-96">
                        <template x-for="(slide, index) in slides" :key="index">
                            <div x-show="active === index" x-transition:enter="transition ease-out duration-700"
                                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                class="absolute inset-0">
                                <img :src="slide.image" :alt="slide.caption" class="h-full w-full object-cover">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent">
                                </div>
                                <p class="absolute bottom-6 left-6 right-6 text-base font-semibold text-white sm:text-lg"
                                    x-text="slide.caption"></p>
                            </div>
                        </template>
                    </div>

                    <button type="button" @click="prev()"
                        class="absolute left-4 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-slate-700 backdrop-blur transition hover:bg-white"
                        aria-label="Slide sebelumnya">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="m15 18-6-6 6-6" />
                        </svg>
                    </button>
                    <button type="button" @click="next()"
                        class="absolute right-4 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-slate-700 backdrop-blur transition hover:bg-white"
                        aria-label="Slide berikutnya">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </button>

                    <div class="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-2">
                        <template x-for="(slide, index) in slides" :key="index">
                            <button type="button" @click="active = index"
                                :class="active === index ? 'bg-white' : 'bg-white/40'"
                                class="h-2 w-2 rounded-full transition" aria-label="Ke slide"></button>
                        </template>
                    </div>
                </div>
            </section>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-slate-100 bg-slate-900 text-slate-300">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-500 text-white">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 12h18M3 12l4-4M3 12l4 4M21 12l-4-4M21 12l-4 4" />
                                </svg>
                            </span>
                            <span class="text-lg font-extrabold text-white">Trans<span
                                    class="text-brand-400">Go</span></span>
                        </div>
                        <p class="mt-4 text-sm text-slate-400">Layanan shuttle antar kota yang aman, nyaman, dan tepat
                            waktu untuk perjalananmu.</p>
                    </div>

                    <div>
                        <h4 class="text-sm font-bold text-white">Layanan</h4>
                        <ul class="mt-4 space-y-2 text-sm">
                            <li><a href="#" class="hover:text-brand-400">Pesan Tiket</a></li>
                            <li><a href="#" class="hover:text-brand-400">Rute Populer</a></li>
                            <li><a href="#" class="hover:text-brand-400">Cara Bayar</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-sm font-bold text-white">Informasi</h4>
                        <ul class="mt-4 space-y-2 text-sm">
                            <li><a href="#" class="hover:text-brand-400">Tentang Kami</a></li>
                            <li><a href="#" class="hover:text-brand-400">Daftar Outlet</a></li>
                            <li><a href="#" class="hover:text-brand-400">Syarat &amp; Ketentuan</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-sm font-bold text-white">Kontak</h4>
                        <ul class="mt-4 space-y-2 text-sm text-slate-400">
                            <li>Jl. Contoh Raya No. 1, Jakarta</li>
                            <li>0800 1 234 567</li>
                            <li>hello@transgo.id</li>
                        </ul>
                    </div>
                </div>

                <div
                    class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-slate-800 pt-6 text-xs text-slate-500 sm:flex-row">
                    <p>&copy; {{ now()->year }} TransGo. Seluruh hak cipta dilindungi.</p>
                    <div class="flex gap-4">
                        <a href="#" class="hover:text-brand-400">Kebijakan Privasi</a>
                        <a href="#" class="hover:text-brand-400">Syarat &amp; Ketentuan</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
