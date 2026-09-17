<?php

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\BookingSetting;
use App\Models\RouteFare;
use App\Models\RouteStop;
use App\Models\Trip;
use App\Models\VehicleSeat;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin', ['title' => 'Booking', 'section' => 'Booking'])] class extends Component {
    use WithPagination;

    public string $title = 'Booking';

    public string $section = 'Booking';

    public string $search = '';

    public string $statusFilter = '';

    public bool $modalOpen = false;

    public bool $confirmCancelOpen = false;

    public ?int $cancelId = null;

    public ?int $tripId = null;

    public ?int $originStopId = null;

    public ?int $destinationStopId = null;

    public string $customerName = '';

    public string $phone = '';

    public int $passengerCount = 1;

    /** @var array<int, int> */
    public array $selectedSeatIds = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTripId(): void
    {
        $this->originStopId = null;
        $this->destinationStopId = null;
        $this->selectedSeatIds = [];
    }

    public function updatedOriginStopId(): void
    {
        $this->selectedSeatIds = [];
    }

    public function updatedDestinationStopId(): void
    {
        $this->selectedSeatIds = [];
    }

    public function updatedPassengerCount(): void
    {
        if (count($this->selectedSeatIds) > $this->passengerCount) {
            $this->selectedSeatIds = array_slice($this->selectedSeatIds, 0, (int) $this->passengerCount);
        }
    }

    public function toggleSeat(int $seatId): void
    {
        $trip = $this->tripId ? Trip::find($this->tripId) : null;
        $originStop = $this->originStopId ? RouteStop::find($this->originStopId) : null;
        $destinationStop = $this->destinationStopId ? RouteStop::find($this->destinationStopId) : null;

        if ($trip && $originStop && $destinationStop && in_array($seatId, Booking::seatIdsInUseForSegment($trip, $originStop, $destinationStop), true)) {
            $this->addError('selectedSeatIds', 'Kursi tersebut sudah diambil pada segmen perjalanan ini.');

            return;
        }

        if (in_array($seatId, $this->selectedSeatIds, true)) {
            $this->selectedSeatIds = array_values(array_diff($this->selectedSeatIds, [$seatId]));

            return;
        }

        if (count($this->selectedSeatIds) >= $this->passengerCount) {
            $this->addError('selectedSeatIds', 'Jumlah kursi sudah sesuai jumlah penumpang. Kurangi penumpang atau lepas kursi lain dahulu.');

            return;
        }

        $this->selectedSeatIds[] = $seatId;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $this->validate([
            'tripId' => ['required', 'exists:trips,id'],
            'originStopId' => ['required', 'exists:route_stops,id'],
            'destinationStopId' => ['required', 'different:originStopId', 'exists:route_stops,id'],
            'customerName' => ['required', 'max:255'],
            'phone' => ['nullable', 'max:30'],
            'passengerCount' => ['required', 'integer', 'min:1'],
        ]);

        $trip = Trip::findOrFail($this->tripId);
        $originStop = RouteStop::where('travel_route_id', $trip->travel_route_id)->findOrFail($this->originStopId);
        $destinationStop = RouteStop::where('travel_route_id', $trip->travel_route_id)->findOrFail($this->destinationStopId);

        if ($originStop->stop_sequence >= $destinationStop->stop_sequence) {
            $this->addError('destinationStopId', 'Titik tujuan harus berada setelah titik naik.');

            return;
        }

        $bookingSetting = BookingSetting::where('is_active', true)->first();
        $maxPassengers = $bookingSetting->max_passengers ?? 8;

        if ($this->passengerCount > $maxPassengers) {
            $this->addError('passengerCount', "Maksimal {$maxPassengers} penumpang per booking.");

            return;
        }

        $availableSeatIds = Booking::availableSeatsForSegment($trip, $originStop, $destinationStop)->pluck('id')->all();
        $selectedSeatIds = array_values(array_intersect($this->selectedSeatIds, $availableSeatIds));

        if (count($selectedSeatIds) !== (int) $this->passengerCount) {
            $this->addError('selectedSeatIds', 'Jumlah kursi yang dipilih harus sama dengan jumlah penumpang.');

            return;
        }

        $fare = RouteFare::where('travel_route_id', $trip->travel_route_id)->where('origin_stop_id', $originStop->id)->where('destination_stop_id', $destinationStop->id)->where('is_active', true)->first();

        $unitCost = $fare->cost ?? 0;

        $booking = DB::transaction(function () use ($trip, $originStop, $destinationStop, $fare, $unitCost, $selectedSeatIds, $bookingSetting) {
            $booking = Booking::create([
                'trip_id' => $trip->id,
                'origin_stop_id' => $originStop->id,
                'destination_stop_id' => $destinationStop->id,
                'route_fare_id' => $fare?->id,
                'customer_name' => $this->customerName,
                'phone' => $this->phone,
                'passenger_count' => $this->passengerCount,
                'total_cost' => $unitCost * $this->passengerCount,
                'status' => $bookingSetting->default_status ?? 'pending',
            ]);

            foreach ($selectedSeatIds as $seatId) {
                BookingSeat::create(['booking_id' => $booking->id, 'vehicle_seat_id' => $seatId]);
            }

            return $booking;
        });

        $this->modalOpen = false;
        $this->resetForm();
        session()->flash('toast', ['type' => 'success', 'message' => "Booking {$booking->booking_code} berhasil dibuat."]);
    }

    public function confirmPayment(int $id): void
    {
        $booking = Booking::where('status', 'pending')->findOrFail($id);
        $booking->update([
            'status' => 'confirmed',
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
        ]);

        session()->flash('toast', ['type' => 'success', 'message' => "Pembayaran {$booking->booking_code} berhasil dikonfirmasi."]);
    }

    public function confirmCancel(int $id): void
    {
        $this->cancelId = $id;
        $this->confirmCancelOpen = true;
    }

    public function cancelBooking(): void
    {
        $booking = Booking::findOrFail($this->cancelId);
        $booking->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        $this->confirmCancelOpen = false;
        $this->cancelId = null;
        session()->flash('toast', ['type' => 'success', 'message' => "Booking {$booking->booking_code} berhasil dibatalkan."]);
    }

    public function logout(): void
    {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        $this->redirectRoute('login', navigate: true);
    }

    private function resetForm(): void
    {
        $this->reset(['modalOpen', 'tripId', 'originStopId', 'destinationStopId', 'customerName', 'phone', 'selectedSeatIds']);
        $this->passengerCount = 1;
        $this->resetValidation();
    }

    public function render(): mixed
    {
        $trip = $this->tripId ? Trip::with('travelRoute')->find($this->tripId) : null;
        $stops = $trip ? RouteStop::with('outlet')->where('travel_route_id', $trip->travel_route_id)->orderBy('stop_sequence')->get() : collect();
        $originStop = $this->originStopId ? $stops->firstWhere('id', $this->originStopId) : null;
        $destinationStop = $this->destinationStopId ? $stops->firstWhere('id', $this->destinationStopId) : null;

        $fare = null;
        $availableSeats = collect();
        $allSeats = collect();
        $occupiedSeatIds = [];

        if ($trip && $originStop && $destinationStop && $originStop->stop_sequence < $destinationStop->stop_sequence) {
            $fare = RouteFare::where('travel_route_id', $trip->travel_route_id)->where('origin_stop_id', $originStop->id)->where('destination_stop_id', $destinationStop->id)->where('is_active', true)->first();

            $availableSeats = Booking::availableSeatsForSegment($trip, $originStop, $destinationStop);
            $allSeats = VehicleSeat::where('vehicle_id', $trip->vehicle_id)->where('is_active', true)->orderBy('seat_row')->orderBy('seat_column')->get();
            $occupiedSeatIds = Booking::seatIdsInUseForSegment($trip, $originStop, $destinationStop);
        }

        return view('pages.booking.⚡booking', [
            'trips' => Trip::whereIn('status', ['scheduled', 'boarding'])
                ->with(['travelRoute.originCity', 'travelRoute.destinationCity', 'vehicle'])
                ->orderBy('departure_date')
                ->get(),
            'stops' => $stops,
            'fare' => $fare,
            'availableSeats' => $availableSeats,
            'allSeats' => $allSeats,
            'occupiedSeatIds' => $occupiedSeatIds,
            'bookings' => Booking::with(['trip.travelRoute.originCity', 'trip.travelRoute.destinationCity', 'originStop.outlet', 'destinationStop.outlet', 'seats.vehicleSeat'])
                ->when($this->search !== '', fn($query) => $query->where(fn($q) => $q->where('booking_code', 'like', '%' . $this->search . '%')->orWhere('customer_name', 'like', '%' . $this->search . '%')))
                ->when($this->statusFilter !== '', fn($query) => $query->where('status', $this->statusFilter))
                ->latest()
                ->paginate(10),
            'stats' => [
                'total' => Booking::count(),
                'pending' => Booking::where('status', 'pending')->count(),
                'confirmed' => Booking::where('status', 'confirmed')->count(),
                'completed' => Booking::where('status', 'completed')->count(),
            ],
        ]);
    }
};
?>

<div>
    @if (session('toast'))
        <div
            class="mb-6 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m5 12 4 4L19 6" />
            </svg>
            {{ session('toast.message') }}
        </div>
    @endif

    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-brand-600">Booking</p>
            <h2 class="mt-2 text-3xl font-extrabold text-slate-900">Daftar Booking</h2>
            <p class="mt-2 text-sm text-slate-500">Daftarkan penumpang pada trip yang tersedia dan kelola pembayarannya.
            </p>
        </div>
        <button type="button" wire:click="openCreate" wire:loading.attr="disabled"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-brand-500/20 hover:bg-brand-600">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12h14" />
            </svg>
            <span wire:loading.remove wire:target="openCreate">Booking Baru</span>
            <span wire:loading wire:target="openCreate">Membuka...</span>
        </button>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['label' => 'Total Booking', 'value' => $stats['total'], 'color' => 'brand'], ['label' => 'Menunggu Pembayaran', 'value' => $stats['pending'], 'color' => 'amber'], ['label' => 'Dikonfirmasi', 'value' => $stats['confirmed'], 'color' => 'blue'], ['label' => 'Selesai', 'value' => $stats['completed'], 'color' => 'green']] as $stat)
            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-500">{{ $stat['label'] }}</p>
                    <span class="h-2.5 w-2.5 rounded-full bg-{{ $stat['color'] }}-500"></span>
                </div>
                <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-100 p-4 lg:flex-row lg:items-center lg:justify-between">
            <h3 class="font-extrabold text-slate-900">Daftar Booking</h3>
            <div class="flex flex-col gap-3 sm:flex-row">
                <input wire:model.live.debounce.300ms="search" placeholder="Cari nomor booking atau penumpang..."
                    class="rounded-xl border px-4 py-2.5 text-sm sm:w-72">
                <select wire:model.live="statusFilter" class="rounded-xl border px-4 py-2.5 text-sm">
                    <option value="">Semua status</option>
                    <option value="pending">Menunggu pembayaran</option>
                    <option value="confirmed">Dikonfirmasi</option>
                    <option value="completed">Selesai</option>
                    <option value="cancelled">Dibatalkan</option>
                </select>
            </div>
        </div>

        <div class="relative overflow-x-auto">
            <div wire:loading wire:target="search,statusFilter,confirmPayment,cancelBooking"
                class="absolute inset-0 z-10 flex items-center justify-center bg-white/70">
                <span class="rounded-xl bg-white px-4 py-3 text-sm font-semibold shadow-lg">Memuat data...</span>
            </div>
            <table class="w-full min-w-250 text-left text-sm">
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
                    @forelse ($bookings as $booking)
                        @php($statusMap = ['pending' => ['label' => 'Menunggu pembayaran', 'class' => 'bg-amber-50 text-amber-700'], 'confirmed' => ['label' => 'Dikonfirmasi', 'class' => 'bg-blue-50 text-blue-700'], 'completed' => ['label' => 'Selesai', 'class' => 'bg-green-50 text-green-700'], 'cancelled' => ['label' => 'Dibatalkan', 'class' => 'bg-red-50 text-red-700']])
                        <tr wire:key="booking-{{ $booking->id }}">
                            <td class="px-6 py-4 font-bold text-slate-900">{{ $booking->booking_code }}</td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-800">{{ $booking->customer_name }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $booking->passenger_count }} penumpang</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold">{{ $booking->originStop->outlet->name }} &rarr;
                                    {{ $booking->destinationStop->outlet->name }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $booking->trip->trip_code ?? '-' }} ·
                                    {{ $booking->trip->departure_date->format('d M Y') }}</p>
                            </td>
                            <td class="px-6 py-4 font-semibold">
                                {{ $booking->seats->pluck('vehicleSeat.seat_number')->join(', ') ?: '-' }}</td>
                            <td class="px-6 py-4 font-bold text-brand-700">
                                Rp{{ number_format($booking->total_cost, 0, ',', '.') }}</td>
                            <td class="px-6 py-4"><span
                                    class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusMap[$booking->status]['class'] ?? 'bg-slate-100 text-slate-600' }}">{{ $statusMap[$booking->status]['label'] ?? ucfirst($booking->status) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if ($booking->status === 'pending')
                                    <button type="button" wire:click="confirmPayment({{ $booking->id }})"
                                        wire:loading.attr="disabled"
                                        class="px-2 text-xs font-bold text-green-600">Konfirmasi Bayar</button>
                                @endif
                                @if (in_array($booking->status, ['pending', 'confirmed']))
                                    <button type="button" wire:click="confirmCancel({{ $booking->id }})"
                                        class="px-2 text-xs font-bold text-red-600">Batalkan</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">Belum ada booking.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-6 py-4">{{ $bookings->links() }}</div>
    </div>

    @if ($modalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4">
            <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-xl font-extrabold">Booking Baru</h2>
                <form wire:submit="save" class="mt-5 space-y-5">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Trip</label>
                        <select wire:model.live="tripId" class="w-full rounded-xl border px-4 py-3 text-sm">
                            <option value="">Pilih trip</option>
                            @foreach ($trips as $tripOption)
                                <option value="{{ $tripOption->id }}">
                                    {{ $tripOption->trip_code ?? '#' . $tripOption->id }} ·
                                    {{ $tripOption->travelRoute->originCity->name }} &rarr;
                                    {{ $tripOption->travelRoute->destinationCity->name }} ·
                                    {{ $tripOption->departure_date->format('d M Y') }}
                                    {{ substr($tripOption->departure_time, 0, 5) }}</option>
                            @endforeach
                        </select>
                        @error('tripId')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Titik naik</label>
                            <select wire:model.live="originStopId" class="w-full rounded-xl border px-4 py-3 text-sm"
                                @disabled(!$tripId)>
                                <option value="">Pilih titik naik</option>
                                @foreach ($stops as $stop)
                                    <option value="{{ $stop->id }}">{{ $stop->stop_sequence }}.
                                        {{ $stop->outlet->name }}</option>
                                @endforeach
                            </select>
                            @error('originStopId')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Titik turun</label>
                            <select wire:model.live="destinationStopId"
                                class="w-full rounded-xl border px-4 py-3 text-sm" @disabled(!$tripId)>
                                <option value="">Pilih titik turun</option>
                                @foreach ($stops as $stop)
                                    <option value="{{ $stop->id }}">{{ $stop->stop_sequence }}.
                                        {{ $stop->outlet->name }}</option>
                                @endforeach
                            </select>
                            @error('destinationStopId')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="rounded-xl border border-brand-100 bg-brand-50 p-3 text-sm">
                        <p class="font-bold text-brand-700">Estimasi tarif</p>
                        @if ($fare)
                            <p class="mt-1 text-slate-700">Rp{{ number_format($fare->cost, 0, ',', '.') }} / penumpang
                            </p>
                        @else
                            <p class="mt-1 text-slate-500">Pilih titik naik dan turun yang valid untuk melihat tarif.
                                Jika tarif belum diatur, hubungi Master Data &gt; Tarif Antar Titik.</p>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Nama penumpang</label>
                            <input wire:model="customerName" placeholder="Nama pemesan"
                                class="w-full rounded-xl border px-4 py-3 text-sm">
                            @error('customerName')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">No. telepon</label>
                            <input wire:model="phone" placeholder="0812xxxxxxx"
                                class="w-full rounded-xl border px-4 py-3 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Jumlah penumpang</label>
                            <input wire:model.live="passengerCount" type="number" min="1"
                                class="w-full rounded-xl border px-4 py-3 text-sm">
                            @error('passengerCount')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Pilih kursi
                            ({{ count($selectedSeatIds) }}/{{ $passengerCount }})</label>
                        @error('selectedSeatIds')
                            <p class="mb-2 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                        @if ($tripId && $originStopId && $destinationStopId)
                            <div class="grid grid-cols-4 gap-2 sm:grid-cols-6">
                                @forelse ($allSeats as $seat)
                                    @php($seatTaken = in_array($seat->id, $occupiedSeatIds, true))
                                    <button type="button" wire:click="toggleSeat({{ $seat->id }})"
                                        @disabled($seatTaken)
                                        class="rounded-xl border px-3 py-2 text-sm font-bold {{ $seatTaken ? 'cursor-not-allowed border-red-200 bg-red-50 text-red-500' : (in_array($seat->id, $selectedSeatIds) ? 'border-brand-500 bg-brand-500 text-white' : 'border-slate-200 text-slate-600 hover:border-brand-300') }}">
                                        <span class="block">{{ $seat->seat_number }}</span>
                                        @if ($seatTaken)
                                            <span class="mt-0.5 block text-[9px] font-semibold">Sudah diambil</span>
                                        @endif
                                    </button>
                                @empty
                                    <p class="col-span-full text-sm text-slate-500">Kursi armada belum tersedia.</p>
                                @endforelse
                            </div>
                            <div class="mt-3 flex flex-wrap gap-3 text-xs text-slate-500">
                                <span class="inline-flex items-center gap-1.5"><span
                                        class="h-3 w-3 rounded border border-slate-200 bg-white"></span>Tersedia</span>
                                <span class="inline-flex items-center gap-1.5"><span
                                        class="h-3 w-3 rounded bg-brand-500"></span>Dipilih</span>
                                <span class="inline-flex items-center gap-1.5"><span
                                        class="h-3 w-3 rounded border border-red-200 bg-red-50"></span>Sudah
                                    diambil</span>
                            </div>
                        @else
                            <p class="text-sm text-slate-500">Pilih trip, titik naik, dan titik turun terlebih dahulu.
                            </p>
                        @endif
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="$set('modalOpen', false)"
                            class="rounded-xl border px-5 py-3 text-sm font-bold">Batal</button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="rounded-xl bg-brand-500 px-5 py-3 text-sm font-bold text-white">
                            <span wire:loading.remove wire:target="save">Simpan Booking</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($confirmCancelOpen)
        <div class="fixed inset-0 z-60 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="font-extrabold">Batalkan booking?</h2>
                <p class="mt-2 text-sm text-slate-500">Kursi yang terpakai akan dilepas untuk segmen ini.</p>
                <div class="mt-5 flex justify-end gap-3">
                    <button wire:click="$set('confirmCancelOpen', false)"
                        class="rounded-xl border px-4 py-2 text-sm font-bold">Batal</button>
                    <button wire:click="cancelBooking"
                        class="rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white">Ya, Batalkan</button>
                </div>
            </div>
        </div>
    @endif
</div>
