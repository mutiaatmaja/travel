<?php

use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts::admin')] class extends Component
{
    public string $title = 'Kategori Pengeluaran';
    public string $section = 'Pengeluaran';
    public bool $modalOpen = false;
    public bool $confirmDeleteOpen = false;
    #[Locked]
    public ?int $editingId = null;
    #[Locked]
    public ?int $deleteId = null;
    public string $name = '';
    public string $service = 'travel';
    public string $description = '';

    public function openCreate(): void
    {
        $this->ensureAuthorized();
        $this->reset(['editingId', 'name', 'service', 'description']);
        $this->resetValidation();
        $this->modalOpen = true;
    }

    public function openEdit(int $id): void
    {
        $this->ensureAuthorized();
        $category = ExpenseCategory::findOrFail($id);
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->service = $category->service;
        $this->description = $category->description ?? '';
        $this->resetValidation();
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $this->ensureAuthorized();
        $this->name = trim($this->name);
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('expense_categories', 'name')->where('service', $this->service)->ignore($this->editingId)],
            'service' => ['required', Rule::in(array_keys(ExpenseCategory::SERVICES))],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        DB::transaction(function () use ($validated): void {
            $category = $this->editingId ? ExpenseCategory::query()->lockForUpdate()->findOrFail($this->editingId) : new ExpenseCategory;
            if ($category->exists && $category->service !== $this->service) {
                throw ValidationException::withMessages(['service' => 'Layanan kategori tidak dapat diubah.']);
            }
            $category->fill($validated)->save();
        });
        $this->reset(['modalOpen', 'editingId', 'name', 'service', 'description']);
        session()->flash('toast', ['type' => 'success', 'message' => 'Kategori pengeluaran berhasil disimpan.']);
    }

    public function confirmDelete(int $id): void
    {
        $this->ensureAuthorized();
        $this->deleteId = ExpenseCategory::findOrFail($id)->id;
        $this->resetValidation();
        $this->confirmDeleteOpen = true;
    }

    public function delete(): void
    {
        $this->ensureAuthorized();
        abort_unless($this->confirmDeleteOpen && $this->deleteId, 403);
        DB::transaction(function (): void {
            $category = ExpenseCategory::query()->lockForUpdate()->findOrFail($this->deleteId);
            if ($category->expenses()->exists()) {
                throw ValidationException::withMessages(['deleteId' => 'Kategori masih digunakan oleh pengeluaran dan tidak dapat dihapus.']);
            }
            $category->delete();
        });
        $this->reset(['confirmDeleteOpen', 'deleteId']);
        session()->flash('toast', ['type' => 'success', 'message' => 'Kategori pengeluaran berhasil dihapus.']);
    }

    private function ensureAuthorized(): void
    {
        abort_unless(auth()->user()?->hasPermission('expenses.manage'), 403);
    }

    public function render(): mixed
    {
        $this->ensureAuthorized();
        return view('pages.expenses.⚡categories', ['categoryGroups' => ExpenseCategory::withCount('expenses')->orderBy('name')->get()->groupBy('service')]);
    }
};
?>

<div>
    @include('pages.partials.crud-header', ['heading' => 'Kategori Pengeluaran', 'description' => 'Pisahkan kategori biaya layanan Travel dan Paket.'])
    <div class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-2">
        @foreach (ExpenseCategory::SERVICES as $serviceKey => $label)
            <section wire:key="expense-service-{{ $serviceKey }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-extrabold">{{ $label }}</h3>
                <ul class="mt-4 divide-y divide-slate-100">
                    @forelse ($categoryGroups->get($serviceKey, collect()) as $category)
                        <li wire:key="expense-category-{{ $category->id }}" class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0"><p class="break-words font-bold">{{ $category->name }}</p><p class="mt-1 break-words text-sm text-slate-500">{{ $category->description ?: 'Tanpa keterangan' }}</p><p class="mt-1 text-xs text-slate-400">{{ $category->expenses_count }} pengeluaran</p></div>
                            <div class="flex shrink-0 gap-4 text-sm font-bold">
                                <button type="button" wire:click="openEdit({{ $category->id }})" wire:loading.attr="disabled" wire:target="openEdit({{ $category->id }})" class="rounded text-brand-600 focus:ring-2 focus:ring-brand-500 disabled:opacity-60"><span wire:loading.remove wire:target="openEdit({{ $category->id }})">Edit</span><span wire:loading wire:target="openEdit({{ $category->id }})" role="status">Membuka...</span></button>
                                <button type="button" wire:click="confirmDelete({{ $category->id }})" wire:loading.attr="disabled" wire:target="confirmDelete({{ $category->id }})" class="rounded text-red-600 focus:ring-2 focus:ring-red-500 disabled:opacity-60"><span wire:loading.remove wire:target="confirmDelete({{ $category->id }})">Hapus</span><span wire:loading wire:target="confirmDelete({{ $category->id }})" role="status">Membuka...</span></button>
                            </div>
                        </li>
                    @empty
                        <li class="py-6 text-sm text-slate-500">Belum ada kategori {{ $label }}. Tambahkan kategori sebelum mencatat pengeluaran.</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
    @if ($modalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" role="dialog" aria-modal="true" aria-labelledby="expense-category-title">
            <div class="max-h-[calc(100dvh-2rem)] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
                <h2 id="expense-category-title" class="text-xl font-extrabold">{{ $editingId ? 'Edit' : 'Tambah' }} Kategori Pengeluaran</h2>
                <form wire:submit="save" class="mt-5 space-y-4">
                    <div><label for="expense-category-service" class="mb-1 block text-sm font-semibold">Layanan</label><select id="expense-category-service" wire:model="service" @disabled($editingId) class="w-full rounded-xl border px-4 py-3 text-sm focus:ring-2 focus:ring-brand-500">@foreach (ExpenseCategory::SERVICES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>@error('service')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label for="expense-category-name" class="mb-1 block text-sm font-semibold">Nama kategori</label><input id="expense-category-name" wire:model="name" maxlength="255" class="w-full rounded-xl border px-4 py-3 text-sm focus:ring-2 focus:ring-brand-500">@error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label for="expense-category-description" class="mb-1 block text-sm font-semibold">Keterangan (opsional)</label><textarea id="expense-category-description" wire:model="description" rows="3" class="w-full rounded-xl border px-4 py-3 text-sm focus:ring-2 focus:ring-brand-500"></textarea>@error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    <div class="flex justify-end gap-3"><button type="button" wire:click="$set('modalOpen', false)" wire:loading.attr="disabled" wire:target="save" class="rounded-xl border px-4 py-3 text-sm font-bold disabled:opacity-60">Batal</button><button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-xl bg-brand-500 px-4 py-3 text-sm font-bold text-white disabled:opacity-60"><span wire:loading.remove wire:target="save">Simpan</span><span wire:loading wire:target="save" role="status">Menyimpan...</span></button></div>
                </form>
            </div>
        </div>
    @endif
    @if ($confirmDeleteOpen)
        <div class="fixed inset-0 z-60 flex items-center justify-center bg-slate-900/60 p-4" role="dialog" aria-modal="true" aria-labelledby="expense-category-delete-title"><div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl"><h2 id="expense-category-delete-title" class="text-lg font-bold">Hapus kategori pengeluaran?</h2><p class="mt-2 text-sm text-slate-500">Kategori yang masih digunakan tidak dapat dihapus.</p>@error('deleteId')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror<div class="mt-5 flex justify-end gap-3"><button type="button" wire:click="$set('confirmDeleteOpen', false)" wire:loading.attr="disabled" wire:target="delete" class="rounded-xl border px-4 py-2 text-sm font-bold disabled:opacity-60">Batal</button><button type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-60"><span wire:loading.remove wire:target="delete">Hapus</span><span wire:loading wire:target="delete" role="status">Menghapus...</span></button></div></div></div>
    @endif
</div>