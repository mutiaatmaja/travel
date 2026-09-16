<?php

use App\Models\RouteFare;
use App\Models\RouteStop;
use App\Models\TravelRoute;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] class extends Component {
    use WithPagination;

    public string $title = 'Tarif Antar Titik';

    public string $section = 'Master Data';

    public string $search = '';

    public bool $modalOpen = false;

    public bool $confirmDeleteOpen = false;

    public ?int $editingId = null;

    public ?int $deleteId = null;

    public ?int $routeId = null;

    public ?int $originStopId = null;

    public ?int $destinationStopId = null;

    public int $cost = 0;

    public bool $isActive = true;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRouteId(): void
    {
        $this->originStopId = null;
        $this->destinationStopId = null;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->modalOpen = true;
    }

    public function openEdit(int $id): void
    {
        $fare = RouteFare::findOrFail($id);
        $this->editingId = $fare->id;
        $this->routeId = $fare->travel_route_id;
        $this->originStopId = $fare->origin_stop_id;
        $this->destinationStopId = $fare->destination_stop_id;
        $this->cost = $fare->cost;
        $this->isActive = $fare->is_active;
        $this->resetValidation();
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $this->validate([
            'routeId' => ['required', 'exists:travel_routes,id'],
            'originStopId' => ['required', 'exists:route_stops,id'],
            'destinationStopId' => ['required', 'different:originStopId', 'exists:route_stops,id'],
            'cost' => ['required', 'integer', 'min:0'],
        ]);

        $originStop = RouteStop::where('travel_route_id', $this->routeId)->findOrFail($this->originStopId);
        $destinationStop = RouteStop::where('travel_route_id', $this->routeId)->findOrFail($this->destinationStopId);

        if ($originStop->stop_sequence >= $destinationStop->stop_sequence) {
            $this->addError('destinationStopId', 'Titik tujuan harus berada setelah titik asal.');

            return;
        }

        $duplicate = RouteFare::query()->where('travel_route_id', $this->routeId)->where('origin_stop_id', $this->originStopId)->where('destination_stop_id', $this->destinationStopId)->when($this->editingId, fn($query) => $query->where('id', '<>', $this->editingId))->exists();

        if ($duplicate) {
            $this->addError('destinationStopId', 'Tarif untuk pasangan titik ini sudah ada.');

            return;
        }

        RouteFare::updateOrCreate(
            ['id' => $this->editingId],
            [
                'travel_route_id' => $this->routeId,
                'origin_stop_id' => $this->originStopId,
                'destination_stop_id' => $this->destinationStopId,
                'cost' => $this->cost,
                'is_active' => $this->isActive,
            ],
        );

        $message = $this->editingId ? 'Tarif antar titik berhasil diperbarui.' : 'Tarif antar titik berhasil ditambahkan.';
        $this->modalOpen = false;
        $this->resetForm();
        session()->flash('toast', ['type' => 'success', 'message' => $message]);
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->confirmDeleteOpen = true;
    }

    public function delete(): void
    {
        RouteFare::findOrFail($this->deleteId)->delete();
        $this->confirmDeleteOpen = false;
        $this->deleteId = null;
        session()->flash('toast', ['type' => 'success', 'message' => 'Tarif antar titik berhasil dihapus.']);
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
        $this->reset(['modalOpen', 'editingId', 'deleteId', 'routeId', 'originStopId', 'destinationStopId', 'cost', 'isActive']);
        $this->cost = 0;
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render(): mixed
    {
        return view('pages.master-data.⚡route-fares', [
            'fares' => RouteFare::with(['travelRoute', 'originStop.outlet', 'destinationStop.outlet'])
                ->when($this->search !== '', fn($query) => $query->whereHas('travelRoute', fn($route) => $route->where('name', 'like', '%' . $this->search . '%')))
                ->latest()
                ->paginate(10),
            'routes' => TravelRoute::with('stops.outlet')->where('is_active', true)->orderBy('name')->get(),
            'stops' => $this->routeId ? RouteStop::with('outlet')->where('travel_route_id', $this->routeId)->orderBy('stop_sequence')->get() : collect(),
        ]);
    }
};
?>

<div>
    @include('pages.partials.crud-header', [
        'heading' => 'Tarif Antar Titik',
        'description' => 'Atur biaya berdasarkan titik naik dan titik turun dalam satu rute.',
    ])

    <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="font-extrabold">Daftar Tarif</h3>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <input wire:model.live.debounce.300ms="search" placeholder="Cari nama rute..."
                    class="w-full rounded-xl border px-4 py-2.5 text-sm sm:max-w-xs">
                <button type="button" wire:click="openCreate" wire:loading.attr="disabled"
                    class="rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-bold text-white">
                    <span wire:loading.remove wire:target="openCreate">Tambah</span>
                    <span wire:loading wire:target="openCreate">Membuka...</span>
                </button>
            </div>
        </div>
        <div class="relative overflow-x-auto">
            <div wire:loading wire:target="search,save,openEdit,confirmDelete,delete"
                class="absolute inset-0 z-10 flex items-center justify-center bg-white/70">
                <span class="rounded-xl bg-white px-4 py-3 text-sm font-semibold shadow-lg">Memuat data...</span>
            </div>
            <table class="w-full min-w-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Rute</th>
                        <th class="px-6 py-4">Asal</th>
                        <th class="px-6 py-4">Tujuan</th>
                        <th class="px-6 py-4">Biaya</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($fares as $fare)
                        <tr>
                            <td class="px-6 py-4 font-bold">{{ $fare->travelRoute->name }}</td>
                            <td class="px-6 py-4">{{ $fare->originStop->outlet->name }}</td>
                            <td class="px-6 py-4">{{ $fare->destinationStop->outlet->name }}</td>
                            <td class="px-6 py-4 font-bold text-brand-700">
                                Rp{{ number_format($fare->cost, 0, ',', '.') }}</td>
                            <td class="px-6 py-4"><span
                                    class="rounded-full {{ $fare->is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-600' }} px-2.5 py-1 text-xs font-bold">{{ $fare->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="px-6 py-4 text-right"><button wire:click="openEdit({{ $fare->id }})"
                                    class="px-2 text-xs font-bold text-brand-600">Edit</button><button
                                    wire:click="confirmDelete({{ $fare->id }})"
                                    class="px-2 text-xs font-bold text-red-600">Hapus</button></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">Belum ada tarif antar
                                titik.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-6 py-4">{{ $fares->links() }}</div>
    </div>

    @if ($modalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4">
            <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-xl font-extrabold">{{ $editingId ? 'Edit' : 'Tambah' }} Tarif Antar Titik</h2>
                <form wire:submit="save" class="mt-5 space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Rute</label>
                        <select wire:model.live="routeId" class="w-full rounded-xl border px-4 py-3 text-sm">
                            <option value="">Pilih rute</option>
                            @foreach ($routes as $route)
                                <option value="{{ $route->id }}">{{ $route->name }}</option>
                            @endforeach
                        </select>
                        @error('routeId')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Titik asal</label>
                            <select wire:model="originStopId" class="w-full rounded-xl border px-4 py-3 text-sm"
                                @disabled(!$routeId)>
                                <option value="">Pilih titik asal</option>
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
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Titik tujuan</label>
                            <select wire:model="destinationStopId" class="w-full rounded-xl border px-4 py-3 text-sm"
                                @disabled(!$routeId)>
                                <option value="">Pilih titik tujuan</option>
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
                    <div><label class="mb-1 block text-sm font-semibold text-slate-700">Biaya</label><input
                            wire:model="cost" type="number" min="0" step="1000" placeholder="150000"
                            class="w-full rounded-xl border px-4 py-3 text-sm">
                        @error('cost')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <label
                        class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-3 text-sm text-slate-700"><input
                            wire:model="isActive" type="checkbox" class="h-4 w-4"><span class="font-medium">Tarif
                            aktif</span></label>
                    <div class="flex justify-end gap-3"><button type="button" wire:click="$set('modalOpen', false)"
                            class="rounded-xl border px-5 py-3 text-sm font-bold">Batal</button><button type="submit"
                            wire:loading.attr="disabled"
                            class="rounded-xl bg-brand-500 px-5 py-3 text-sm font-bold text-white"><span
                                wire:loading.remove wire:target="save">Simpan</span><span wire:loading
                                wire:target="save">Menyimpan...</span></button></div>
                </form>
            </div>
        </div>
    @endif

    @if ($confirmDeleteOpen)
        <div class="fixed inset-0 z-60 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="font-extrabold">Hapus tarif antar titik?</h2>
                <div class="mt-5 flex justify-end gap-3"><button wire:click="$set('confirmDeleteOpen', false)"
                        class="rounded-xl border px-4 py-2 text-sm font-bold">Batal</button><button wire:click="delete"
                        class="rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white">Hapus</button></div>
            </div>
        </div>
    @endif
</div>
