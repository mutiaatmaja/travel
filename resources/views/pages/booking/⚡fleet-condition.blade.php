<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\Booking;
use App\Models\RouteStop;
use App\Models\Trip;
use App\Models\TripPositionReport;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts::admin', ['title' => 'Kondisi Armada', 'section' => 'Booking'])] class extends Component {
    public string $title = 'Kondisi Armada';

    public string $section = 'Booking';

    public ?int $selectedTripId = null;

    public function mount(): void
    {
        abort_unless($this->canViewFleetCondition(), 403);

        $this->selectedTripId = $this->visibleTripsQuery()
            ->orderBy('departure_date')
            ->orderBy('departure_time')
            ->value('id');
    }

    public function updatedSelectedTripId(): void
    {
        if ($this->selectedTripId && ! $this->visibleTripsQuery()->whereKey($this->selectedTripId)->exists()) {
            $this->selectedTripId = null;
            abort(403);
        }

        $this->resetValidation();
    }

    public function reportNextStop(): void
    {
        $user = auth()->user();
        $trip = $this->visibleTripsQuery()
            ->with(['travelRoute.stops.outlet.city', 'currentStop'])
            ->findOrFail($this->selectedTripId);

        if (in_array($trip->status, ['completed', 'cancelled'], true)) {
            $this->addError('selectedTripId', 'Posisi tidak dapat diperbarui karena Trip sudah selesai atau dibatalkan.');

            return;
        }

        if ($user->hasPermission('fleet-position.report-own-trip')
            && $trip->driver_id === $user->id
            && ! in_array($trip->status, ['boarding', 'on_the_way'], true)) {
            $this->addError('selectedTripId', 'Supir hanya dapat melaporkan posisi Trip yang sedang boarding atau berjalan.');

            return;
        }

        $nextStop = $trip->travelRoute->stops->first(
            fn (RouteStop $stop): bool => $stop->stop_sequence > ($trip->currentStop?->stop_sequence ?? 0),
        );

        if (! $nextStop) {
            $this->addError('selectedTripId', 'Trip sudah berada di titik akhir rute.');

            return;
        }

        $canReportAny = $user->hasPermission('fleet-position.update-any');
        $isAssignedDriver = $user->hasPermission('fleet-position.report-own-trip')
            && $trip->driver_id === $user->id;
        $isAssignedCityAdmin = $user->hasPermission('fleet-position.update-assigned-city')
            && $user->assigned_city_id !== null
            && $nextStop->outlet->city_id === $user->assigned_city_id;

        if (! $canReportAny && ! $isAssignedDriver && ! $isAssignedCityAdmin) {
            $this->addError('selectedTripId', 'Akun ini hanya dapat melaporkan posisi pada wilayah atau Trip yang ditugaskan.');

            return;
        }

        DB::transaction(function () use ($trip, $nextStop, $user): void {
            $reportedAt = now();

            $trip->update([
                'current_stop_id' => $nextStop->id,
                'position_updated_at' => $reportedAt,
                'position_updated_by' => $user->id,
            ]);

            TripPositionReport::create([
                'trip_id' => $trip->id,
                'route_stop_id' => $nextStop->id,
                'reported_by' => $user->id,
                'reported_at' => $reportedAt,
            ]);
        });

        session()->flash('toast', ['type' => 'success', 'message' => "Posisi {$trip->trip_code} dilaporkan tiba di {$nextStop->outlet->name}."]);
    }

    public function logout(): void
    {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        $this->redirectRoute('login', navigate: true);
    }

    private function canViewFleetCondition(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('fleet-condition.view-all')
            || $user->hasPermission('fleet-condition.view-assigned-trips')
            || $user->hasPermission('fleet-condition.view-own-trips');
    }

    private function visibleTripsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();
        $query = Trip::query()->whereNotIn('status', ['completed', 'cancelled']);

        if ($user->hasPermission('fleet-condition.view-all')) {
            return $query;
        }

        if ($user->hasPermission('fleet-condition.view-own-trips')) {
            return $query->where('driver_id', $user->id)
                ->whereIn('status', ['boarding', 'on_the_way']);
        }

        if ($user->hasPermission('fleet-condition.view-assigned-trips') && $user->assigned_city_id) {
            return $query->whereHas('travelRoute.stops.outlet', fn ($outletQuery) =>
                $outletQuery->where('city_id', $user->assigned_city_id));
        }

        return $query->whereRaw('1 = 0');
    }

    public function render(): mixed
    {
        $trips = $this->visibleTripsQuery()
            ->with(['travelRoute.originCity', 'travelRoute.destinationCity', 'vehicle', 'driver', 'currentStop.outlet.city'])
            ->withCount(['bookings as active_bookings_count' => fn ($query) => $query->whereIn('status', Booking::ACTIVE_STATUSES)])
            ->orderBy('departure_date')
            ->orderBy('departure_time')
            ->get();

        $selectedTrip = $this->selectedTripId
            ? $this->visibleTripsQuery()->with([
                'travelRoute.stops.outlet.city',
                'vehicle',
                'driver',
                'currentStop.outlet.city',
                'positionUpdatedBy',
                'positionReports.reporter',
                'positionReports.routeStop.outlet.city',
                'bookings' => fn ($query) => $query->whereIn('status', Booking::ACTIVE_STATUSES)
                    ->with(['originStop', 'destinationStop']),
            ])->find($this->selectedTripId)
            : null;

        $stopFlows = $selectedTrip?->travelRoute?->stops->map(function (RouteStop $stop) use ($selectedTrip): array {
            $activeBookings = $selectedTrip->bookings;
            $onboardAfterStop = $activeBookings->filter(fn (Booking $booking): bool =>
                $booking->originStop->stop_sequence <= $stop->stop_sequence
                && $booking->destinationStop->stop_sequence > $stop->stop_sequence
            )->sum('passenger_count');

            return [
                'stop' => $stop,
                'boardings' => $activeBookings->where('origin_stop_id', $stop->id)->sum('passenger_count'),
                'alightings' => $activeBookings->where('destination_stop_id', $stop->id)->sum('passenger_count'),
                'onboard' => $onboardAfterStop,
                'available' => max((int) ($selectedTrip->vehicle?->seat_capacity ?? 0) - $onboardAfterStop, 0),
            ];
        }) ?? collect();

        $currentFlow = $stopFlows->first(fn (array $flow): bool => $flow['stop']->id === $selectedTrip?->current_stop_id);
        $nextStop = $selectedTrip?->travelRoute?->stops->first(
            fn (RouteStop $stop): bool => $stop->stop_sequence > ($selectedTrip->currentStop?->stop_sequence ?? 0),
        );

        $user = auth()->user();
        $canReportAny = $user->hasPermission('fleet-position.update-any');
        $canReportAsDriver = $selectedTrip
            && $user->hasPermission('fleet-position.report-own-trip')
            && $selectedTrip->driver_id === $user->id
            && in_array($selectedTrip->status, ['boarding', 'on_the_way'], true);
        $canReportAssignedCity = $selectedTrip
            && $nextStop
            && $user->hasPermission('fleet-position.update-assigned-city')
            && $user->assigned_city_id !== null
            && $nextStop->outlet->city_id === $user->assigned_city_id;

        return view('pages.booking.⚡fleet-condition', [
            'trips' => $trips,
            'selectedTrip' => $selectedTrip,
            'stopFlows' => $stopFlows,
            'currentFlow' => $currentFlow,
            'nextStop' => $nextStop,
            'canReportNextStop' => (bool) $nextStop && ($canReportAny || $canReportAsDriver || $canReportAssignedCity),
            'activeTripsCount' => $trips->count(),
            'movingTripsCount' => $trips->whereIn('status', ['boarding', 'on_the_way'])->count(),
        ]);
    }
};
?>

<div>
    @if (session('toast'))
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
            {{ session('toast.message') }}
        </div>
    @endif

    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-brand-600">Operasional</p>
            <h2 class="mt-2 text-3xl font-extrabold text-slate-900">Kondisi Armada</h2>
            <p class="mt-2 text-sm text-slate-500">Pantau okupansi segmen dan titik perjalanan berdasarkan Trip serta Booking.</p>
        </div>
        <label class="w-full sm:max-w-md">
            <span class="mb-1 block text-sm font-semibold text-slate-700">Pilih Trip</span>
            <select wire:model.live="selectedTripId" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm">
                <option value="">Pilih trip operasional</option>
                @foreach ($trips as $tripOption)
                    <option value="{{ $tripOption->id }}">{{ $tripOption->trip_code }} · {{ $tripOption->travelRoute->originCity->name }} → {{ $tripOption->travelRoute->destinationCity->name }} · {{ $tripOption->departure_date->format('d M Y') }} {{ substr($tripOption->departure_time, 0, 5) }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ([['label' => 'Trip operasional', 'value' => $activeTripsCount, 'detail' => 'Belum selesai atau dibatalkan'], ['label' => 'Sedang berjalan', 'value' => $movingTripsCount, 'detail' => 'Boarding atau dalam perjalanan'], ['label' => 'Penumpang pada segmen', 'value' => $currentFlow['onboard'] ?? '—', 'detail' => $selectedTrip?->currentStop ? 'Setelah titik terakhir dilaporkan' : 'Posisi trip belum dilaporkan']] as $stat)
            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
                <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $stat['value'] }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $stat['detail'] }}</p>
            </div>
        @endforeach
    </div>

    @if ($selectedTrip)
        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h3 class="text-xl font-extrabold text-slate-900">{{ $selectedTrip->trip_code }}</h3>
                        <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-700">{{ ucfirst(str_replace('_', ' ', $selectedTrip->status)) }}</span>
                    </div>
                    <p class="mt-2 text-sm text-slate-500">{{ $selectedTrip->vehicle?->code }} · {{ $selectedTrip->vehicle?->license_plate }} · Supir: {{ $selectedTrip->driver?->name ?? 'Belum ditentukan' }}</p>
                    <p class="mt-1 text-xs text-slate-400">Berangkat {{ $selectedTrip->departure_date->format('d M Y') }} pukul {{ substr($selectedTrip->departure_time, 0, 5) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-400">Titik terakhir dilaporkan</p>
                    @if ($selectedTrip->currentStop)
                        <p class="mt-1 font-extrabold text-slate-900">{{ $selectedTrip->currentStop->outlet->name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $selectedTrip->position_updated_at?->format('d M Y, H:i') }}</p>
                    @else
                        <p class="mt-1 font-bold text-slate-600">Posisi belum diperbarui</p>
                    @endif
                </div>
            </div>

            <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h4 class="font-extrabold text-slate-900">Lintasan dan arus penumpang</h4>
                    <p class="mt-1 text-xs text-slate-500">Penumpang aktif mencakup booking pending dan confirmed yang menahan kursi.</p>
                </div>
                <div class="sm:text-right">
                    @if ($canReportNextStop)
                        <p class="mb-2 text-xs text-slate-500">Laporan hanya dapat maju ke stop berikutnya.</p>
                        <button type="button" wire:click="reportNextStop" wire:loading.attr="disabled" class="rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-600 disabled:opacity-60">
                            <span wire:loading.remove wire:target="reportNextStop">Armada tiba di {{ $nextStop->outlet->city->name }}</span>
                            <span wire:loading wire:target="reportNextStop">Mencatat posisi...</span>
                        </button>
                    @elseif ($nextStop)
                        <p class="max-w-xs text-sm text-slate-500">Stop berikutnya {{ $nextStop->outlet->city->name }}. Akun ini tidak berwenang melaporkan posisi di titik tersebut.</p>
                    @else
                        <p class="text-sm font-semibold text-slate-500">Trip sudah mencapai titik akhir.</p>
                    @endif
                </div>
            </div>
            @error('selectedTripId') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror

            <div class="mt-6 overflow-x-auto pb-2">
                <div class="flex min-w-max items-start">
                    @foreach ($stopFlows as $index => $flow)
                        @php($isCurrent = $selectedTrip->current_stop_id === $flow['stop']->id)
                        <div class="w-48 shrink-0 pr-5" wire:key="trip-stop-{{ $flow['stop']->id }}">
                            <div class="flex items-center">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-extrabold {{ $isCurrent ? 'bg-brand-500 text-white ring-4 ring-brand-100' : ($selectedTrip->currentStop && $flow['stop']->stop_sequence < $selectedTrip->currentStop->stop_sequence ? 'bg-green-500 text-white' : 'bg-slate-200 text-slate-600') }}">{{ $flow['stop']->stop_sequence }}</span>
                                @if (!$loop->last)
                                    <span class="h-1 w-full {{ $selectedTrip->currentStop && $flow['stop']->stop_sequence < $selectedTrip->currentStop->stop_sequence ? 'bg-green-400' : 'bg-slate-200' }}"></span>
                                @endif
                            </div>
                            <p class="mt-3 text-sm font-extrabold text-slate-900">{{ $flow['stop']->outlet->city->name }}</p>
                            <p class="mt-1 truncate text-xs text-slate-500">{{ $flow['stop']->outlet->name }}</p>
                            <div class="mt-3 space-y-1 text-xs">
                                <p class="font-semibold text-green-700">{{ $flow['boardings'] }} naik</p>
                                <p class="font-semibold text-red-600">{{ $flow['alightings'] }} turun</p>
                                <p class="border-t border-slate-100 pt-2 font-bold text-slate-700">{{ $flow['onboard'] }} di segmen berikutnya</p>
                                <p class="text-slate-500">{{ $flow['available'] }} kursi tersedia</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($currentFlow)
                <div class="mt-5 grid grid-cols-1 gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-3">
                    <div><p class="text-xs text-slate-500">Penumpang setelah titik ini</p><p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $currentFlow['onboard'] }} / {{ $selectedTrip->vehicle?->seat_capacity ?? 0 }}</p></div>
                    <div><p class="text-xs text-green-700">Naik di {{ $selectedTrip->currentStop->outlet->city->name }}</p><p class="mt-1 text-xl font-extrabold text-green-800">{{ $currentFlow['boardings'] }}</p></div>
                    <div><p class="text-xs text-red-700">Turun di {{ $selectedTrip->currentStop->outlet->city->name }}</p><p class="mt-1 text-xl font-extrabold text-red-800">{{ $currentFlow['alightings'] }}</p></div>
                </div>
            @endif

            @if (auth()->user()->hasPermission('fleet-position.view-history'))
                <div class="mt-6 border-t border-slate-100 pt-5">
                    <h4 class="text-sm font-extrabold text-slate-900">Riwayat laporan posisi</h4>
                    <div class="mt-3 divide-y divide-slate-100">
                        @forelse ($selectedTrip->positionReports->take(5) as $report)
                            <div class="flex flex-col gap-1 py-3 text-sm sm:flex-row sm:items-center sm:justify-between" wire:key="position-report-{{ $report->id }}">
                                <p class="font-semibold text-slate-800">{{ $report->routeStop->outlet->city->name }} · dilaporkan oleh {{ $report->reporter?->name ?? 'Akun dihapus' }}</p>
                                <time class="text-xs text-slate-500">{{ $report->reported_at->format('d M Y, H:i') }}</time>
                            </div>
                        @empty
                            <p class="py-3 text-sm text-slate-500">Belum ada laporan posisi untuk Trip ini.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </section>

        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5">
                <h3 class="font-extrabold text-slate-900">Trip operasional lainnya</h3>
                <p class="mt-1 text-xs text-slate-500">Pilih Trip lain untuk melihat kondisi dan memperbarui titik terakhir.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($trips as $tripOption)
                    <button type="button" wire:key="active-trip-{{ $tripOption->id }}" wire:click="$set('selectedTripId', {{ $tripOption->id }})"
                        class="flex w-full flex-col gap-2 px-5 py-4 text-left transition hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between {{ $selectedTrip->id === $tripOption->id ? 'bg-brand-50/60' : '' }}">
                        <span><span class="font-bold text-slate-900">{{ $tripOption->trip_code }}</span><span class="ml-2 text-sm text-slate-600">{{ $tripOption->travelRoute->originCity->name }} → {{ $tripOption->travelRoute->destinationCity->name }}</span></span>
                        <span class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500"><span>{{ $tripOption->currentStop?->outlet->city->name ?? 'Posisi belum diperbarui' }}</span><span>{{ $tripOption->active_bookings_count }} booking aktif</span><span>{{ ucfirst(str_replace('_', ' ', $tripOption->status)) }}</span></span>
                    </button>
                @empty
                    <p class="p-6 text-center text-sm text-slate-500">Belum ada Trip operasional.</p>
                @endforelse
            </div>
        </section>
    @else
        <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <h3 class="font-extrabold text-slate-900">Belum ada Trip operasional</h3>
            <p class="mt-2 text-sm text-slate-500">Buat Jadwal Trip terlebih dahulu; Trip berstatus selesai atau dibatalkan tidak ditampilkan di sini.</p>
        </div>
    @endif
</div>