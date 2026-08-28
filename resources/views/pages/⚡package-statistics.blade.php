<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin')] class extends Component {
    public string $title = 'Statistik';

    public string $section = 'Paket';

    public function mount(): void
    {
        if (! Auth::check()) {
            $this->redirectRoute('login', navigate: true);
        }
    }

    public function logout(): void
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirectRoute('login', navigate: true);
    }
};
?>

<div>
    <div class="mb-8 flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-brand-600">Paket</p>
            <h2 class="mt-2 text-2xl font-extrabold text-slate-900">Statistik Pengiriman Paket</h2>
            <p class="mt-1 text-sm text-slate-500">Ringkasan aktivitas pengiriman paket (data contoh).</p>
        </div>
        <p class="text-sm font-semibold text-slate-500">{{ now()->translatedFormat('l, d F Y') }}</p>
    </div>

    {{-- Kartu ringkasan --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Total Paket', 'value' => '3.482', 'change' => '+9.4%', 'icon' => 'box'],
            ['label' => 'Dalam Perjalanan', 'value' => '128', 'change' => '+12 hari ini', 'icon' => 'truck'],
            ['label' => 'Terkirim', 'value' => '3.201', 'change' => '92% berhasil', 'icon' => 'check'],
            ['label' => 'Bermasalah', 'value' => '15', 'change' => '-2 dari kemarin', 'icon' => 'alert'],
        ] as $stat)
            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                        @if ($stat['icon'] === 'box')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21 8-9-5-9 5 9 5 9-5Zm0 0v8l-9 5m0-8v8m0-8L3 8" /></svg>
                        @elseif ($stat['icon'] === 'truck')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z" /><circle cx="7" cy="18" r="1.5" /><circle cx="17" cy="18" r="1.5" /></svg>
                        @elseif ($stat['icon'] === 'check')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6" /></svg>
                        @else
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4m0 4h.01M10.3 3.9 2.5 17a1.7 1.7 0 0 0 1.5 2.6h16a1.7 1.7 0 0 0 1.5-2.6L13.7 3.9a1.7 1.7 0 0 0-3.4 0Z" /></svg>
                        @endif
                    </span>
                    <span class="text-xs font-bold text-green-600">{{ $stat['change'] }}</span>
                </div>
                <p class="mt-5 text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- Grafik volume paket --}}
        <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm xl:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-slate-900">Volume Paket Masuk</h3>
                    <p class="mt-1 text-xs text-slate-500">Ringkasan 7 hari terakhir</p>
                </div>
                <a href="#" class="text-xs font-bold text-brand-600">Lihat semua &rarr;</a>
            </div>
            <div class="mt-8 flex h-44 items-end justify-between gap-2 sm:gap-4">
                @foreach ([40, 65, 52, 78, 60, 88, 70] as $height)
                    <div class="flex h-full flex-1 flex-col items-center justify-end gap-2">
                        <div class="w-full max-w-12 rounded-t-lg bg-brand-100 transition hover:bg-brand-500" style="height: {{ $height }}%"></div>
                        <span class="text-[10px] font-semibold text-slate-400">{{ now()->subDays(6 - $loop->index)->format('D') }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Distribusi status --}}
        <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <h3 class="font-extrabold text-slate-900">Status Paket</h3>
            <div class="mt-5 space-y-4">
                @foreach ([
                    ['label' => 'Terkirim', 'percent' => 78, 'color' => 'bg-green-500'],
                    ['label' => 'Dalam Perjalanan', 'percent' => 15, 'color' => 'bg-brand-500'],
                    ['label' => 'Menunggu Pickup', 'percent' => 5, 'color' => 'bg-amber-500'],
                    ['label' => 'Bermasalah', 'percent' => 2, 'color' => 'bg-red-500'],
                ] as $status)
                    <div>
                        <div class="mb-1.5 flex items-center justify-between text-xs font-semibold text-slate-600">
                            <span>{{ $status['label'] }}</span>
                            <span>{{ $status['percent'] }}%</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-slate-100">
                            <div class="h-2 rounded-full {{ $status['color'] }}" style="width: {{ $status['percent'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Tabel outlet teraktif --}}
    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 p-6">
            <h3 class="font-extrabold text-slate-900">Outlet Pengiriman Teraktif</h3>
            <a href="#" class="text-xs font-bold text-brand-600">Lihat semua &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-150 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Outlet</th>
                        <th class="px-6 py-4">Paket Terkirim</th>
                        <th class="px-6 py-4">Paket Aktif</th>
                        <th class="px-6 py-4 text-right">Pertumbuhan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ([
                        ['name' => 'Outlet Pontianak Center', 'sent' => 812, 'active' => 34, 'growth' => '+11%'],
                        ['name' => 'Outlet Singkawang Center', 'sent' => 654, 'active' => 21, 'growth' => '+6%'],
                        ['name' => 'Outlet Mempawah Center', 'sent' => 401, 'active' => 12, 'growth' => '+4%'],
                        ['name' => 'Outlet Sungai Pinyuh', 'sent' => 298, 'active' => 9, 'growth' => '-2%'],
                    ] as $outlet)
                        <tr>
                            <td class="px-6 py-4 font-bold text-slate-900">{{ $outlet['name'] }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $outlet['sent'] }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $outlet['active'] }}</td>
                            <td class="px-6 py-4 text-right font-semibold {{ str_starts_with($outlet['growth'], '-') ? 'text-red-600' : 'text-green-600' }}">{{ $outlet['growth'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
