<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] class extends Component
{
    use WithPagination;

    public string $title = 'Pengeluaran';
    public string $section = 'Pengeluaran';
    #[Locked]
    public string $service;
    public bool $modalOpen = false;
    public bool $confirmDeleteOpen = false;
    #[Locked]
    public ?int $editingId = null;
    #[Locked]
    public ?int $deleteId = null;
    #[Locked]
    public string $submissionToken = '';
    public ?int $expenseCategoryId = null;
    public string $expenseDate = '';
    public string $amount = '';
    public string $description = '';
    public string $search = '';
    public ?int $categoryFilter = null;
    public string $startDate = '';
    public string $endDate = '';
    #[Locked]
    public string $sortDirection = 'desc';
    /** @var array{search: string, categoryFilter: ?int, startDate: string, endDate: string} */
    #[Locked]
    public array $filters = ['search' => '', 'categoryFilter' => null, 'startDate' => '', 'endDate' => ''];

    public function mount(string $service): void
    {
        $this->ensureAuthorized();
        abort_unless(array_key_exists($service, ExpenseCategory::SERVICES), 404);
        $this->service = $service;
        $this->title = 'Pengeluaran '.ExpenseCategory::SERVICES[$service];
    }

    public function openCreate(): void
    {
        $this->ensureAuthorized();
        $this->reset(['editingId', 'expenseCategoryId', 'amount', 'description']);
        $this->expenseDate = now()->format('Y-m-d');
        $this->submissionToken = (string) Str::uuid();
        $this->resetValidation();
        $this->modalOpen = true;
    }

    public function openEdit(int $id): void
    {
        $this->ensureAuthorized();
        $expense = $this->serviceQuery()->findOrFail($id);
        $this->editingId = $expense->id;
        $this->expenseCategoryId = $expense->expense_category_id;
        $this->expenseDate = $expense->expense_date->format('Y-m-d');
        $this->amount = (string) $expense->amount;
        $this->description = $expense->description ?? '';
        $this->submissionToken = $expense->submission_token;
        $this->resetValidation();
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $this->ensureAuthorized();
        if (! $this->modalOpen) {
            return;
        }
        $this->validate([
            'expenseCategoryId' => ['required', 'integer', Rule::exists('expense_categories', 'id')->where('service', $this->service)],
            'expenseDate' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'description' => ['nullable', 'string', 'max:2000'],
            'submissionToken' => ['required', 'uuid'],
        ]);
        DB::transaction(function (): void {
            ExpenseCategory::query()->where('service', $this->service)->lockForUpdate()->findOrFail($this->expenseCategoryId);
            $attributes = ['expense_category_id' => $this->expenseCategoryId, 'expense_date' => $this->expenseDate, 'amount' => (int) $this->amount, 'description' => $this->description];
            if ($this->editingId) {
                $this->serviceQuery()->lockForUpdate()->findOrFail($this->editingId)->update($attributes);
            } else {
                Expense::firstOrCreate(['submission_token' => $this->submissionToken], [...$attributes, 'created_by' => auth()->id()]);
            }
        });
        $this->modalOpen = false;
        $this->reset(['editingId', 'expenseCategoryId', 'amount', 'description', 'submissionToken']);
        session()->flash('toast', ['type' => 'success', 'message' => 'Pengeluaran berhasil disimpan.']);
    }

    public function confirmDelete(int $id): void
    {
        $this->ensureAuthorized();
        $this->deleteId = $this->serviceQuery()->findOrFail($id)->id;
        $this->confirmDeleteOpen = true;
        $this->resetValidation();
    }

    public function delete(): void
    {
        $this->ensureAuthorized();
        abort_unless($this->confirmDeleteOpen && $this->deleteId, 403);
        $this->serviceQuery()->findOrFail($this->deleteId)->delete();
        $this->reset(['confirmDeleteOpen', 'deleteId']);
        $this->resetPage();
        session()->flash('toast', ['type' => 'success', 'message' => 'Pengeluaran berhasil dihapus.']);
    }

    public function applyFilters(): void
    {
        $this->ensureAuthorized();
        $this->search = trim($this->search);
        $this->filters = $this->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'categoryFilter' => ['nullable', 'integer', Rule::exists('expense_categories', 'id')->where('service', $this->service)],
            'startDate' => ['nullable', 'date_format:Y-m-d'],
            'endDate' => ['nullable', 'date_format:Y-m-d', Rule::when($this->startDate !== '', ['after_or_equal:startDate'])],
        ]);
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->ensureAuthorized();
        $this->reset(['search', 'categoryFilter', 'startDate', 'endDate', 'filters']);
        $this->resetValidation();
        $this->resetPage();
    }

    public function toggleDateSort(): void
    {
        $this->ensureAuthorized();
        $this->sortDirection = $this->sortDirection === 'desc' ? 'asc' : 'desc';
        $this->resetPage();
    }

    private function ensureAuthorized(): void
    {
        abort_unless(auth()->user()?->hasPermission('expenses.manage'), 403);
    }

    private function serviceQuery(): Builder
    {
        return Expense::query()->whereHas('category', fn (Builder $query): Builder => $query->where('service', $this->service));
    }

    private function filteredQuery(): Builder
    {
        return $this->serviceQuery()
            ->when($this->filters['search'] !== '', fn (Builder $query): Builder => $query->where(function (Builder $query): void {
                $query->where('description', 'like', '%'.$this->filters['search'].'%')
                    ->orWhereHas('category', fn (Builder $category): Builder => $category->where('name', 'like', '%'.$this->filters['search'].'%'));
            }))
            ->when($this->filters['categoryFilter'], fn (Builder $query): Builder => $query->where('expense_category_id', $this->filters['categoryFilter']))
            ->when($this->filters['startDate'] !== '', fn (Builder $query): Builder => $query->where('expense_date', '>=', $this->filters['startDate']))
            ->when($this->filters['endDate'] !== '', fn (Builder $query): Builder => $query->where('expense_date', '<', Carbon::parse($this->filters['endDate'])->addDay()->toDateString()));
    }

    public function render(): mixed
    {
        $this->ensureAuthorized();
        $query = $this->filteredQuery();
        return view('pages.expenses.⚡index', [
            'categories' => ExpenseCategory::where('service', $this->service)->orderBy('name')->get(),
            'expenses' => (clone $query)->with(['category', 'creator'])->orderBy('expense_date', $this->sortDirection)->orderBy('id', $this->sortDirection)->paginate(10),
            'totalAmount' => (int) (clone $query)->sum('amount'),
            'expenseCount' => (clone $query)->count(),
        ]);
    }
};
?>

<div>
    @include('pages.partials.crud-header', ['heading' => $title, 'description' => 'Catat biaya layanan '.ExpenseCategory::SERVICES[$service].'. Data dipisahkan dari layanan lainnya.'])
    <div class="mt-6 rounded-2xl border border-brand-100 bg-brand-50 p-5">
        <p class="text-sm font-semibold text-brand-700">Total Pengeluaran {{ ExpenseCategory::SERVICES[$service] }}</p>
        <p class="mt-2 break-words text-3xl font-extrabold text-brand-700">Rp{{ number_format($totalAmount, 0, ',', '.') }}</p>
        <p class="mt-2 text-sm text-slate-600">{{ $expenseCount }} transaksi · total seluruh hasil filter, termasuk halaman lainnya.</p>
    </div>
    <form wire:submit="applyFilters" class="mt-5 grid grid-cols-1 gap-4 rounded-2xl border border-slate-200 bg-white p-5 sm:grid-cols-2 xl:grid-cols-4">
        <div><label for="expense-search" class="mb-1 block text-sm font-semibold">Cari pengeluaran</label><input id="expense-search" wire:model="search" placeholder="Kategori atau keterangan" class="w-full rounded-xl border px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500">@error('search')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <div><label for="expense-filter-category" class="mb-1 block text-sm font-semibold">Kategori</label><select id="expense-filter-category" wire:model="categoryFilter" class="w-full rounded-xl border px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500"><option value="">Semua kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>@error('categoryFilter')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <div><label for="expense-start" class="mb-1 block text-sm font-semibold">Tanggal awal</label><input id="expense-start" type="date" wire:model="startDate" class="w-full rounded-xl border px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500">@error('startDate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <div><label for="expense-end" class="mb-1 block text-sm font-semibold">Tanggal akhir</label><input id="expense-end" type="date" wire:model="endDate" class="w-full rounded-xl border px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500">@error('endDate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <div class="flex flex-wrap gap-3 sm:col-span-2 xl:col-span-4">
            <button type="submit" wire:loading.attr="disabled" wire:target="applyFilters,clearFilters" class="rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-60"><span wire:loading.remove wire:target="applyFilters">Terapkan Filter</span><span wire:loading wire:target="applyFilters" role="status">Memfilter...</span></button>
            <button type="button" wire:click="clearFilters" wire:loading.attr="disabled" wire:target="applyFilters,clearFilters" class="rounded-xl border px-4 py-2.5 text-sm font-bold disabled:opacity-60"><span wire:loading.remove wire:target="clearFilters">Reset</span><span wire:loading wire:target="clearFilters" role="status">Mereset...</span></button>
        </div>
    </form>
    <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between"><h3 class="font-extrabold">Daftar Pengeluaran</h3><button type="button" wire:click="toggleDateSort" wire:loading.attr="disabled" wire:target="toggleDateSort" class="rounded-lg border px-3 py-2 text-sm font-semibold disabled:opacity-60"><span wire:loading.remove wire:target="toggleDateSort">Tanggal: {{ $sortDirection === 'desc' ? 'Terbaru dahulu' : 'Terlama dahulu' }}</span><span wire:loading wire:target="toggleDateSort" role="status">Mengurutkan...</span></button></div>
        <p wire:loading wire:target="applyFilters,clearFilters,toggleDateSort,gotoPage,nextPage,previousPage" role="status" class="px-5 py-3 text-sm font-semibold text-brand-600">Memuat daftar pengeluaran...</p>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[680px] text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Kategori / Keterangan</th><th class="px-5 py-3 text-right">Nominal</th><th class="px-5 py-3">Pencatat</th><th class="px-5 py-3 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse ($expenses as $expense)
                    <tr wire:key="expense-{{ $expense->id }}"><td class="whitespace-nowrap px-5 py-4">{{ $expense->expense_date->format('d M Y') }}</td><td class="max-w-xs px-5 py-4"><p class="break-words font-bold">{{ $expense->category->name }}</p><p class="mt-1 break-words text-slate-500">{{ $expense->description ?: 'Tanpa keterangan' }}</p></td><td class="whitespace-nowrap px-5 py-4 text-right font-bold">Rp{{ number_format($expense->amount, 0, ',', '.') }}</td><td class="px-5 py-4">{{ $expense->creator?->name ?? 'Akun sudah dihapus' }}</td><td class="px-5 py-4"><div class="flex justify-end gap-3 font-bold"><button type="button" wire:click="openEdit({{ $expense->id }})" wire:loading.attr="disabled" wire:target="openEdit({{ $expense->id }})" class="rounded text-brand-600 focus:ring-2 focus:ring-brand-500 disabled:opacity-60"><span wire:loading.remove wire:target="openEdit({{ $expense->id }})">Edit</span><span wire:loading wire:target="openEdit({{ $expense->id }})" role="status">Membuka...</span></button><button type="button" wire:click="confirmDelete({{ $expense->id }})" wire:loading.attr="disabled" wire:target="confirmDelete({{ $expense->id }})" class="rounded text-red-600 focus:ring-2 focus:ring-red-500 disabled:opacity-60"><span wire:loading.remove wire:target="confirmDelete({{ $expense->id }})">Hapus</span><span wire:loading wire:target="confirmDelete({{ $expense->id }})" role="status">Membuka...</span></button></div></td></tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Tidak ada pengeluaran sesuai filter yang dipilih.</td></tr>
                @endforelse
            </tbody></table>
        </div>
        <div class="border-t border-slate-100 p-5">{{ $expenses->links() }}</div>
    </div>
    @if ($modalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" role="dialog" aria-modal="true" aria-labelledby="expense-modal-title">
            <div class="max-h-[calc(100dvh-2rem)] w-full max-w-xl overflow-y-auto rounded-2xl bg-white p-5 shadow-xl sm:p-6">
                <h2 id="expense-modal-title" class="text-xl font-extrabold">{{ $editingId ? 'Edit' : 'Tambah' }} {{ $title }}</h2>
                <form wire:submit="save" class="mt-5 space-y-4">
                    @if ($categories->isEmpty())<p class="rounded-xl bg-amber-50 p-3 text-sm text-amber-800">Belum ada kategori untuk layanan ini. <a wire:navigate href="{{ route('expenses.categories') }}" class="font-bold underline">Buat kategori terlebih dahulu.</a></p>@endif
                    <div><label for="expense-category" class="mb-1 block text-sm font-semibold">Kategori {{ ExpenseCategory::SERVICES[$service] }}</label><select id="expense-category" wire:model="expenseCategoryId" class="w-full rounded-xl border px-4 py-3 text-sm focus:ring-2 focus:ring-brand-500"><option value="">Pilih kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>@error('expenseCategoryId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2"><div><label for="expense-date" class="mb-1 block text-sm font-semibold">Tanggal pengeluaran</label><input id="expense-date" type="date" wire:model="expenseDate" class="w-full rounded-xl border px-4 py-3 text-sm focus:ring-2 focus:ring-brand-500">@error('expenseDate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div><div><label for="expense-amount" class="mb-1 block text-sm font-semibold">Nominal (Rp)</label><input id="expense-amount" type="number" min="1" max="999999999999" step="1" wire:model="amount" placeholder="Contoh: 50000" class="w-full rounded-xl border px-4 py-3 text-sm focus:ring-2 focus:ring-brand-500">@error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div></div>
                    <div><label for="expense-description" class="mb-1 block text-sm font-semibold">Keterangan (opsional)</label><textarea id="expense-description" wire:model="description" rows="3" class="w-full rounded-xl border px-4 py-3 text-sm focus:ring-2 focus:ring-brand-500"></textarea>@error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    <p class="text-xs text-slate-500">Pencatat diisi otomatis. Nominal menggunakan rupiah tanpa pecahan.</p>
                    <div class="flex justify-end gap-3"><button type="button" wire:click="$set('modalOpen', false)" wire:loading.attr="disabled" wire:target="save" class="rounded-xl border px-4 py-3 text-sm font-bold disabled:opacity-60">Batal</button><button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-xl bg-brand-500 px-4 py-3 text-sm font-bold text-white disabled:opacity-60"><span wire:loading.remove wire:target="save">Simpan</span><span wire:loading wire:target="save" role="status">Menyimpan...</span></button></div>
                </form>
            </div>
        </div>
    @endif
    @if ($confirmDeleteOpen)
        <div class="fixed inset-0 z-60 flex items-center justify-center bg-slate-900/60 p-4" role="dialog" aria-modal="true" aria-labelledby="expense-delete-title"><div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl"><h2 id="expense-delete-title" class="text-lg font-bold">Hapus pengeluaran ini?</h2><p class="mt-2 text-sm text-slate-500">Data yang dihapus tidak dapat dikembalikan.</p><div class="mt-5 flex justify-end gap-3"><button type="button" wire:click="$set('confirmDeleteOpen', false)" wire:loading.attr="disabled" wire:target="delete" class="rounded-xl border px-4 py-2 text-sm font-bold disabled:opacity-60">Batal</button><button type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-60"><span wire:loading.remove wire:target="delete">Hapus</span><span wire:loading wire:target="delete" role="status">Menghapus...</span></button></div></div></div>
    @endif
</div>
