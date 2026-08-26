<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin')] class extends Component {
    public string $title = 'Dashboard';

    public string $section = 'Overview';

    public function mount(): void
    {
        if (!Auth::check()) {
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
    @if (session('toast'))
        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
            {{ session('toast.message') }}
        </div>
    @endif

    <div class="mb-8 flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
        <div>
            <h2 class="text-2xl font-extrabold text-slate-900">Selamat datang, {{ auth()->user()->name }}!</h2>
            <p class="mt-1 text-sm text-slate-500">Berikut ringkasan aktivitas TransGo hari ini.</p>
        </div>
        <p class="text-sm font-semibold text-slate-500">{{ now()->translatedFormat('l, d F Y') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['label' => 'Total Booking', 'value' => '1,248', 'change' => '+12.5%'], ['label' => 'Pendapatan', 'value' => 'Rp42,8 Jt', 'change' => '+8.2%'], ['label' => 'Jadwal Aktif', 'value' => '86', 'change' => '+4 hari ini'], ['label' => 'Armada Aktif', 'value' => '32', 'change' => '92% tersedia']] as $stat)
            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><svg
                            class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="16" rx="2" />
                            <path d="M7 8h10M7 12h6" />
                        </svg></span>
                    <span class="text-xs font-bold text-green-600">{{ $stat['change'] }}</span>
                </div>
                <p class="mt-5 text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm xl:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-slate-900">Aktivitas Booking</h3>
                    <p class="mt-1 text-xs text-slate-500">Ringkasan 7 hari terakhir</p>
                </div><a href="#" class="text-xs font-bold text-brand-600">Lihat laporan &rarr;</a>
            </div>
            <div class="mt-8 flex h-44 items-end justify-between gap-2 sm:gap-4">
                @foreach ([55, 72, 46, 83, 68, 92, 78] as $height)
                    <div class="flex h-full flex-1 flex-col items-center justify-end gap-2">
                        <div class="w-full max-w-12 rounded-t-lg bg-brand-100 transition hover:bg-brand-500"
                            style="height: {{ $height }}%"></div><span
                            class="text-[10px] font-semibold text-slate-400">{{ now()->subDays(6 - $loop->index)->format('D') }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="font-extrabold text-slate-900">Booking Terbaru</h3><a href="#"
                    class="text-xs font-bold text-brand-600">Semua</a>
            </div>
            <div class="mt-5 space-y-4">
                @foreach ([['code' => 'TRG-1024', 'route' => 'Jakarta - Bandung', 'status' => 'Lunas'], ['code' => 'TRG-1023', 'route' => 'Bandung - Yogyakarta', 'status' => 'Menunggu'], ['code' => 'TRG-1022', 'route' => 'Surabaya - Malang', 'status' => 'Lunas']] as $booking)
                    <div class="flex items-center gap-3"><span
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><svg
                                class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M5 12h14M13 6l6 6-6 6" />
                            </svg></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-slate-800">{{ $booking['code'] }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $booking['route'] }}</p>
                        </div><span
                            class="text-[10px] font-bold {{ $booking['status'] === 'Lunas' ? 'text-green-600' : 'text-amber-600' }}">{{ $booking['status'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
