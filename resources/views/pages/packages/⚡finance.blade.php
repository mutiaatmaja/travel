<?php

use App\Models\Expense;
use App\Models\Package;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts::admin')] class extends Component
{
    public string $title = 'Keuangan Paket';
    public string $section = 'Paket';
    public string $startDate = '';
    public string $endDate = '';
    /** @var array{startDate: string, endDate: string} */
    #[Locked]
    public array $period = [];

    public function mount(): void
    {
        $this->ensureAuthorized();
        $validator = validator(request()->query(), ['startDate' => ['sometimes', 'required', 'date_format:Y-m-d'], 'endDate' => ['sometimes', 'required', 'date_format:Y-m-d']]);
        abort_if($validator->fails(), 422);
        $dates = $validator->validated();
        $this->startDate = $dates['startDate'] ?? now()->startOfMonth()->toDateString();
        $this->endDate = $dates['endDate'] ?? now()->toDateString();
        $periodValidator = validator(['startDate' => $this->startDate, 'endDate' => $this->endDate], ['endDate' => ['after_or_equal:startDate']]);
        abort_if($periodValidator->fails(), 422);
        $this->period = ['startDate' => $this->startDate, 'endDate' => $this->endDate];
    }

    public function applyPeriod(): void
    {
        $this->ensureAuthorized();
        $this->period = $this->validate(['startDate' => ['required', 'date_format:Y-m-d'], 'endDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:startDate']]);
    }

    private function ensureAuthorized(): void
    {
        abort_unless(auth()->user()?->hasPermission('packages.manage') && auth()->user()?->hasPermission('expenses.manage'), 403);
    }

    public function render(): mixed
    {
        $this->ensureAuthorized();
        $summary = Package::financeSummary(Package::financeQuery($this->period['startDate'], $this->period['endDate']));
        $cancelled = Package::financeSummary(Package::financeQuery($this->period['startDate'], $this->period['endDate'], true)->where('status', 'cancelled'));
        $summary['groups']['status']['"cancelled"'] = ['value' => 'cancelled', 'amount' => $cancelled['amount'], 'count' => $cancelled['count']];
        $expenseQuery = Expense::whereHas('category', fn (Builder $query): Builder => $query->where('service', 'package'))
            ->where('expense_date', '>=', $this->period['startDate'])
            ->where('expense_date', '<', Carbon::parse($this->period['endDate'])->addDay()->toDateString());
        $expenseAmount = (int) (clone $expenseQuery)->sum('amount');
        return view('pages.packages.⚡finance', ['summary' => $summary, 'expenseAmount' => $expenseAmount, 'expenseCount' => $expenseQuery->count(), 'balance' => $summary['amount'] - $expenseAmount]);
    }
};
?>

<div class="mx-auto max-w-5xl">
    <h2 class="text-2xl font-extrabold text-slate-900">Keuangan Paket</h2>
    <p class="mt-2 text-sm text-slate-500">Ringkasan ongkos dan pengeluaran khusus layanan Paket.</p>
    <form wire:submit="applyPeriod" class="mt-5 grid grid-cols-2 gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-3 sm:items-end">
        <div><label for="finance-start" class="mb-1 block text-sm font-semibold">Dari tanggal</label><input id="finance-start" type="date" wire:model="startDate" class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500">@error('startDate')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <div><label for="finance-end" class="mb-1 block text-sm font-semibold">Sampai tanggal</label><input id="finance-end" type="date" wire:model="endDate" class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500">@error('endDate')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <button type="submit" wire:loading.attr="disabled" wire:target="applyPeriod" class="col-span-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-60 sm:col-span-1"><span wire:loading.remove wire:target="applyPeriod">Terapkan Periode</span><span wire:loading wire:target="applyPeriod" role="status">Memuat ringkasan...</span></button>
    </form>
    <p class="mt-3 text-xs text-slate-500">Periode aktif: {{ $period['startDate'] }} s.d. {{ $period['endDate'] }}. Ongkos berdasarkan tanggal paket dibuat; biaya berdasarkan tanggal pengeluaran.</p>
    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <section class="rounded-2xl border border-brand-100 bg-brand-50 p-5"><h3 class="text-sm font-semibold text-brand-700">Nilai Ongkos Paket</h3><p class="mt-2 break-words text-2xl font-extrabold text-brand-700">Rp{{ number_format($summary['amount'], 0, ',', '.') }}</p><p class="mt-2 text-xs text-slate-600">{{ $summary['count'] }} paket · tanpa paket dibatalkan</p><a wire:navigate href="{{ route('packages.finance.detail', $period) }}" class="mt-4 inline-block rounded text-sm font-bold text-brand-700 focus:ring-2 focus:ring-brand-500">Lebih Detil →</a></section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5"><h3 class="text-sm font-semibold text-slate-600">Pengeluaran Paket</h3><p class="mt-2 break-words text-2xl font-extrabold text-slate-900">Rp{{ number_format($expenseAmount, 0, ',', '.') }}</p><p class="mt-2 text-xs text-slate-500">{{ $expenseCount }} transaksi pengeluaran</p><a wire:navigate href="{{ route('expenses.index', ['service' => 'package', ...$period]) }}" class="mt-4 inline-block rounded text-sm font-bold text-brand-700 focus:ring-2 focus:ring-brand-500">Lebih Detil →</a></section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5"><h3 class="text-sm font-semibold text-slate-600">Selisih Sementara</h3><p class="mt-2 break-words text-2xl font-extrabold {{ $balance < 0 ? 'text-red-600' : 'text-slate-900' }}">Rp{{ number_format($balance, 0, ',', '.') }}</p><p class="mt-2 text-xs text-slate-500">Nilai ongkos dikurangi pengeluaran Paket.</p></section>
    </div>
    <p class="mt-4 rounded-xl bg-amber-50 p-4 text-xs leading-relaxed text-amber-900">Nilai ongkos mengikuti tarif saat ini, belum menunjukkan pembayaran diterima. Selisih bukan keuntungan aktual. Biaya belum dialokasikan per kategori.</p>
    @if ($summary['count'] === 0)<p class="mt-4 text-sm text-slate-500">Tidak ada paket aktif dalam periode ini. Pengeluaran tetap ditampilkan jika tersedia.</p>@endif
    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach (['category' => 'Kategori Paket', 'financial_category' => 'Kategori Keuangan', 'pricing_type' => 'Jenis Tarif', 'status' => 'Status Paket'] as $group => $heading)
            <section wire:key="finance-group-{{ $group }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <h3 class="border-b border-slate-100 px-5 py-4 font-extrabold">{{ $heading }}</h3>
                <ul class="divide-y divide-slate-100">
                    @forelse ($summary['groups'][$group] as $row)
                        @php
                            $labels = ['weight' => 'Berat', 'volume' => 'Volumetrik', 'pending' => 'Pending', 'pickup' => 'Pickup', 'in_transit' => 'Dalam Perjalanan', 'delivered' => 'Terkirim', 'cancelled' => 'Dibatalkan'];
                            $label = $row['value'] === null ? 'Belum dikategorikan' : (in_array($group, ['pricing_type', 'status'], true) ? ($labels[$row['value']] ?? $row['value']) : $row['value']);
                            $detailParameters = [...$period, 'group' => $group, 'value' => $row['value'], 'missing' => $row['value'] === null ? 1 : 0];
                        @endphp
                        <li wire:key="finance-row-{{ $group }}-{{ md5(json_encode($row['value'])) }}" class="px-5 py-4">
                            <div class="flex items-start justify-between gap-3"><p class="min-w-0 break-words text-sm font-bold">{{ $label }}</p><p class="shrink-0 text-sm font-bold">Rp{{ number_format($row['amount'], 0, ',', '.') }}</p></div>
                            <div class="mt-2 flex items-center justify-between gap-3"><p class="text-xs text-slate-500">{{ $row['count'] }} paket{{ $group === 'status' && $row['value'] === 'cancelled' ? ' · di luar total utama' : '' }}</p><a wire:navigate href="{{ route('packages.finance.detail', $detailParameters) }}" class="shrink-0 rounded text-xs font-bold text-brand-700 focus:ring-2 focus:ring-brand-500">Lebih Detil →</a></div>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-sm text-slate-500">Belum ada data.</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
</div>