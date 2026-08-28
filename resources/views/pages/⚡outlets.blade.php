<?php

use App\Models\City;
use App\Models\Outlet;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] class extends Component {
    use WithPagination;
    public string $title = 'Outlet';
    public string $section = 'Master Data';
    public string $search = '';
    public bool $modalOpen = false;
    public bool $confirmDeleteOpen = false;
    public ?int $editingId = null;
    public ?int $deleteId = null;
    public ?int $cityId = null;
    public string $code = '';
    public string $name = '';
    public string $address = '';
    public string $phone = '';
    public bool $isActive = true;

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
        $outlet = Outlet::findOrFail($id);
        $this->editingId = $id;
        $this->cityId = $outlet->city_id;
        $this->code = $outlet->code;
        $this->name = $outlet->name;
        $this->address = $outlet->address;
        $this->phone = $outlet->phone ?? '';
        $this->isActive = $outlet->is_active;
        $this->resetValidation();
        $this->modalOpen = true;
    }
    public function save(): void
    {
        $this->validate(['cityId' => ['required', 'exists:cities,id'], 'code' => ['required', Rule::unique('outlets', 'code')->ignore($this->editingId)], 'name' => ['required', 'string', 'max:255'], 'address' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:30']]);
        Outlet::updateOrCreate(['id' => $this->editingId], ['city_id' => $this->cityId, 'code' => strtoupper($this->code), 'name' => $this->name, 'address' => $this->address, 'phone' => $this->phone, 'is_active' => $this->isActive]);
        $message = $this->editingId ? 'Outlet berhasil diperbarui.' : 'Outlet berhasil ditambahkan.';
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
        Outlet::findOrFail($this->deleteId)->delete();
        $this->confirmDeleteOpen = false;
        $this->deleteId = null;
        session()->flash('toast', ['type' => 'success', 'message' => 'Outlet berhasil dihapus.']);
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
        $this->reset(['modalOpen', 'editingId', 'deleteId', 'cityId', 'code', 'name', 'address', 'phone']);
        $this->isActive = true;
        $this->resetValidation();
    }
    public function render(): mixed
    {
        return view('pages.⚡outlets', [
            'outlets' => Outlet::with('city')
                ->when($this->search !== '', fn($q) => $q->where('name', 'like', '%' . $this->search . '%')->orWhere('code', 'like', '%' . $this->search . '%'))
                ->latest()
                ->paginate(10),
            'cities' => City::orderBy('name')->get(),
        ]);
    }
};
?>

<div>
    @include('pages.partials.crud-header', [
        'heading' => 'Outlet',
        'description' => 'Kelola pool dan titik pemberhentian perjalanan.',
    ])
    <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="font-extrabold text-slate-900">Daftar Outlet</h3><input wire:model.live.debounce.300ms="search"
                placeholder="Cari outlet..."
                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none focus:border-brand-500 sm:max-w-xs">
        </div>
        <div class="relative overflow-x-auto">
            <div wire:loading wire:target="search,save,openEdit,confirmDelete,delete"
                class="absolute inset-0 z-10 flex items-center justify-center bg-white/70"><span
                    class="rounded-xl bg-white px-4 py-3 text-sm font-semibold shadow-lg">Memuat data...</span></div>
            <table class="w-full min-w-170 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Outlet</th>
                        <th class="px-6 py-4">Kota</th>
                        <th class="px-6 py-4">Alamat</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($outlets as $outlet)
                        <tr>
                            <td class="px-6 py-4">
                                <p class="font-bold">{{ $outlet->name }}</p>
                                <p class="text-xs text-slate-400">{{ $outlet->code }}</p>
                            </td>
                            <td class="px-6 py-4">{{ $outlet->city->name }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ $outlet->address }}</td>
                            <td class="px-6 py-4"><span
                                    class="rounded-full px-2.5 py-1 text-xs font-bold {{ $outlet->is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500' }}">{{ $outlet->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="px-6 py-4 text-right"><button wire:click="openEdit({{ $outlet->id }})"
                                    class="px-2 text-xs font-bold text-brand-600">Edit</button><button
                                    wire:click="confirmDelete({{ $outlet->id }})"
                                    class="px-2 text-xs font-bold text-red-600">Hapus</button></td>
                    </tr>@empty<tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">Belum ada outlet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-6 py-4">{{ $outlets->links() }}</div>
    </div>
    @if ($modalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                <h2 class="text-xl font-extrabold">{{ $editingId ? 'Edit' : 'Tambah' }} Outlet</h2>
                <form wire:submit="save" class="mt-5 space-y-4"><select wire:model="cityId"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                        <option value="">Pilih kota</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}">{{ $city->name }}</option>
                        @endforeach
                    </select><input wire:model="code" placeholder="Kode outlet"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"><input
                        wire:model="name" placeholder="Nama outlet"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"><input
                        wire:model="address" placeholder="Alamat"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"><input
                        wire:model="phone" placeholder="Telepon"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"><label
                        class="flex gap-2 text-sm"><input type="checkbox" wire:model="isActive"> Aktif</label>
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
                <h2 class="font-extrabold">Hapus outlet?</h2>
                <p class="mt-2 text-sm text-slate-500">Data yang dihapus tidak dapat dikembalikan.</p>
                <div class="mt-5 flex justify-end gap-3"><button wire:click="$set('confirmDeleteOpen', false)"
                        class="rounded-xl border px-4 py-2 text-sm font-bold">Batal</button><button wire:click="delete"
                        class="rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white"><span wire:loading.remove
                            wire:target="delete">Hapus</span><span wire:loading
                            wire:target="delete">Menghapus...</span></button></div>
            </div>
        </div>
    @endif
</div>
