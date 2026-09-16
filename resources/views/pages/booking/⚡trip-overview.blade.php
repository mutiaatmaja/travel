<?php

use App\Models\Trip;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin', ['title' => 'Perjalanan / Trip', 'section' => 'Booking'])] class extends Component {
    use WithPagination;

    public string $title = 'Perjalanan / Trip';

    public string $section = 'Booking';

    public string $search = '';

    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function logout(): void
    {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        $this->redirectRoute('login', navigate: true);
    }

    public function render(): mixed
    {
        $today = now()->toDateString();

        $trips = Trip::query()
            ->with(['travelRoute.originCity', 'travelRoute.destinationCity', 'vehicle', 'driver'])
            ->when($this->search !== '', fn($query) => $query->where('trip_code', 'like', '%' . $this->search . '%')->orWhereHas('travelRoute', fn($route) => $route->where('name', 'like', '%' . $this->search . '%')))
            ->when($this->statusFilter !== '', fn($query) => $query->where('status', $this->statusFilter))
            ->orderByDesc('departure_date')
            ->orderByDesc('departure_time')
            ->paginate(10);

        return view('pages.booking.⚡trip-overview', [
            'trips' => $trips,
            'stats' => [
                'today' => Trip::whereDate('departure_date', $today)->count(),
                'waiting' => Trip::whereIn('status', ['draft', 'scheduled'])
                    ->whereDate('departure_date', $today)
                    ->count(),
                'ongoing' => Trip::whereIn('status', ['boarding', 'on_the_way'])->count(),
                'completed' => Trip::where('status', 'completed')->whereDate('departure_date', $today)->count(),
            ],
        ]);
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
        <a wire:navigate href="{{ route('trips') }}"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-brand-500/20 hover:bg-brand-600">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12h14" />
            </svg>
            Kelola Jadwal
        </a>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['label' => 'Trip hari ini', 'value' => $stats['today'], 'detail' => 'Semua status', 'color' => 'brand'], ['label' => 'Menunggu berangkat', 'value' => $stats['waiting'], 'detail' => 'Hari ini · draft & terjadwal', 'color' => 'amber'], ['label' => 'Sedang berjalan', 'value' => $stats['ongoing'], 'detail' => 'Boarding & dalam perjalanan', 'color' => 'blue'], ['label' => 'Selesai', 'value' => $stats['completed'], 'detail' => 'Hari ini', 'color' => 'green']] as $stat)
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
                <h3 class="font-extrabold text-slate-900">Daftar Trip</h3>
                <p class="mt-1 text-xs text-slate-500">Trip yang dibuat dari Jadwal (Master Data) dan mendapat kode
                    otomatis.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <input wire:model.live.debounce.300ms="search" placeholder="Cari kode trip atau rute..."
                    class="rounded-xl border px-4 py-2.5 text-sm sm:w-64">
                <select wire:model.live="statusFilter" class="rounded-xl border px-4 py-2.5 text-sm">
                    <option value="">Semua status</option>
                    <option value="draft">Draft</option>
                    <option value="scheduled">Terjadwal</option>
                    <option value="boarding">Boarding</option>
                    <option value="on_the_way">Dalam perjalanan</option>
                    <option value="completed">Selesai</option>
                    <option value="cancelled">Dibatalkan</option>
                </select>
            </div>
        </div>

        <div class="relative overflow-x-auto">
            <div wire:loading wire:target="search,statusFilter"
                class="absolute inset-0 z-10 flex items-center justify-center bg-white/70">
                <span class="rounded-xl bg-white px-4 py-3 text-sm font-semibold shadow-lg">Memuat data...</span>
            </div>
            <table class="w-full min-w-250 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Kode Trip</th>
                        <th class="px-6 py-4">Rute</th>
                        <th class="px-6 py-4">Jadwal</th>
                        <th class="px-6 py-4">Armada</th>
                        <th class="px-6 py-4">Kapasitas</th>
                        <th class="px-6 py-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($trips as $trip)
                        @php($statusMap = ['draft' => ['label' => 'Draft', 'class' => 'bg-slate-100 text-slate-600'], 'scheduled' => ['label' => 'Terjadwal', 'class' => 'bg-amber-50 text-amber-700'], 'boarding' => ['label' => 'Boarding', 'class' => 'bg-blue-50 text-blue-700'], 'on_the_way' => ['label' => 'Dalam perjalanan', 'class' => 'bg-brand-50 text-brand-700'], 'completed' => ['label' => 'Selesai', 'class' => 'bg-green-50 text-green-700'], 'cancelled' => ['label' => 'Dibatalkan', 'class' => 'bg-red-50 text-red-700']])
                        <tr>
                            <td class="px-6 py-4 font-bold text-slate-900">{{ $trip->trip_code ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-800">
                                    {{ $trip->travelRoute?->originCity?->name ?? '-' }} &rarr;
                                    {{ $trip->travelRoute?->destinationCity?->name ?? '-' }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $trip->travelRoute?->name }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold">{{ $trip->departure_date->format('d M Y') }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ substr($trip->departure_time, 0, 5) }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold">{{ $trip->vehicle?->code }}</p>
                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $trip->driver?->name ?? 'Supir belum ditentukan' }}</p>
                            </td>
                            <td class="px-6 py-4 font-bold text-brand-700">{{ $trip->vehicle?->seat_capacity ?? '-' }}
                                kursi</td>
                            <td class="px-6 py-4">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusMap[$trip->status]['class'] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $statusMap[$trip->status]['label'] ?? ucfirst($trip->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">Belum ada trip. Buat jadwal
                                terlebih dahulu di menu Jadwal.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-6 py-4">{{ $trips->links() }}</div>
    </div>

    <div class="mt-6 rounded-2xl border border-brand-100 bg-brand-50 p-5">
        <p class="text-sm font-extrabold text-brand-800">Alur data</p>
        <div class="mt-4 grid grid-cols-1 gap-3 text-sm font-semibold text-brand-900 sm:grid-cols-4">
            <p>1. Jadwal dibuat</p>
            <p>2. Trip mendapat kode otomatis</p>
            <p>3. Booking memilih trip</p>
            <p>4. Armada dipantau</p>
        </div>
    </div>
</div>
