<?php

use App\Models\Permission;
use App\Models\Role;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] class extends Component {
    use WithPagination;
    public string $title = 'Role & Permission';

    public string $section = 'Master Data';

    public string $activeTab = 'roles';

    public string $search = '';

    public bool $modalOpen = false;

    public bool $confirmDeleteOpen = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $displayName = '';

    public string $description = '';

    /** @var array<int, string> */
    public array $selectedPermissions = [];

    public ?string $deleteType = null;

    public ?int $deleteId = null;

    public function updatingSearch(): void
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

    public function setTab(string $tab): void
    {
        if (!in_array($tab, ['roles', 'permissions'], true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->resetForm();
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->modalOpen = true;
    }

    public function openEdit(int $id): void
    {
        $this->resetValidation();
        $this->editingId = $id;

        if ($this->activeTab === 'roles') {
            $record = Role::with('permissions')->findOrFail($id);
            $this->selectedPermissions = $record->permissions->pluck('id')->map(fn(int $permissionId): string => (string) $permissionId)->all();
        } else {
            $record = Permission::findOrFail($id);
        }

        $this->name = $record->name;
        $this->displayName = $record->display_name ?? '';
        $this->description = $record->description ?? '';
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', 'alpha_dash'],
            'displayName' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];

        if ($this->activeTab === 'roles') {
            $rules['selectedPermissions.*'] = ['integer', 'exists:permissions,id'];
        }

        $this->validate($rules);

        if ($this->activeTab === 'roles') {
            $role = Role::updateOrCreate(['id' => $this->editingId], ['name' => $this->name, 'display_name' => $this->displayName, 'description' => $this->description]);
            $role->permissions()->sync($this->selectedPermissions);
            $message = $this->editingId ? 'Role berhasil diperbarui.' : 'Role berhasil ditambahkan.';
        } else {
            Permission::updateOrCreate(['id' => $this->editingId], ['name' => $this->name, 'display_name' => $this->displayName, 'description' => $this->description]);
            $message = $this->editingId ? 'Permission berhasil diperbarui.' : 'Permission berhasil ditambahkan.';
        }

        $this->modalOpen = false;
        $this->resetForm();
        session()->flash('toast', ['type' => 'success', 'message' => $message]);
    }

    public function confirmDelete(string $type, int $id): void
    {
        if (!in_array($type, ['role', 'permission'], true)) {
            return;
        }

        $this->deleteType = $type;
        $this->deleteId = $id;
        $this->confirmDeleteOpen = true;
    }

    public function delete(): void
    {
        if ($this->deleteType === 'role' && $this->deleteId) {
            Role::findOrFail($this->deleteId)->delete();
            $message = 'Role berhasil dihapus.';
        } elseif ($this->deleteType === 'permission' && $this->deleteId) {
            Permission::findOrFail($this->deleteId)->delete();
            $message = 'Permission berhasil dihapus.';
        } else {
            return;
        }

        $this->confirmDeleteOpen = false;
        $this->deleteType = null;
        $this->deleteId = null;
        session()->flash('toast', ['type' => 'success', 'message' => $message]);
    }

    private function resetForm(): void
    {
        $this->reset(['modalOpen', 'editingId', 'name', 'displayName', 'description', 'selectedPermissions']);
        $this->resetValidation();
    }

    public function render(): mixed
    {
        $query = $this->activeTab === 'roles' ? Role::withCount('permissions')->with('permissions') : Permission::withCount('roles');

        $records = $query
            ->when(
                $this->search !== '',
                fn($builder) => $builder->where(function ($query): void {
                    $query->where('name', 'like', '%' . $this->search . '%')->orWhere('display_name', 'like', '%' . $this->search . '%');
                }),
            )
            ->latest()
            ->paginate(10);

        return view('pages.⚡roles-permissions', [
            'records' => $records,
            'permissions' => Permission::orderBy('display_name')->get(),
        ]);
    }
};
?>

<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('dashboard') }}" wire:navigate
                    class="inline-flex items-center gap-2 text-sm font-semibold text-brand-600 hover:text-brand-700">&larr;
                    Kembali ke Dashboard</a>
                <p class="mt-6 text-xs font-bold uppercase tracking-widest text-brand-600">Master Data</p>
                <h1 class="mt-2 text-3xl font-extrabold text-slate-900">Role &amp; Permission</h1>
                <p class="mt-2 text-sm text-slate-500">Atur peran dan hak akses yang digunakan di dalam sistem.</p>
            </div>
            <button type="button" wire:click="openCreate" wire:loading.attr="disabled" wire:target="openCreate"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-brand-500/20 hover:bg-brand-600 disabled:opacity-60">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                <span wire:loading.remove wire:target="openCreate">Tambah
                    {{ $activeTab === 'roles' ? 'Role' : 'Permission' }}</span>
                <span wire:loading wire:target="openCreate">Membuka...</span>
            </button>
        </div>

        @if (session('toast'))
            <div
                class="mt-6 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="m5 12 4 4L19 6" />
                </svg>
                {{ session('toast.message') }}
            </div>
        @endif

        <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div
                class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex rounded-xl bg-slate-100 p-1">
                    <button type="button" wire:click="setTab('roles')"
                        class="rounded-lg px-4 py-2 text-sm font-bold transition {{ $activeTab === 'roles' ? 'bg-white text-brand-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">Role</button>
                    <button type="button" wire:click="setTab('permissions')"
                        class="rounded-lg px-4 py-2 text-sm font-bold transition {{ $activeTab === 'permissions' ? 'bg-white text-brand-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">Permission</button>
                </div>
                <div class="relative w-full sm:max-w-xs">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                    <input type="search" wire:model.live.debounce.300ms="search"
                        placeholder="Cari {{ $activeTab === 'roles' ? 'role' : 'permission' }}..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-10 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
                    <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2"><svg
                            class="h-4 w-4 animate-spin text-brand-500" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="3" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4Z" />
                        </svg></span>
                </div>
            </div>

            <div class="relative overflow-x-auto">
                <div wire:loading wire:target="search,setTab,save,openEdit,confirmDelete,delete"
                    class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur-sm">
                    <div
                        class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-lg">
                        <svg class="h-5 w-5 animate-spin text-brand-500" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="3" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4Z" />
                        </svg>Memuat data...
                    </div>
                </div>
                <table class="w-full min-w-170 text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-4">Nama</th>
                            <th class="px-6 py-4">Display Name</th>
                            <th class="px-6 py-4">Deskripsi</th>
                            <th class="px-6 py-4">{{ $activeTab === 'roles' ? 'Permission' : 'Role' }}</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($records as $record)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-6 py-4 font-bold text-slate-900">{{ $record->name }}</td>
                                <td class="px-6 py-4 font-medium text-slate-700">{{ $record->display_name }}</td>
                                <td class="max-w-xs px-6 py-4 text-slate-500">{{ $record->description ?: '-' }}</td>
                                <td class="px-6 py-4"><span
                                        class="rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700">{{ $activeTab === 'roles' ? $record->permissions_count : $record->roles_count }}
                                        item</span></td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2"><button type="button"
                                            wire:click="openEdit({{ $record->id }})" wire:loading.attr="disabled"
                                            wire:target="openEdit({{ $record->id }})"
                                            class="rounded-lg px-3 py-2 text-xs font-bold text-brand-600 hover:bg-brand-50 disabled:opacity-50"><span
                                                wire:loading.remove
                                                wire:target="openEdit({{ $record->id }})">Edit</span><span
                                                wire:loading
                                                wire:target="openEdit({{ $record->id }})">...</span></button><button
                                            type="button"
                                            wire:click="confirmDelete('{{ $activeTab === 'roles' ? 'role' : 'permission' }}', {{ $record->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="confirmDelete('{{ $activeTab === 'roles' ? 'role' : 'permission' }}', {{ $record->id }})"
                                            class="rounded-lg px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50 disabled:opacity-50"><span
                                                wire:loading.remove
                                                wire:target="confirmDelete('{{ $activeTab === 'roles' ? 'role' : 'permission' }}', {{ $record->id }})">Hapus</span><span
                                                wire:loading
                                                wire:target="confirmDelete('{{ $activeTab === 'roles' ? 'role' : 'permission' }}', {{ $record->id }})">...</span></button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">Belum ada
                                    {{ $activeTab === 'roles' ? 'role' : 'permission' }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-4">{{ $records->links() }}</div>
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
                        <h2 class="mt-1 text-xl font-extrabold text-slate-900">
                            {{ $activeTab === 'roles' ? 'Role' : 'Permission' }}</h2>
                    </div><button type="button" wire:click="$set('modalOpen', false)"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-50" aria-label="Tutup modal"><svg
                            class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 6l12 12M18 6L6 18" />
                        </svg></button>
                </div>
                <form wire:submit="save" class="mt-6 space-y-4">
                    <div><label for="name" class="mb-1.5 block text-sm font-semibold text-slate-700">Nama
                            teknis</label><input id="name" type="text" wire:model="name"
                            placeholder="contoh: manage_users"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
                        @error('name')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div><label for="displayName" class="mb-1.5 block text-sm font-semibold text-slate-700">Display
                            name</label><input id="displayName" type="text" wire:model="displayName"
                            placeholder="contoh: Kelola Users"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
                        @error('displayName')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div><label for="description"
                            class="mb-1.5 block text-sm font-semibold text-slate-700">Deskripsi</label>
                        <textarea id="description" wire:model="description" rows="3"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100"></textarea>
                        @error('description')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    @if ($activeTab === 'roles')
                        <div>
                            <p class="mb-2 text-sm font-semibold text-slate-700">Permission</p>
                            <div
                                class="grid max-h-36 grid-cols-1 gap-2 overflow-y-auto rounded-xl border border-slate-200 p-3 sm:grid-cols-2">
                                @forelse ($permissions as $permission)
                                    <label class="flex items-center gap-2 text-sm text-slate-600"><input
                                            type="checkbox" wire:model="selectedPermissions"
                                            value="{{ $permission->id }}"
                                        class="rounded border-slate-300 text-brand-500 focus:ring-brand-500">{{ $permission->display_name ?: $permission->name }}</label>@empty
                                    <p class="text-xs text-slate-400">Belum ada permission.</p>
                                @endforelse
                            </div>
                        </div>
                    @endif
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
    @endif

    @if ($confirmDeleteOpen)
        <div class="fixed inset-0 z-60 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
                <h2 class="text-lg font-extrabold text-slate-900">Hapus
                    {{ $deleteType === 'role' ? 'role' : 'permission' }}?</h2>
                <p class="mt-2 text-sm text-slate-500">Data yang dihapus tidak dapat dikembalikan.</p>
                <div class="mt-6 flex justify-end gap-3"><button type="button"
                        wire:click="$set('confirmDeleteOpen', false)"
                        class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600">Batal</button><button
                        type="button" wire:click="delete" wire:loading.attr="disabled"
                        class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-60"><span
                            wire:loading.remove wire:target="delete">Ya, Hapus</span><span wire:loading
                            wire:target="delete">Menghapus...</span></button></div>
            </div>
        </div>
    @endif
</div>
</div>
