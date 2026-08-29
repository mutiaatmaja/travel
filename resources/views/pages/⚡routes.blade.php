<?php

use App\Models\City;
use App\Models\Outlet;
use App\Models\RouteStop;
use App\Models\TravelRoute;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] class extends Component {
    use WithPagination;
    public string $title = 'Rute';
    public string $section = 'Master Data';
    public string $search = '';
    public bool $modalOpen = false;
    public bool $confirmDeleteOpen = false;
    public ?int $editingId = null;
    public ?int $deleteId = null;
    public ?int $originCityId = null;
    public ?int $destinationCityId = null;
    public string $code = '';
    public string $name = '';
    public int $duration = 240;
    public int $cost = 0;
    public ?float $distance = null;
    public array $selectedStops = [];
    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    public function openCreate(): void
    {
        $this->resetForm();
        $this->modalOpen = true;
    }
    public function openEdit(int $id): void
    {
        $route = TravelRoute::with('stops')->findOrFail($id);
        $this->editingId = $id;
        $this->code = $route->code;
        $this->name = $route->name;
        $this->originCityId = $route->origin_city_id;
        $this->destinationCityId = $route->destination_city_id;
        $this->duration = $route->estimated_duration_minutes;
        $this->cost = (int) $route->cost;
        $this->distance = $route->distance_km;
        $this->selectedStops = $route->stops->sortBy('stop_sequence')->pluck('outlet_id')->map(fn(int $id): string => (string) $id)->all();
        $this->resetValidation();
        $this->modalOpen = true;
    }
    public function save(): void
    {
        $this->validate(['code' => ['required', Rule::unique('travel_routes', 'code')->ignore($this->editingId)], 'name' => ['required', 'max:255'], 'originCityId' => ['required', 'exists:cities,id'], 'destinationCityId' => ['required', 'different:originCityId', 'exists:cities,id'], 'duration' => ['required', 'integer', 'min:1'], 'cost' => ['required', 'integer', 'min:0'], 'distance' => ['nullable', 'numeric', 'min:0'], 'selectedStops' => ['required', 'array', 'min:2'], 'selectedStops.*' => ['integer', 'distinct', 'exists:outlets,id']]);
        $route = TravelRoute::updateOrCreate(['id' => $this->editingId], ['code' => strtoupper($this->code), 'name' => $this->name, 'origin_city_id' => $this->originCityId, 'destination_city_id' => $this->destinationCityId, 'estimated_duration_minutes' => $this->duration, 'distance_km' => $this->distance, 'cost' => $this->cost, 'is_active' => true]);
        RouteStop::where('travel_route_id', $route->id)->delete();
        foreach ($this->selectedStops as $index => $outletId) {
            RouteStop::create(['travel_route_id' => $route->id, 'stop_sequence' => $index + 1, 'outlet_id' => $outletId, 'arrival_offset_minutes' => $index * 60, 'departure_offset_minutes' => $index * 60 + 10, 'is_boarding_allowed' => true, 'is_dropoff_allowed' => true]);
        }
        $message = $this->editingId ? 'Rute berhasil diperbarui.' : 'Rute berhasil ditambahkan.';
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
        TravelRoute::findOrFail($this->deleteId)->delete();
        $this->confirmDeleteOpen = false;
        $this->deleteId = null;
        session()->flash('toast', ['type' => 'success', 'message' => 'Rute berhasil dihapus.']);
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
        $this->reset(['modalOpen', 'editingId', 'deleteId', 'originCityId', 'destinationCityId', 'code', 'name', 'distance', 'cost', 'selectedStops']);
        $this->duration = 240;
        $this->cost = 0;
        $this->resetValidation();
    }
    public function render(): mixed
    {
        return view('pages.⚡routes', [
            'routes' => TravelRoute::with(['originCity', 'destinationCity', 'stops.outlet'])
                ->when($this->search !== '', fn($q) => $q->where('name', 'like', '%' . $this->search . '%')->orWhere('code', 'like', '%' . $this->search . '%'))
                ->latest()
                ->paginate(10),
            'cities' => City::orderBy('name')->get(),
            'outlets' => Outlet::orderBy('name')->get(),
        ]);
    }
};
?>
<div>
    @include('pages.partials.crud-header', [
        'heading' => 'Rute',
        'description' => 'Kelola rute dua arah dan urutan outlet pemberhentian.',
    ])
    <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="font-extrabold">Daftar Rute</h3><input wire:model.live.debounce.300ms="search"
                placeholder="Cari rute..." class="w-full rounded-xl border px-4 py-2.5 text-sm sm:max-w-xs">
        </div>
        <div class="relative overflow-x-auto">
            <div wire:loading wire:target="search,save,openEdit,confirmDelete,delete"
                class="absolute inset-0 z-10 flex items-center justify-center bg-white/70"><span
                    class="rounded-xl bg-white px-4 py-3 text-sm font-semibold shadow-lg">Memuat data...</span></div>
            <table class="w-full min-w-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Kode</th>
                        <th class="px-6 py-4">Rute utama</th>
                        <th class="px-6 py-4">Urutan stop outlet</th>
                        <th class="px-6 py-4">Durasi</th>
                        <th class="px-6 py-4">Biaya</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($routes as $route)
                        <tr>
                            <td class="px-6 py-4 font-bold">{{ $route->code }}</td>
                            <td class="px-6 py-4">{{ $route->originCity->name }} <span
                                    class="text-brand-500">&rarr;</span> {{ $route->destinationCity->name }}</td>
                            <td class="px-6 py-4">
                                <div class="flex max-w-lg flex-wrap items-center gap-1.5">
                                    @foreach ($route->stops as $stop)
                                        <span
                                            class="rounded-lg bg-brand-50 px-2.5 py-1.5 text-xs font-semibold text-brand-700"><span
                                                class="mr-1 text-brand-500">{{ $stop->stop_sequence }}.</span>{{ $stop->outlet->name }}</span>
                                        @if (!$loop->last)
                                            <span class="text-slate-300">&rarr;</span>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4">{{ $route->estimated_duration_minutes }} menit</td>
                            <td class="px-6 py-4">Rp{{ number_format($route->cost, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right"><button wire:click="openEdit({{ $route->id }})"
                                    class="px-2 text-xs font-bold text-brand-600">Edit</button><button
                                    wire:click="confirmDelete({{ $route->id }})"
                                    class="px-2 text-xs font-bold text-red-600">Hapus</button></td>
                    </tr>@empty<tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">Belum ada rute.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-6 py-4">{{ $routes->links() }}</div>
    </div>
    @if ($modalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4">
            <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-xl font-extrabold">{{ $editingId ? 'Edit' : 'Tambah' }} Rute</h2>
                <form wire:submit="save" class="mt-5 space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2"><input wire:model="code" placeholder="Kode rute"
                            class="rounded-xl border px-4 py-3 text-sm"><input wire:model="name" placeholder="Nama rute"
                            class="rounded-xl border px-4 py-3 text-sm"><select wire:model="originCityId"
                            class="rounded-xl border px-4 py-3 text-sm">
                            <option value="">Kota asal</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city->id }}">{{ $city->name }}</option>
                            @endforeach
                        </select>
                        <select wire:model="destinationCityId" class="rounded-xl border px-4 py-3 text-sm">
                            <option value="">Kota tujuan</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city->id }}">{{ $city->name }}</option>
                            @endforeach
                        </select>
                        <input wire:model="duration" type="number" placeholder="Durasi (menit)"
                            class="rounded-xl border px-4 py-3 text-sm"><input wire:model="cost" type="number"
                            min="0" step="1000" placeholder="Biaya (Rp)"
                            class="rounded-xl border px-4 py-3 text-sm">
                        <input wire:model="distance" type="number" step="0.01" placeholder="Jarak (km)"
                            class="rounded-xl border px-4 py-3 text-sm sm:col-span-2">
                    </div>
                    <div>
                        <p class="mb-2 text-sm font-semibold">Urutan stop outlet</p>
                        <div
                            class="grid max-h-40 grid-cols-1 gap-2 overflow-y-auto rounded-xl border p-3 sm:grid-cols-2">
                            @foreach ($outlets as $outlet)
                                <label class="flex gap-2 text-sm"><input type="checkbox" wire:model="selectedStops"
                                        value="{{ $outlet->id }}">{{ $outlet->name }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex justify-end gap-3"><button type="button" wire:click="$set('modalOpen', false)"
                            class="rounded-xl border px-5 py-3 text-sm font-bold">Batal</button><button
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
                <h2 class="font-extrabold">Hapus rute?</h2>
                <div class="mt-5 flex justify-end gap-3"><button wire:click="$set('confirmDeleteOpen', false)"
                        class="rounded-xl border px-4 py-2 text-sm font-bold">Batal</button><button wire:click="delete"
                        class="rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white">Hapus</button></div>
            </div>
        </div>
    @endif
</div>
