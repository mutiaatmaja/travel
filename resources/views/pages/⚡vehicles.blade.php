<?php

use App\Models\Vehicle;
use App\Models\VehicleSeat;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] class extends Component {
    use WithPagination;
    public string $title = 'Armada';
    public string $section = 'Master Data';
    public string $search = '';
    public bool $modalOpen = false;
    public bool $confirmDeleteOpen = false;
    public ?int $editingId = null;
    public ?int $deleteId = null;
    public string $code = '';
    public string $licensePlate = '';
    public string $type = 'Minibus';
    public string $brand = '';
    public string $model = '';
    public int $seatCapacity = 10;
    public string $status = 'active';
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
        $vehicle = Vehicle::findOrFail($id);
        $this->editingId = $id;
        $this->code = $vehicle->code;
        $this->licensePlate = $vehicle->license_plate;
        $this->type = $vehicle->type;
        $this->brand = $vehicle->brand ?? '';
        $this->model = $vehicle->model ?? '';
        $this->seatCapacity = $vehicle->seat_capacity;
        $this->status = $vehicle->status;
        $this->resetValidation();
        $this->modalOpen = true;
    }
    public function save(): void
    {
        $this->validate(['code' => ['required', Rule::unique('vehicles', 'code')->ignore($this->editingId)], 'licensePlate' => ['required', Rule::unique('vehicles', 'license_plate')->ignore($this->editingId)], 'type' => ['required', 'max:50'], 'seatCapacity' => ['required', 'integer', 'min:1', 'max:50'], 'status' => ['required', 'in:active,maintenance,inactive']]);
        $vehicle = Vehicle::updateOrCreate(['id' => $this->editingId], ['code' => strtoupper($this->code), 'license_plate' => strtoupper($this->licensePlate), 'type' => $this->type, 'brand' => $this->brand, 'model' => $this->model, 'seat_capacity' => $this->seatCapacity, 'status' => $this->status]);
        for ($number = 1; $number <= $this->seatCapacity; $number++) {
            VehicleSeat::updateOrCreate(['vehicle_id' => $vehicle->id, 'seat_number' => (string) $number], ['seat_row' => (int) ceil($number / 2), 'seat_column' => $number % 2 === 0 ? 2 : 1, 'seat_type' => 'regular', 'is_active' => true]);
        }
        $message = $this->editingId ? 'Armada berhasil diperbarui.' : 'Armada berhasil ditambahkan.';
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
        Vehicle::findOrFail($this->deleteId)->delete();
        $this->confirmDeleteOpen = false;
        $this->deleteId = null;
        session()->flash('toast', ['type' => 'success', 'message' => 'Armada berhasil dihapus.']);
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
        $this->reset(['modalOpen', 'editingId', 'deleteId', 'code', 'licensePlate', 'brand', 'model']);
        $this->type = 'Minibus';
        $this->seatCapacity = 10;
        $this->status = 'active';
        $this->resetValidation();
    }
    public function render(): mixed
    {
        return view('pages.⚡vehicles', [
            'vehicles' => Vehicle::when($this->search !== '', fn($q) => $q->where('code', 'like', '%' . $this->search . '%')->orWhere('license_plate', 'like', '%' . $this->search . '%'))
                ->withCount('seats')
                ->latest()
                ->paginate(10),
        ]);
    }
};
?>
<div>
    @include('pages.partials.crud-header', [
        'heading' => 'Armada',
        'description' => 'Kelola kendaraan dan kapasitas kursi.',
    ])
    <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="font-extrabold">Daftar Armada</h3><input wire:model.live.debounce.300ms="search"
                placeholder="Cari kode atau plat nomor..."
                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm sm:max-w-xs">
        </div>
        <div class="relative overflow-x-auto">
            <div wire:loading wire:target="search,save,openEdit,confirmDelete,delete"
                class="absolute inset-0 z-10 flex items-center justify-center bg-white/70"><span
                    class="rounded-xl bg-white px-4 py-3 text-sm font-semibold shadow-lg">Memuat data...</span></div>
            <table class="w-full min-w-170 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Armada</th>
                        <th class="px-6 py-4">Kendaraan</th>
                        <th class="px-6 py-4">Kursi</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($vehicles as $vehicle)
                        <tr>
                            <td class="px-6 py-4">
                                <p class="font-bold">{{ $vehicle->code }}</p>
                                <p class="text-xs text-slate-400">{{ $vehicle->license_plate }}</p>
                            </td>
                            <td class="px-6 py-4">{{ $vehicle->brand }} {{ $vehicle->model }}<p
                                    class="text-xs text-slate-400">{{ $vehicle->type }}</p>
                            </td>
                            <td class="px-6 py-4">{{ $vehicle->seats_count }} kursi</td>
                            <td class="px-6 py-4"><span
                                    class="rounded-full bg-green-50 px-2.5 py-1 text-xs font-bold text-green-700">{{ ucfirst($vehicle->status) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right"><button wire:click="openEdit({{ $vehicle->id }})"
                                    class="px-2 text-xs font-bold text-brand-600">Edit</button><button
                                    wire:click="confirmDelete({{ $vehicle->id }})"
                                    class="px-2 text-xs font-bold text-red-600">Hapus</button></td>
                    </tr>@empty<tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">Belum ada armada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-6 py-4">{{ $vehicles->links() }}</div>
    </div>
    @if ($modalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-xl font-extrabold">{{ $editingId ? 'Edit' : 'Tambah' }} Armada</h2>
                <form wire:submit="save" class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2"><input wire:model="code"
                        placeholder="Kode armada" class="rounded-xl border px-4 py-3 text-sm"><input
                        wire:model="licensePlate" placeholder="Nomor polisi"
                        class="rounded-xl border px-4 py-3 text-sm"><input wire:model="type"
                        placeholder="Jenis kendaraan" class="rounded-xl border px-4 py-3 text-sm"><input
                        wire:model="brand" placeholder="Merek" class="rounded-xl border px-4 py-3 text-sm"><input
                        wire:model="model" placeholder="Model" class="rounded-xl border px-4 py-3 text-sm"><input
                        wire:model="seatCapacity" type="number" min="1" placeholder="Kapasitas kursi"
                        class="rounded-xl border px-4 py-3 text-sm"><select wire:model="status"
                        class="rounded-xl border px-4 py-3 text-sm">
                        <option value="active">Aktif</option>
                        <option value="maintenance">Perawatan</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                    <div class="flex justify-end gap-3 sm:col-span-2"><button type="button"
                            wire:click="$set('modalOpen', false)"
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
                <h2 class="font-extrabold">Hapus armada?</h2>
                <div class="mt-5 flex justify-end gap-3"><button wire:click="$set('confirmDeleteOpen', false)"
                        class="rounded-xl border px-4 py-2 text-sm font-bold">Batal</button><button wire:click="delete"
                        class="rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white">Hapus</button></div>
            </div>
        </div>
    @endif
</div>
