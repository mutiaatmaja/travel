<?php

use App\Models\City;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] class extends Component {
    use WithPagination;

    public string $title = 'Wilayah';
    public string $section = 'Master Data';
    public string $search = '';
    public bool $modalOpen = false;
    public bool $confirmDeleteOpen = false;
    public ?int $editingId = null;
    public ?int $deleteId = null;
    public string $code = '';
    public string $name = '';
    public string $province = '';
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
        $city = City::findOrFail($id);
        $this->editingId = $city->id;
        $this->code = $city->code;
        $this->name = $city->name;
        $this->province = $city->province ?? '';
        $this->isActive = $city->is_active;
        $this->resetValidation();
        $this->modalOpen = true;
    }
    public function save(): void
    {
        $this->validate(['code' => ['required', 'string', 'max:20', Rule::unique('cities', 'code')->ignore($this->editingId)], 'name' => ['required', 'string', 'max:255', Rule::unique('cities', 'name')->ignore($this->editingId)], 'province' => ['nullable', 'string', 'max:255']]);
        City::updateOrCreate(['id' => $this->editingId], ['code' => strtoupper($this->code), 'name' => $this->name, 'province' => $this->province, 'is_active' => $this->isActive]);
        $this->modalOpen = false;
        $this->resetForm();
        session()->flash('toast', ['type' => 'success', 'message' => $this->editingId ? 'Wilayah berhasil diperbarui.' : 'Wilayah berhasil ditambahkan.']);
    }
    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->confirmDeleteOpen = true;
    }
    public function delete(): void
    {
        City::findOrFail($this->deleteId)->delete();
        $this->confirmDeleteOpen = false;
        $this->deleteId = null;
        session()->flash('toast', ['type' => 'success', 'message' => 'Wilayah berhasil dihapus.']);
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
        $this->reset(['modalOpen', 'editingId', 'deleteId', 'code', 'name', 'province']);
        $this->isActive = true;
        $this->resetValidation();
    }
    public function render(): mixed
    {
        return view('pages.⚡cities', [
            'cities' => City::when($this->search !== '', fn($q) => $q->where('name', 'like', '%' . $this->search . '%')->orWhere('code', 'like', '%' . $this->search . '%'))
                ->latest()
                ->paginate(10),
        ]);
    }
};
?>

<div>
    @include('pages.partials.crud-header', [
        'heading' => 'Wilayah',
        'description' => 'Kelola kota dan wilayah operasional.',
    ])
    <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="font-extrabold text-slate-900">Daftar Wilayah</h3><input wire:model.live.debounce.300ms="search"
                placeholder="Cari wilayah..."
                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none focus:border-brand-500 sm:max-w-xs">
        </div>
        <div class="relative overflow-x-auto">
            <div wire:loading wire:target="search,save,openEdit,confirmDelete,delete"
                class="absolute inset-0 z-10 flex items-center justify-center bg-white/70"><span
                    class="rounded-xl bg-white px-4 py-3 text-sm font-semibold shadow-lg">Memuat data...</span></div>
            <table class="w-full min-w-170 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Kode</th>
                        <th class="px-6 py-4">Nama</th>
                        <th class="px-6 py-4">Provinsi</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($cities as $city)
                        <tr>
                            <td class="px-6 py-4 font-bold">{{ $city->code }}</td>
                            <td class="px-6 py-4">{{ $city->name }}</td>
                            <td class="px-6 py-4">{{ $city->province ?: '-' }}</td>
                            <td class="px-6 py-4"><span
                                    class="rounded-full px-2.5 py-1 text-xs font-bold {{ $city->is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500' }}">{{ $city->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="px-6 py-4 text-right"><button wire:click="openEdit({{ $city->id }})"
                                    class="px-2 text-xs font-bold text-brand-600">Edit</button><button
                                    wire:click="confirmDelete({{ $city->id }})"
                                    class="px-2 text-xs font-bold text-red-600">Hapus</button></td>
                    </tr>@empty<tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">Belum ada wilayah.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-6 py-4">{{ $cities->links() }}</div>
    </div>
    @include('pages.partials.crud-modal', [
        'entity' => 'Wilayah',
        'fields' => ['code' => 'Kode', 'name' => 'Nama wilayah', 'province' => 'Provinsi'],
    ])
</div>
