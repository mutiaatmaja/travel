<?php

use App\Models\Booking;
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

    public function render(): mixed
    {
        $bookings = Booking::with(['trip.travelRoute.originCity', 'trip.travelRoute.destinationCity', 'originStop.outlet', 'destinationStop.outlet'])
            ->whereIn('status', ['pending', 'confirmed', 'completed', 'cancelled'])
            ->latest()
            ->limit(50)
            ->get()
            ->groupBy('status');

        return view('pages.booking.⚡status', [
            'columns' => [['key' => 'pending', 'title' => 'Menunggu pembayaran', 'class' => 'border-amber-200 bg-amber-50', 'dot' => 'bg-amber-500'], ['key' => 'confirmed', 'title' => 'Dikonfirmasi', 'class' => 'border-blue-200 bg-blue-50', 'dot' => 'bg-blue-500'], ['key' => 'completed', 'title' => 'Selesai', 'class' => 'border-green-200 bg-green-50', 'dot' => 'bg-green-500'], ['key' => 'cancelled', 'title' => 'Dibatalkan', 'class' => 'border-red-200 bg-red-50', 'dot' => 'bg-red-500']],
            'bookingsByStatus' => $bookings,
        ]);
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
        <a wire:navigate href="{{ route('booking') }}"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">
            Lihat daftar booking
        </a>
    </div>

    <div class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ($columns as $column)
            <div class="rounded-2xl border p-5 {{ $column['class'] }}">
                <p class="text-xs font-bold uppercase tracking-wide">{{ $column['title'] }}</p>
                <p class="mt-3 text-3xl font-extrabold">{{ $bookingsByStatus->get($column['key'], collect())->count() }}
                </p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 overflow-x-auto pb-4">
        <div class="grid min-w-275 grid-cols-4 gap-4">
            @foreach ($columns as $column)
                @php($items = $bookingsByStatus->get($column['key'], collect()))
                <section class="rounded-2xl border bg-white shadow-sm {{ $column['class'] }}">
                    <div class="flex items-center justify-between border-b border-black/5 p-4">
                        <div class="flex items-center gap-2"><span
                                class="h-2.5 w-2.5 rounded-full {{ $column['dot'] }}"></span>
                            <h3 class="text-sm font-extrabold text-slate-800">{{ $column['title'] }}</h3>
                        </div>
                        <span
                            class="rounded-full bg-white/80 px-2 py-1 text-xs font-bold text-slate-600">{{ $items->count() }}</span>
                    </div>
                    <div class="space-y-3 p-3">
                        @forelse ($items->take(6) as $booking)
                            <article class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm"
                                wire:key="status-booking-{{ $booking->id }}">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-xs font-extrabold text-slate-900">{{ $booking->booking_code }}</p>
                                    <span
                                        class="text-[10px] font-bold text-slate-400">{{ substr($booking->trip->departure_time ?? '', 0, 5) }}</span>
                                </div>
                                <p class="mt-3 text-sm font-bold text-slate-800">{{ $booking->customer_name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $booking->originStop->outlet->name }} &rarr;
                                    {{ $booking->destinationStop->outlet->name }}</p>
                            </article>
                        @empty
                            <p class="px-1 py-6 text-center text-xs text-slate-400">Belum ada booking di status ini.</p>
                        @endforelse
                        @if ($items->count() > 6)
                            <a wire:navigate href="{{ route('booking') }}"
                                class="block w-full rounded-xl border border-dashed border-slate-300 py-3 text-center text-xs font-bold text-slate-500 hover:border-brand-300 hover:text-brand-600">Lihat
                                semua</a>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</div>
