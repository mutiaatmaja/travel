<?php

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] class extends Component {
    use WithPagination;
    public string $title = 'Supir';
    public string $section = 'Master Data';
    public string $search = '';
    public bool $modalOpen = false;
    public bool $confirmDeleteOpen = false;
    public ?int $editingId = null;
    public ?int $deleteId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
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
        $driver = User::findOrFail($id);
        $this->editingId = $id;
        $this->name = $driver->name;
        $this->email = $driver->email;
        $this->resetValidation();
        $this->modalOpen = true;
    }
    public function save(): void
    {
        $this->validate(['name' => ['required', 'max:255'], 'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->editingId)], 'password' => [$this->editingId ? 'nullable' : 'required', 'min:8']]);
        $driver = $this->editingId ? User::findOrFail($this->editingId) : new User();
        $driver->name = $this->name;
        $driver->email = $this->email;
        if ($this->password !== '') {
            $driver->password = $this->password;
        }
        $driver->save();
        $driver->syncRoles(['supir']);
        $message = $this->editingId ? 'Supir berhasil diperbarui.' : 'Supir berhasil ditambahkan.';
        $this->modalOpen = false;
        $this->resetForm();
        session()->flash('toast', ['type' => 'success', 'message' => $message]);
    }
    public function confirmDelete(int $id): void
    {
        if ($id === auth()->id()) {
            session()->flash('toast', ['type' => 'error', 'message' => 'Akun yang sedang digunakan tidak dapat dihapus.']);
            return;
        }
        $this->deleteId = $id;
        $this->confirmDeleteOpen = true;
    }
    public function delete(): void
    {
        User::findOrFail($this->deleteId)->delete();
        $this->confirmDeleteOpen = false;
        $this->deleteId = null;
        session()->flash('toast', ['type' => 'success', 'message' => 'Supir berhasil dihapus.']);
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
        $this->reset(['modalOpen', 'editingId', 'deleteId', 'name', 'email', 'password']);
        $this->resetValidation();
    }
    public function render(): mixed
    {
        return view('pages.⚡drivers', [
            'drivers' => User::with('roles')
                ->whereHas('roles', fn($q) => $q->where('name', 'supir'))
                ->when(
                    $this->search !== '',
                    fn($q) => $q->where(function ($q): void {
                        $q->where('name', 'like', '%' . $this->search . '%')->orWhere('email', 'like', '%' . $this->search . '%');
                    }),
                )
                ->latest()
                ->paginate(10),
        ]);
    }
};
?>
<div>
    @include('pages.partials.crud-header', [
        'heading' => 'Supir',
        'description' => 'Kelola akun pengemudi dan jadwal operasionalnya.',
    ])
    <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex justify-end border-b border-slate-100 p-4"><input wire:model.live.debounce.300ms="search"
                placeholder="Cari supir..." class="w-full rounded-xl border px-4 py-2.5 text-sm sm:max-w-xs"></div>
        <div class="relative overflow-x-auto">
            <div wire:loading wire:target="search,save,openEdit,confirmDelete,delete"
                class="absolute inset-0 z-10 flex items-center justify-center bg-white/70"><span
                    class="rounded-xl bg-white px-4 py-3 text-sm font-semibold shadow-lg">Memuat data...</span></div>
            <table class="w-full min-w-170 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Nama</th>
                        <th class="px-6 py-4">Email</th>
                        <th class="px-6 py-4">Role</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($drivers as $driver)
                        <tr>
                            <td class="px-6 py-4 font-bold">{{ $driver->name }}</td>
                            <td class="px-6 py-4">{{ $driver->email }}</td>
                            <td class="px-6 py-4"><span
                                    class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-700">Supir</span>
                            </td>
                            <td class="px-6 py-4 text-right"><button wire:click="openEdit({{ $driver->id }})"
                                    class="px-2 text-xs font-bold text-brand-600">Edit</button><button
                                    wire:click="confirmDelete({{ $driver->id }})"
                                    class="px-2 text-xs font-bold text-red-600">Hapus</button></td>
                    </tr>@empty<tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-500">Belum ada supir.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-6 py-4">{{ $drivers->links() }}</div>
    </div>
    @if ($modalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-xl font-extrabold">{{ $editingId ? 'Edit' : 'Tambah' }} Supir</h2>
                <form wire:submit="save" class="mt-5 space-y-4"><input wire:model="name" placeholder="Nama lengkap"
                        class="w-full rounded-xl border px-4 py-3 text-sm"><input wire:model="email" type="email"
                        placeholder="Email" class="w-full rounded-xl border px-4 py-3 text-sm"><input
                        wire:model="password" type="password"
                        placeholder="Password {{ $editingId ? '(opsional)' : '' }}"
                        class="w-full rounded-xl border px-4 py-3 text-sm">
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
                <h2 class="font-extrabold">Hapus supir?</h2>
                <div class="mt-5 flex justify-end gap-3"><button wire:click="$set('confirmDeleteOpen', false)"
                        class="rounded-xl border px-4 py-2 text-sm font-bold">Batal</button><button wire:click="delete"
                        class="rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white">Hapus</button></div>
            </div>
        </div>
    @endif
</div>
