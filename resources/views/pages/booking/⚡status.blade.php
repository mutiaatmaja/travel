<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin', ['title' => 'Status Booking', 'section' => 'Booking'])] class extends Component {
    public string $title = 'Status Booking';

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
            <h2 class="mt-2 text-3xl font-extrabold text-slate-900">Status Booking</h2>
            <p class="mt-2 text-sm text-slate-500">Pantau tahapan booking dari pembayaran sampai perjalanan selesai.</p>
        </div>
        <a wire:navigate href="{{ route('booking') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">
            Lihat daftar booking
        </a>
    </div>

    <div class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([['label' => 'Menunggu pembayaran', 'value' => '18', 'class' => 'border-amber-200 bg-amber-50 text-amber-700'], ['label' => 'Dikonfirmasi', 'value' => '76', 'class' => 'border-blue-200 bg-blue-50 text-blue-700'], ['label' => 'Dalam perjalanan', 'value' => '12', 'class' => 'border-brand-200 bg-brand-50 text-brand-700'], ['label' => 'Selesai', 'value' => '34', 'class' => 'border-green-200 bg-green-50 text-green-700']] as $status)
            <div class="rounded-2xl border p-5 {{ $status['class'] }}">
                <p class="text-xs font-bold uppercase tracking-wide">{{ $status['label'] }}</p>
                <p class="mt-3 text-3xl font-extrabold">{{ $status['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 overflow-x-auto pb-4">
        <div class="grid min-w-275 grid-cols-4 gap-4">
            @foreach ([['title' => 'Menunggu pembayaran', 'count' => '18', 'class' => 'border-amber-200 bg-amber-50', 'dot' => 'bg-amber-500', 'items' => [['code' => 'BKG-20260914-002', 'name' => 'Sari Melati', 'route' => 'Sintang → Pontianak', 'time' => '08:30'], ['code' => 'BKG-20260914-006', 'name' => 'Dedi Irawan', 'route' => 'Sekadau → Pontianak', 'time' => '11:00']]], ['title' => 'Dikonfirmasi', 'count' => '76', 'class' => 'border-blue-200 bg-blue-50', 'dot' => 'bg-blue-500', 'items' => [['code' => 'BKG-20260914-001', 'name' => 'Budi Santoso', 'route' => 'Semitau → Sekadau', 'time' => '07:00'], ['code' => 'BKG-20260914-004', 'name' => 'Maya Putri', 'route' => 'Semitau → Sintang', 'time' => '07:30']]], ['title' => 'Dalam perjalanan', 'count' => '12', 'class' => 'border-brand-200 bg-brand-50', 'dot' => 'bg-brand-500', 'items' => [['code' => 'BKG-20260914-003', 'name' => 'Andi Wijaya', 'route' => 'Sekadau → Pontianak', 'time' => '10:00'], ['code' => 'BKG-20260914-005', 'name' => 'Lina Sari', 'route' => 'Sintang → Sekadau', 'time' => '09:15']]], ['title' => 'Selesai', 'count' => '34', 'class' => 'border-green-200 bg-green-50', 'dot' => 'bg-green-500', 'items' => [['code' => 'BKG-20260913-018', 'name' => 'Rina Permata', 'route' => 'Semitau → Pontianak', 'time' => '06:30'], ['code' => 'BKG-20260913-017', 'name' => 'Fajar Nugraha', 'route' => 'Sintang → Pontianak', 'time' => '08:00']]]] as $column)
                <section class="rounded-2xl border bg-white shadow-sm {{ $column['class'] }}">
                    <div class="flex items-center justify-between border-b border-black/5 p-4">
                        <div class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full {{ $column['dot'] }}"></span><h3 class="text-sm font-extrabold text-slate-800">{{ $column['title'] }}</h3></div>
                        <span class="rounded-full bg-white/80 px-2 py-1 text-xs font-bold text-slate-600">{{ $column['count'] }}</span>
                    </div>
                    <div class="space-y-3 p-3">
                        @foreach ($column['items'] as $item)
                            <article class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
                                <div class="flex items-start justify-between gap-3"><p class="text-xs font-extrabold text-slate-900">{{ $item['code'] }}</p><span class="text-[10px] font-bold text-slate-400">{{ $item['time'] }}</span></div>
                                <p class="mt-3 text-sm font-bold text-slate-800">{{ $item['name'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $item['route'] }}</p>
                                <button type="button" class="mt-3 text-xs font-bold text-brand-600">Lihat detail</button>
                            </article>
                        @endforeach
                        <button type="button" class="w-full rounded-xl border border-dashed border-slate-300 py-3 text-xs font-bold text-slate-500 hover:border-brand-300 hover:text-brand-600">Lihat semua</button>
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</div>