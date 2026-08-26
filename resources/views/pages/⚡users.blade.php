<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] class extends Component
{
    use WithPagination;
    public string $title = 'Users';

    public string $section = 'Master Data';


    public string $search = '';
    public bool $modalOpen = false;
    public bool $confirmDeleteOpen = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    /** @var array<int, string> */
    public array $selectedRoles = [];
    public ?int $deleteId = null;

    public function mount(): void
    {
        if (!auth()->check()) {
            $this->redirectRoute('login', navigate: true);
        }
    }

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
        $user = User::with('roles')->findOrFail($id);
        $this->resetValidation();
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->selectedRoles = $user->roles->pluck('id')->map(fn(int $roleId): string => (string) $roleId)->all();
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8'],
            'selectedRoles' => ['required', 'array', 'min:1'],
            'selectedRoles.*' => ['integer', 'exists:roles,id'],
        ]);

        $attributes = ['name' => $this->name, 'email' => $this->email];
        if ($this->password !== '') {
            $attributes['password'] = $this->password;
        }

        $user = $this->editingId ? User::findOrFail($this->editingId) : new User();
        $user->fill($attributes);
        $user->save();
        $user->syncRoles($this->selectedRoles);

        $message = $this->editingId ? 'User berhasil diperbarui.' : 'User berhasil ditambahkan.';
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
        if (!$this->deleteId || $this->deleteId === auth()->id()) {
            return;
        }

        User::findOrFail($this->deleteId)->delete();
        $this->confirmDeleteOpen = false;
        $this->deleteId = null;
        session()->flash('toast', ['type' => 'success', 'message' => 'User berhasil dihapus.']);
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
        $this->reset(['modalOpen', 'editingId', 'name', 'email', 'password', 'selectedRoles']);
        $this->resetValidation();
    }

    public function render(): mixed
    {
        return view('pages.⚡users', [
            'users' => User::with('roles')
                ->when(
                    $this->search !== '',
                    fn($query) => $query->where(function ($query): void {
                        $query->where('name', 'like', '%' . $this->search . '%')->orWhere('email', 'like', '%' . $this->search . '%');
                    }),
                )
                ->latest()
                ->paginate(10),
            'roles' => Role::orderBy('display_name')->get(),
        ]);
    }
};
?>

<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-brand-600">Master Data</p>
                <h2 class="mt-2 text-3xl font-extrabold text-slate-900">Users</h2>
                <p class="mt-2 text-sm text-slate-500">Kelola akun pengguna dan satu atau beberapa peran aksesnya.</p>
            </div>
            <button type="button" wire:click="openCreate" wire:loading.attr="disabled" wire:target="openCreate"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-brand-500/20 hover:bg-brand-600 disabled:opacity-60">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                <span wire:loading.remove wire:target="openCreate">Tambah User</span><span wire:loading
                    wire:target="openCreate">Membuka...</span>
            </button>
        </div>

        @if (session('toast'))
            <div
                class="mt-6 flex items-center gap-3 rounded-xl border px-4 py-3 text-sm font-semibold {{ session('toast.type') === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-green-200 bg-green-50 text-green-700' }}">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m5 12 4 4L19 6" />
                </svg>{{ session('toast.message') }}
            </div>
        @endif

        <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div
                class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="font-extrabold text-slate-900">Daftar Pengguna</h3>
                <div class="relative w-full sm:max-w-xs"><svg
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m21 21-4.3-4.3" />
                    </svg><input type="search" wire:model.live.debounce.300ms="search"
                        placeholder="Cari nama atau email..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-10 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100"><span
                        wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2"><svg
                            class="h-4 w-4 animate-spin text-brand-500" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="3" />
                            <path d="M4 12a8 8 0 0 1 8-8" />
                        </svg></span></div>
            </div>
            <div class="relative overflow-x-auto">
                <div wire:loading wire:target="search,save,openEdit,confirmDelete,delete"
                    class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur-sm">
                    <div
                        class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-lg">
                        <svg class="h-5 w-5 animate-spin text-brand-500" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="3" />
                            <path d="M4 12a8 8 0 0 1 8-8" />
                        </svg>Memuat data...
                    </div>
                </div>
                <table class="w-full min-w-170 text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-4">User</th>
                            <th class="px-6 py-4">Email</th>
                            <th class="px-6 py-4">Peran</th>
                            <th class="px-6 py-4">Terdaftar</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $user)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3"><img
                                            src="https://i.pravatar.cc/80?u={{ urlencode($user->email) }}"
                                            alt="Avatar {{ $user->name }}"
                                            class="h-9 w-9 rounded-full object-cover"><span
                                            class="font-bold text-slate-900">{{ $user->name }}</span></div>
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $user->email }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex max-w-xs flex-wrap gap-1.5">
                                        @forelse ($user->roles as $role)
                                            <span
                                            class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-700">{{ $role->display_name ?: $role->name }}</span>@empty<span
                                                class="text-xs text-slate-400">Belum ada role</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-500">{{ $user->created_at?->format('d M Y') }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2"><button type="button"
                                            wire:click="openEdit({{ $user->id }})" wire:loading.attr="disabled"
                                            wire:target="openEdit({{ $user->id }})"
                                            class="rounded-lg px-3 py-2 text-xs font-bold text-brand-600 hover:bg-brand-50 disabled:opacity-50"><span
                                                wire:loading.remove
                                                wire:target="openEdit({{ $user->id }})">Edit</span><span
                                                wire:loading
                                                wire:target="openEdit({{ $user->id }})">...</span></button><button
                                            type="button" wire:click="confirmDelete({{ $user->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="confirmDelete({{ $user->id }})"
                                            class="rounded-lg px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50 disabled:opacity-50"><span
                                                wire:loading.remove
                                                wire:target="confirmDelete({{ $user->id }})">Hapus</span><span
                                                wire:loading
                                                wire:target="confirmDelete({{ $user->id }})">...</span></button>
                                    </div>
                                </td>
                        </tr>@empty<tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">Belum ada
                                    user.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-4">{{ $users->links() }}</div>
        </div>
    </div>

    @if ($modalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4"
            role="dialog" aria-modal="true">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-brand-600">
                            {{ $editingId ? 'Edit' : 'Tambah' }}</p>
                        <h2 class="mt-1 text-xl font-extrabold text-slate-900">User</h2>
                    </div><button type="button" wire:click="$set('modalOpen', false)"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-50" aria-label="Tutup modal"><svg
                            class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M6 6l12 12M18 6L6 18" />
                        </svg></button>
                </div>
                <form wire:submit="save" class="mt-6 space-y-4">
                    <div><label for="user-name"
                            class="mb-1.5 block text-sm font-semibold text-slate-700">Nama</label><input
                            id="user-name" type="text" wire:model="name"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
                        @error('name')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div><label for="user-email"
                            class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label><input
                            id="user-email" type="email" wire:model="email"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
                        @error('email')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div><label for="user-password" class="mb-1.5 block text-sm font-semibold text-slate-700">Password
                            {{ $editingId ? '(kosongkan jika tidak diubah)' : '' }}</label><input id="user-password"
                            type="password" wire:model="password"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
                        @error('password')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <p class="mb-2 text-sm font-semibold text-slate-700">Peran user</p>
                        <div
                            class="grid max-h-40 grid-cols-1 gap-2 overflow-y-auto rounded-xl border border-slate-200 p-3 sm:grid-cols-2">
                            @forelse ($roles as $role)
                                <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox"
                                        wire:model="selectedRoles" value="{{ $role->id }}"
                                    class="rounded border-slate-300 text-brand-500 focus:ring-brand-500">{{ $role->display_name ?: $role->name }}</label>@empty
                                <p class="text-xs text-slate-400">Belum ada role.</p>
                            @endforelse
                        </div>
                        @error('selectedRoles')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex flex-col-reverse gap-3 pt-3 sm:flex-row sm:justify-end"><button type="button"
                            wire:click="$set('modalOpen', false)"
                            class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600 hover:bg-slate-50">Batal</button><button
                            type="submit" wire:loading.attr="disabled"
                            class="rounded-xl bg-brand-500 px-5 py-3 text-sm font-bold text-white hover:bg-brand-600 disabled:opacity-60"><span
                                wire:loading.remove wire:target="save">Simpan</span><span wire:loading
                                wire:target="save">Menyimpan...</span></button></div>
                </form>
            </div>
        </div>
        +
    @endif
    +
    + @if ($confirmDeleteOpen)
        + <div class="fixed inset-0 z-60 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
                <h2 class="text-lg font-extrabold text-slate-900">Hapus user?</h2>
                <p class="mt-2 text-sm text-slate-500">Data user dan relasi rolenya akan dihapus.</p>
                <div class="mt-6 flex justify-end gap-3"><button type="button"
                        wire:click="$set('confirmDeleteOpen', false)"
                        class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600">Batal</button><button
                        type="button" wire:click="delete" wire:loading.attr="disabled"
                        class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-60"><span
                            wire:loading.remove wire:target="delete">Ya, Hapus</span><span wire:loading
                            wire:target="delete">Menghapus...</span></button></div>
            </div>
        </div>
        +
    @endif
</div>
