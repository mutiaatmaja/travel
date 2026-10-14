<?php

use App\Models\Package;
use App\Models\PackageSetting;
use App\Models\PackageCategory;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] class extends Component {
    use WithPagination;

    public string $title = 'Semua Paket';

    public string $section = 'Paket';

    public string $search = '';

    public bool $modalOpen = false;

    public bool $confirmDeleteOpen = false;

    public ?int $editingId = null;

    public ?int $deleteId = null;

    public ?int $packageSettingId = null;

    public string $code = '';

    public string $customerName = '';

    public string $category = '';

    public string $financialCategory = '';

    public float $weightKg = 0;

    public float $lengthCm = 0;

    public float $widthCm = 0;

    public float $heightCm = 0;

    public string $status = 'pending';

    public string $description = '';

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
        $package = Package::findOrFail($id);

        $this->editingId = $id;
        $this->packageSettingId = $package->package_setting_id;
        $this->code = $package->code;
        $this->customerName = $package->customer_name;
        $this->category = $package->category ?? '';
        $this->financialCategory = $package->financial_category ?? '';
        $this->weightKg = (float) $package->weight_kg;
        $this->lengthCm = (float) $package->length_cm;
        $this->widthCm = (float) $package->width_cm;
        $this->heightCm = (float) $package->height_cm;
        $this->status = $package->status;
        $this->description = $package->description ?? '';

        $this->resetValidation();
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $this->validate([
            'packageSettingId' => ['required', 'exists:package_settings,id'],
            'customerName' => ['required', 'max:255'],
            'category' => ['required', Rule::exists('package_categories', 'name')->where('type', 'package')],
            'financialCategory' => ['required', Rule::exists('package_categories', 'name')->where('type', 'financial')],
            'weightKg' => ['required', 'numeric', 'min:0'],
            'lengthCm' => ['required', 'numeric', 'min:0'],
            'widthCm' => ['required', 'numeric', 'min:0'],
            'heightCm' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:pending,pickup,in_transit,delivered,cancelled'],
            'description' => ['nullable', 'string'],
        ]);

        $package = Package::updateOrCreate(
            [
                'id' => $this->editingId,
            ],
            [
                'package_setting_id' => $this->packageSettingId,
                'customer_name' => $this->customerName,
                'category' => $this->category,
                'financial_category' => $this->financialCategory,
                'weight_kg' => $this->weightKg,
                'length_cm' => $this->lengthCm,
                'width_cm' => $this->widthCm,
                'height_cm' => $this->heightCm,
                'status' => $this->status,
                'description' => $this->description,
            ],
        );

        $message = $this->editingId ? 'Paket berhasil diperbarui.' : 'Paket berhasil ditambahkan.';
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
        Package::findOrFail($this->deleteId)->delete();
        $this->confirmDeleteOpen = false;
        $this->deleteId = null;
        session()->flash('toast', ['type' => 'success', 'message' => 'Paket berhasil dihapus.']);
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
        $this->reset(['modalOpen', 'editingId', 'deleteId', 'packageSettingId', 'code', 'customerName', 'weightKg', 'lengthCm', 'widthCm', 'heightCm', 'status', 'description']);
        $this->weightKg = 0;
        $this->lengthCm = 0;
        $this->widthCm = 0;
        $this->heightCm = 0;
        $this->status = 'pending';
        $this->category = '';
        $this->financialCategory = '';
        $this->description = '';
        $this->resetValidation();
    }

    public function render(): mixed
    {
        return view('pages.packages.⚡index', [
            'packages' => Package::with('packageSetting')
                ->when($this->search !== '', fn($query) => $query->where('code', 'like', '%' . $this->search . '%')->orWhere('customer_name', 'like', '%' . $this->search . '%'))
                ->latest()
                ->paginate(10),
            'packageSettings' => PackageSetting::where('is_active', true)->orderBy('name')->get(),
            'packageCategories' => PackageCategory::where('type', 'package')->orderBy('name')->get(),
            'financialCategories' => PackageCategory::where('type', 'financial')->orderBy('name')->get(),
        ]);
    }
};
?>

<div>
    @include('pages.partials.crud-header', [
        'heading' => 'Semua Paket',
        'description' => 'Kelola paket dan kalkulasi biaya otomatis dari setting tarif.',
    ])

    <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="font-extrabold">Daftar Paket</h3>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <input wire:model.live.debounce.300ms="search" placeholder="Cari kode atau pelanggan..."
                    class="w-full rounded-xl border px-4 py-2.5 text-sm sm:max-w-xs">
                <button type="button" wire:click="openCreate" wire:loading.attr="disabled"
                    class="rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-bold text-white">
                    <span wire:loading.remove wire:target="openCreate">Tambah</span>
                    <span wire:loading wire:target="openCreate">Membuka...</span>
                </button>
            </div>
        </div>

        <div class="relative overflow-x-auto">
            <div wire:loading wire:target="search,save,openEdit,confirmDelete,delete"
                class="absolute inset-0 z-10 flex items-center justify-center bg-white/70">
                <span class="rounded-xl bg-white px-4 py-3 text-sm font-semibold shadow-lg">Memuat data...</span>
            </div>

            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Kode</th>
                        <th class="px-6 py-4">Pelanggan</th>
                        <th class="px-6 py-4">Kategori Paket</th>
                        <th class="px-6 py-4">Kategori Keuangan</th>
                        <th class="px-6 py-4">Tarif</th>
                        <th class="px-6 py-4">Berat</th>
                        <th class="px-6 py-4">Dimensi</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Biaya</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($packages as $package)
                        <tr>
                            <td class="px-6 py-4 font-bold">{{ $package->code }}</td>
                            <td class="px-6 py-4">{{ $package->customer_name }}</td>
                            <td class="px-6 py-4">{{ $package->category ?? 'Belum dikategorikan' }}</td>
                            <td class="px-6 py-4">{{ $package->financial_category ?? 'Belum dikategorikan' }}</td>
                            <td class="px-6 py-4">{{ $package->packageSetting?->name ?? '-' }}</td>
                            <td class="px-6 py-4">{{ $package->weight_kg }} kg</td>
                            <td class="px-6 py-4">
                                {{ $package->length_cm }}×{{ $package->width_cm }}×{{ $package->height_cm }} cm</td>
                            <td class="px-6 py-4">
                                <span
                                    class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-700">{{ ucfirst(str_replace('_', ' ', $package->status)) }}</span>
                            </td>
                            <td class="px-6 py-4 font-bold text-brand-700">
                                Rp{{ number_format($package->calculateTotalCost(), 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="openEdit({{ $package->id }})"
                                    class="px-2 text-xs font-bold text-brand-600">Edit</button>
                                <button wire:click="confirmDelete({{ $package->id }})"
                                    class="px-2 text-xs font-bold text-red-600">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-slate-500">Belum ada paket.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-6 py-4">{{ $packages->links() }}</div>
    </div>

    @if ($modalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 sm:p-4" role="dialog"
            aria-modal="true" aria-labelledby="package-modal-title">
            <div class="flex h-[100dvh] w-full max-w-7xl flex-col overflow-hidden bg-white shadow-2xl sm:h-[calc(100dvh-2rem)] sm:rounded-2xl">
                <header class="flex shrink-0 items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div>
                        <h2 id="package-modal-title" class="text-xl font-extrabold text-slate-900">{{ $editingId ? 'Edit' : 'Tambah' }} Paket</h2>
                        <p class="mt-1 text-sm text-slate-500">Lengkapi data paket, kategori laporan, dan ukuran untuk menghitung ongkos.</p>
                    </div>
                    <button type="button" wire:click="$set('modalOpen', false)" wire:loading.attr="disabled" wire:target="save"
                        aria-label="Tutup modal" class="rounded-lg px-3 py-2 text-2xl text-slate-500 hover:bg-slate-100 focus:ring-2 focus:ring-brand-500 disabled:opacity-60">&times;</button>
                </header>
                <form wire:submit="save" class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain bg-slate-50 p-5 sm:p-6">
                        <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-3">
                            <div class="space-y-5 lg:col-span-2">
                                <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                                    <h3 class="font-bold text-slate-900">Informasi Paket</h3>
                                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="package-code" class="mb-1 block text-sm font-semibold">Nomor paket</label>
                                            <input id="package-code" wire:model="code" readonly placeholder="Dibuat otomatis saat disimpan"
                                                class="w-full rounded-lg border bg-slate-50 px-3 py-2.5 text-sm text-slate-500">
                                        </div>
                                        <div>
                                            <label for="package-customer" class="mb-1 block text-sm font-semibold">Nama pelanggan</label>
                                            <input id="package-customer" wire:model="customerName" placeholder="Nama penerima atau pengirim"
                                                class="w-full rounded-lg border px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                                            @error('customerName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="package-description" class="mb-1 block text-sm font-semibold">Deskripsi paket <span class="font-normal text-slate-400">(opsional)</span></label>
                                            <textarea id="package-description" wire:model="description" rows="2" placeholder="Isi paket atau keterangan penanganan"
                                                class="w-full rounded-lg border px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-100"></textarea>
                                            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                </section>
                                <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                                    <h3 class="font-bold text-slate-900">Kategori & Status</h3>
                                    <p class="mt-1 text-xs text-slate-500">Kategori digunakan untuk pengelompokan laporan.</p>
                                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <div>
                                            <label for="package-category" class="mb-1 block text-sm font-semibold">Kategori Paket</label>
                                            <select id="package-category" wire:model="category" class="w-full rounded-lg border px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                                                <option value="">Pilih kategori paket</option>
                                                @foreach ($packageCategories as $option)
                                                    <option value="{{ $option->name }}">{{ $option->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="package-financial-category" class="mb-1 block text-sm font-semibold">Kategori Keuangan</label>
                                            <select id="package-financial-category" wire:model="financialCategory" class="w-full rounded-lg border px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                                                <option value="">Pilih kategori keuangan</option>
                                                @foreach ($financialCategories as $option)
                                                    <option value="{{ $option->name }}">{{ $option->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('financialCategory') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="package-status" class="mb-1 block text-sm font-semibold">Status paket</label>
                                            <select id="package-status" wire:model="status" class="w-full rounded-lg border px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                                                <option value="pending">Pending</option>
                                                <option value="pickup">Pickup</option>
                                                <option value="in_transit">Dalam perjalanan</option>
                                                <option value="delivered">Terkirim</option>
                                                <option value="cancelled">Dibatalkan</option>
                                            </select>
                                            @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                </section>
                                <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                                    <h3 class="font-bold text-slate-900">Berat & Dimensi</h3>
                                    <p class="mt-1 text-xs text-slate-500">Masukkan berat aktual dalam kg dan ukuran paket dalam cm.</p>
                                    <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                                        @foreach (['weightKg' => 'Berat (kg)', 'lengthCm' => 'Panjang (cm)', 'widthCm' => 'Lebar (cm)', 'heightCm' => 'Tinggi (cm)'] as $field => $label)
                                            <div wire:key="package-measurement-{{ $field }}">
                                                <label for="package-{{ $field }}" class="mb-1 block text-sm font-semibold">{{ $label }}</label>
                                                <input id="package-{{ $field }}" wire:model.live="{{ $field }}" type="number" step="0.01" min="0"
                                                    placeholder="0,00" class="w-full rounded-lg border px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                                                @error($field) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            </div>
                            <aside class="space-y-4 lg:sticky lg:top-0">
                                <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                                    <h3 class="font-bold text-slate-900">Tarif & Estimasi Biaya</h3>
                                    <label for="package-setting" class="mb-1 mt-4 block text-sm font-semibold">Pengaturan tarif</label>
                                    <select id="package-setting" wire:model.live="packageSettingId" class="w-full rounded-lg border px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                                        <option value="">Pilih pengaturan tarif</option>
                                        @foreach ($packageSettings as $setting)
                                            <option value="{{ $setting->id }}">{{ $setting->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('packageSettingId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    <div class="mt-4 rounded-xl border border-brand-100 bg-brand-50 p-4 text-sm" aria-live="polite">
                                        <p class="font-bold text-brand-700">Estimasi biaya</p>
                                        @php
                                            $selectedSetting = $packageSettings->firstWhere('id', (int) $packageSettingId);
                                        @endphp
                                        @if ($selectedSetting)
                                            @php
                                                $estimatedPackage = new App\Models\Package([
                                                    'package_setting_id' => $selectedSetting->id,
                                                    'weight_kg' => $weightKg,
                                                    'length_cm' => $lengthCm,
                                                    'width_cm' => $widthCm,
                                                    'height_cm' => $heightCm,
                                                ]);
                                                $estimatedPackage->setRelation('packageSetting', $selectedSetting);
                                                $estimated = $estimatedPackage->calculateTotalCost();
                                            @endphp
                                            <p class="mt-2 text-2xl font-extrabold text-brand-700">Rp{{ number_format($estimated, 0, ',', '.') }}</p>
                                            <dl class="mt-4 space-y-3 text-xs text-slate-600">
                                                <div><dt class="font-semibold">Metode perhitungan</dt><dd class="mt-1">{{ $selectedSetting->pricing_type === 'volume' ? 'Berat volumetrik' : 'Berat aktual' }}</dd></div>
                                                <div><dt class="font-semibold">Tarif</dt><dd class="mt-1">Rp{{ number_format((int) ($selectedSetting->rate_per_kg ?? ($selectedSetting->rate_per_m3 ?? 0)), 0, ',', '.') }} / {{ $selectedSetting->pricing_type === 'volume' ? 'kg volumetrik' : 'kg' }}</dd></div>
                                                <div><dt class="font-semibold">Biaya minimum</dt><dd class="mt-1">Rp{{ number_format((int) $selectedSetting->minimum_charge, 0, ',', '.') }}</dd></div>
                                                @if ($selectedSetting->pricing_type === 'volume')
                                                    <div><dt class="font-semibold">Berat volumetrik</dt><dd class="mt-1">{{ number_format(($lengthCm * $widthCm * $heightCm) / max((int) ($selectedSetting->volumetric_divisor ?? 6000), 1), 2, ',', '.') }} kg</dd></div>
                                                    <div><dt class="font-semibold">Rumus</dt><dd class="mt-1">Panjang × lebar × tinggi ÷ {{ number_format((int) ($selectedSetting->volumetric_divisor ?? 6000), 0, ',', '.') }}</dd></div>
                                                @endif
                                            </dl>
                                        @else
                                            <p class="mt-2 text-slate-600">Pilih tarif dan isi ukuran paket untuk melihat estimasi ongkos.</p>
                                        @endif
                                    </div>
                                    <p wire:loading wire:target="packageSettingId,weightKg,lengthCm,widthCm,heightCm" role="status" class="mt-3 text-xs text-brand-600">Memperbarui estimasi...</p>
                                </section>
                            </aside>
                        </div>
                    </div>
                    <footer class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 bg-white px-5 py-4 sm:px-6">
                        <button type="button" wire:click="$set('modalOpen', false)" wire:loading.attr="disabled" wire:target="save"
                            class="rounded-xl border px-5 py-2.5 text-sm font-bold focus:ring-2 focus:ring-brand-500 disabled:opacity-60">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="save"
                            class="rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-bold text-white focus:ring-2 focus:ring-brand-500 disabled:opacity-60">
                            <span wire:loading.remove wire:target="save">Simpan Paket</span>
                            <span wire:loading wire:target="save" role="status">Menyimpan...</span>
                        </button>
                    </footer>
                </form>
            </div>
        </div>
    @endif

    @if ($confirmDeleteOpen)
        <div class="fixed inset-0 z-60 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="font-extrabold">Hapus paket?</h2>
                <div class="mt-5 flex justify-end gap-3">
                    <button wire:click="$set('confirmDeleteOpen', false)"
                        class="rounded-xl border px-4 py-2 text-sm font-bold">Batal</button>
                    <button wire:click="delete"
                        class="rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white">Hapus</button>
                </div>
            </div>
        </div>
    @endif
</div>
