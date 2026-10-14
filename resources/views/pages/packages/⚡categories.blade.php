<?php

use App\Models\Package;
use App\Models\PackageCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts::admin')] class extends Component
{
    public string $title = 'Kategori Paket';
    public string $section = 'Paket';
    public bool $modalOpen = false;
    public bool $confirmDeleteOpen = false;
    #[Locked]
    public ?int $editingId = null;
    #[Locked]
    public ?int $deleteId = null;
    public string $name = '';
    public string $type = 'package';

    public function openCreate(): void
    {
        abort_unless(auth()->user()?->hasPermission('packages.manage'), 403);
        $this->reset(['editingId', 'name', 'type']);
        $this->resetValidation();
        $this->modalOpen = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()?->hasPermission('packages.manage'), 403);
        $category = PackageCategory::findOrFail($id);
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->type = $category->type;
        $this->resetValidation();
        $this->modalOpen = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->hasPermission('packages.manage'), 403);
        $this->name = trim($this->name);
        $this->validate([
            'type' => ['required', Rule::in(array_keys(PackageCategory::TYPES))],
            'name' => ['required', 'string', 'max:255', Rule::unique('package_categories', 'name')->where('type', $this->type)->ignore($this->editingId)],
        ]);
        DB::transaction(function (): void {
            $category = $this->editingId ? PackageCategory::query()->lockForUpdate()->findOrFail($this->editingId) : new PackageCategory;
            if ($category->exists) {
                if ($category->type !== $this->type) {
                    throw ValidationException::withMessages(['type' => 'Jenis kategori tidak dapat diubah.']);
                }
                Package::where($category->packageColumn(), $category->name)->update([$category->packageColumn() => $this->name]);
            }
            $category->fill(['name' => $this->name, 'type' => $this->type])->save();
        });
        $this->reset(['modalOpen', 'editingId', 'name', 'type']);
        session()->flash('toast', ['type' => 'success', 'message' => 'Kategori berhasil disimpan.']);
    }

    public function confirmDelete(int $id): void
    {
        abort_unless(auth()->user()?->hasPermission('packages.manage'), 403);
        $this->deleteId = PackageCategory::findOrFail($id)->id;
        $this->resetValidation();
        $this->confirmDeleteOpen = true;
    }

    public function delete(): void
    {
        abort_unless(auth()->user()?->hasPermission('packages.manage'), 403);
        DB::transaction(function (): void {
            $category = PackageCategory::query()->lockForUpdate()->findOrFail($this->deleteId);
            if (Package::where($category->packageColumn(), $category->name)->exists()) {
                throw ValidationException::withMessages(['deleteId' => 'Kategori masih digunakan oleh paket dan tidak dapat dihapus.']);
            }
            $category->delete();
        });
        $this->reset(['confirmDeleteOpen', 'deleteId']);
        session()->flash('toast', ['type' => 'success', 'message' => 'Kategori berhasil dihapus.']);
    }

    public function render(): mixed
    {
        return view('pages.packages.⚡categories', ['categoryGroups' => PackageCategory::orderBy('name')->get()->groupBy('type')]);
    }
};
?>

<div>
    @include('pages.partials.crud-header', ['heading' => 'Kategori Paket', 'description' => 'Kelola kategori paket dan kategori keuangan untuk laporan.'])
    <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2">
        @foreach (PackageCategory::TYPES as $categoryType => $heading)
            <section wire:key="{{ $categoryType }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-extrabold">{{ $heading }}</h3>
                <ul class="mt-4 divide-y divide-slate-100">
                    @forelse ($categoryGroups->get($categoryType, collect()) as $category)
                        <li wire:key="category-{{ $category->id }}" class="flex items-center justify-between gap-3 py-3 text-sm">
                            <span class="font-semibold text-slate-700">{{ $category->name }}</span>
                            <div class="flex gap-3">
                                <button type="button" wire:click="openEdit({{ $category->id }})"
                                    wire:loading.attr="disabled" wire:target="openEdit({{ $category->id }})"
                                    class="font-bold text-brand-600 disabled:cursor-wait disabled:opacity-60">
                                    <span wire:loading.remove wire:target="openEdit({{ $category->id }})">Edit</span>
                                    <span wire:loading wire:target="openEdit({{ $category->id }})" role="status">Membuka...</span>
                                </button>
                                <button type="button" wire:click="confirmDelete({{ $category->id }})"
                                    wire:loading.attr="disabled" wire:target="confirmDelete({{ $category->id }})"
                                    class="font-bold text-red-600 disabled:cursor-wait disabled:opacity-60">
                                    <span wire:loading.remove wire:target="confirmDelete({{ $category->id }})">Hapus</span>
                                    <span wire:loading wire:target="confirmDelete({{ $category->id }})" role="status">Membuka...</span>
                                </button>
                            </div>
                        </li>
                    @empty
                        <li class="py-3 text-sm text-slate-500">Belum ada kategori.</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
    @if ($modalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="category-modal-title">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                <h2 id="category-modal-title" class="text-xl font-extrabold">{{ $editingId ? 'Edit' : 'Tambah' }} Kategori</h2>
                <form wire:submit="save" class="mt-5 space-y-4">
                    <div>
                        <label for="category-type" class="mb-1 block text-sm font-semibold">Jenis kategori</label>
                        <select id="category-type" wire:model="type" @disabled($editingId) class="w-full rounded-xl border px-4 py-3 text-sm">
                            @foreach (PackageCategory::TYPES as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="category-name" class="mb-1 block text-sm font-semibold">Nama kategori</label>
                        <input id="category-name" wire:model="name" class="w-full rounded-xl border px-4 py-3 text-sm">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="$set('modalOpen', false)"
                            wire:loading.attr="disabled" wire:target="save"
                            class="rounded-xl border px-5 py-3 text-sm font-bold disabled:cursor-wait disabled:opacity-60">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="save"
                            class="rounded-xl bg-brand-500 px-5 py-3 text-sm font-bold text-white disabled:cursor-wait disabled:opacity-60">
                            <span wire:loading.remove wire:target="save">Simpan</span>
                            <span wire:loading wire:target="save" role="status">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
    @if ($confirmDeleteOpen)
        <div class="fixed inset-0 z-60 flex items-center justify-center bg-slate-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="category-delete-title">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
                <h2 id="category-delete-title" class="text-lg font-extrabold">Hapus kategori?</h2>
                <p class="mt-2 text-sm text-slate-500">Kategori yang masih digunakan paket tidak dapat dihapus.</p>
                @error('deleteId') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" wire:click="$set('confirmDeleteOpen', false)"
                        wire:loading.attr="disabled" wire:target="delete"
                        class="rounded-xl border px-4 py-2 text-sm font-bold disabled:cursor-wait disabled:opacity-60">Batal</button>
                    <button type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete"
                        class="rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white disabled:cursor-wait disabled:opacity-60">
                        <span wire:loading.remove wire:target="delete">Hapus</span>
                        <span wire:loading wire:target="delete" role="status">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
