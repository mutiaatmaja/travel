<?php

use App\Models\Package;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] class extends Component
{
    use WithPagination;

    public string $title = 'Detil Keuangan Paket';
    public string $section = 'Paket';
    /** @var array{startDate: string, endDate: string, group: string, value: ?string, missing: bool} */
    #[Locked]
    public array $filters = [];
    #[Locked]
    public string $sortDirection = 'desc';

    public function mount(): void
    {
        $this->ensureAuthorized();
        $validator = validator(request()->query(), [
            'startDate' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'endDate' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'group' => ['sometimes', 'nullable', Rule::in(['category', 'financial_category', 'pricing_type', 'status'])],
            'value' => ['nullable', 'string', 'max:255'],
            'missing' => ['sometimes', 'boolean'],
        ]);
        abort_if($validator->fails(), 422);
        $input = $validator->validated();
        $this->filters = ['startDate' => $input['startDate'] ?? now()->startOfMonth()->toDateString(), 'endDate' => $input['endDate'] ?? now()->toDateString(), 'group' => $input['group'] ?? '', 'value' => $input['value'] ?? null, 'missing' => (bool) ($input['missing'] ?? false)];
        abort_if(validator($this->filters, ['endDate' => ['after_or_equal:startDate']])->fails(), 422);
        abort_if($this->filters['group'] !== '' && ! $this->filters['missing'] && $this->filters['value'] === null, 422);
        abort_if($this->filters['missing'] && ! in_array($this->filters['group'], ['category', 'financial_category'], true), 422);
    }

    public function toggleDateSort(): void
    {
        $this->ensureAuthorized();
        $this->sortDirection = $this->sortDirection === 'desc' ? 'asc' : 'desc';
        $this->resetPage();
    }

    private function ensureAuthorized(): void
    {
        abort_unless(auth()->user()?->hasPermission('packages.manage') && auth()->user()?->hasPermission('expenses.manage'), 403);
    }

    private function filteredQuery(): Builder
    {
        $query = Package::financeQuery($this->filters['startDate'], $this->filters['endDate'], $this->filters['group'] === 'status' && $this->filters['value'] === 'cancelled');
        if ($this->filters['group'] === 'pricing_type') {
            return $query->whereHas('packageSetting', fn (Builder $setting): Builder => $setting->where('pricing_type', $this->filters['value']));
        }
        if ($this->filters['group'] !== '') {
            return $this->filters['missing'] ? $query->whereNull($this->filters['group']) : $query->where($this->filters['group'], $this->filters['value']);
        }
        return $query;
    }

    public function render(): mixed
    {
        $this->ensureAuthorized();
        $query = $this->filteredQuery();
        return view('pages.packages.⚡finance-detail', ['totals' => Package::financeSummary($query), 'packages' => $query->with('packageSetting')->orderBy('created_at', $this->sortDirection)->orderBy('id', $this->sortDirection)->paginate(10)]);
    }
};
?>

<div>
    <a wire:navigate href="{{ route('packages.finance', ['startDate' => $filters['startDate'], 'endDate' => $filters['endDate']]) }}" class="rounded text-sm font-bold text-brand-700 focus:ring-2 focus:ring-brand-500">← Ringkasan Keuangan</a>
    <h2 class="mt-4 text-2xl font-extrabold">Detil Keuangan Paket</h2>
    @php
        $groupLabels = ['category' => 'Kategori Paket', 'financial_category' => 'Kategori Keuangan', 'pricing_type' => 'Jenis Tarif', 'status' => 'Status Paket'];
        $valueLabels = ['weight' => 'Berat', 'volume' => 'Volumetrik', 'pending' => 'Pending', 'pickup' => 'Pickup', 'in_transit' => 'Dalam Perjalanan', 'delivered' => 'Terkirim', 'cancelled' => 'Dibatalkan'];
        $selectedLabel = $filters['missing'] ? 'Belum dikategorikan' : (in_array($filters['group'], ['pricing_type', 'status'], true) ? ($valueLabels[$filters['value']] ?? $filters['value']) : $filters['value']);
    @endphp
    <p class="mt-2 text-sm text-slate-600">{{ $filters['group'] === '' ? 'Seluruh paket, kecuali dibatalkan' : $groupLabels[$filters['group']].': '.$selectedLabel }}</p>
    <p class="mt-1 text-xs text-slate-500">Tanggal dibuat: {{ $filters['startDate'] }} s.d. {{ $filters['endDate'] }}</p>
    <section class="mt-5 rounded-2xl border border-brand-100 bg-brand-50 p-5"><p class="text-sm text-brand-700">Nilai Ongkos Sesuai Filter</p><p class="mt-2 text-2xl font-extrabold text-brand-700">Rp{{ number_format($totals['amount'], 0, ',', '.') }}</p><p class="mt-2 text-xs text-slate-600">{{ $totals['count'] }} paket · total seluruh halaman · berdasarkan tarif saat ini</p></section>
    <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between"><h3 class="font-bold">Daftar Paket</h3><button type="button" wire:click="toggleDateSort" wire:loading.attr="disabled" wire:target="toggleDateSort" class="rounded-lg border px-3 py-2 text-sm font-semibold disabled:opacity-60"><span wire:loading.remove wire:target="toggleDateSort">Tanggal dibuat: {{ $sortDirection === 'desc' ? 'Terbaru dahulu' : 'Terlama dahulu' }}</span><span wire:loading wire:target="toggleDateSort" role="status">Mengurutkan...</span></button></div>
        <p wire:loading wire:target="toggleDateSort,gotoPage,nextPage,previousPage" role="status" class="px-4 py-3 text-sm text-brand-600">Memuat daftar paket...</p>
        <div class="overflow-x-auto"><table class="w-full min-w-[1050px] text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Nomor / Tanggal Dibuat</th><th class="px-4 py-3">Pelanggan</th><th class="px-4 py-3">Kategori Paket</th><th class="px-4 py-3">Kategori Keuangan</th><th class="px-4 py-3">Tarif / Jenis</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Ongkos</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse ($packages as $package)
                <tr wire:key="finance-package-{{ $package->id }}"><td class="px-4 py-4"><p class="font-bold">{{ $package->code }}</p><p class="mt-1 text-xs text-slate-500">{{ $package->created_at->format('d M Y, H:i') }}</p></td><td class="px-4 py-4">{{ $package->customer_name }}</td><td class="px-4 py-4">{{ $package->category ?? 'Belum dikategorikan' }}</td><td class="px-4 py-4">{{ $package->financial_category ?? 'Belum dikategorikan' }}</td><td class="px-4 py-4">{{ $package->packageSetting?->name ?? 'Tarif tidak tersedia' }}<p class="mt-1 text-xs text-slate-500">{{ $valueLabels[$package->packageSetting?->pricing_type] ?? '—' }}</p></td><td class="px-4 py-4">{{ $valueLabels[$package->status] ?? $package->status }}</td><td class="whitespace-nowrap px-4 py-4 text-right font-bold">Rp{{ number_format($package->calculateTotalCost(), 0, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">Tidak ada paket sesuai periode dan kelompok ini.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="border-t border-slate-100 p-4">{{ $packages->links() }}</div>
    </div>
</div>