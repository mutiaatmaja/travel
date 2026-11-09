<?php

use App\Models\Package;
use App\Models\PackageSetting;
use App\Models\PackageCategory;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] class extends Component {
    use WithPagination;

    public string $title = 'Semua Paket';

    public string $section = 'Paket';

    public string $search = '';

    public bool $modalOpen = false;

    public bool $confirmDeleteOpen = false;

    #[Locked]
    public ?int $editingId = null;

    public ?int $deleteId = null;

    public ?int $packageSettingId = null;

    #[Locked]
    public string $legacyCustomerName = '';

    public string $senderName = '';

    public string $senderPhone = '';

    public string $senderAddress = '';

    public string $recipientName = '';

    public string $recipientPhone = '';

    public string $recipientAddress = '';

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
        abort_unless(auth()->user()?->hasPermission('packages.manage'), 403);
        $this->resetForm();
        $this->modalOpen = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()?->hasPermission('packages.manage'), 403);
        $package = Package::findOrFail($id);

        $this->editingId = $id;
        $this->packageSettingId = $package->package_setting_id;
        $this->legacyCustomerName = $package->customer_name;
        $this->senderName = $package->sender_name ?? '';
        $this->senderPhone = $package->sender_phone ?? '';
        $this->senderAddress = $package->sender_address ?? '';
        $this->recipientName = $package->recipient_name ?? '';
        $this->recipientPhone = $package->recipient_phone ?? '';
        $this->recipientAddress = $package->recipient_address ?? '';
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
        abort_unless(auth()->user()?->hasPermission('packages.manage'), 403);
        $this->senderName = trim($this->senderName);
        $this->senderPhone = trim($this->senderPhone);
        $this->senderAddress = trim($this->senderAddress);
        $this->recipientName = trim($this->recipientName);
        $this->recipientPhone = trim($this->recipientPhone);
        $this->recipientAddress = trim($this->recipientAddress);
        $this->validate([
            'packageSettingId' => ['required', 'exists:package_settings,id'],
            'senderName' => ['required', 'string', 'max:255'],
            'senderPhone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9][0-9 ()-]{6,28}[0-9]$/'],
            'senderAddress' => ['required', 'string', 'max:2000'],
            'recipientName' => ['required', 'string', 'max:255'],
            'recipientPhone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9][0-9 ()-]{6,28}[0-9]$/'],
            'recipientAddress' => ['required', 'string', 'max:2000'],
            'category' => ['required', Rule::exists('package_categories', 'name')->where('type', 'package')],
            'financialCategory' => ['required', Rule::exists('package_categories', 'name')->where('type', 'financial')],
            'weightKg' => ['required', 'numeric', 'min:0'],
            'lengthCm' => ['required', 'numeric', 'min:0'],
            'widthCm' => ['required', 'numeric', 'min:0'],
            'heightCm' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:pending,pickup,in_transit,delivered,cancelled'],
            'description' => ['nullable', 'string'],
        ]);

        $package = $this->editingId ? Package::findOrFail($this->editingId) : new Package;
        if (! $package->exists) {
            $package->customer_name = $this->senderName;
        }
        $package->fill(
            [
                'package_setting_id' => $this->packageSettingId,
                'sender_name' => $this->senderName,
                'sender_phone' => $this->senderPhone,
                'sender_address' => $this->senderAddress,
                'recipient_name' => $this->recipientName,
                'recipient_phone' => $this->recipientPhone,
                'recipient_address' => $this->recipientAddress,
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
        $package->save();

        $message = $this->editingId ? 'Paket '.$package->code.' berhasil diperbarui.' : 'Paket '.$package->code.' berhasil ditambahkan.';
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
        $this->reset(['modalOpen', 'editingId', 'deleteId', 'packageSettingId', 'legacyCustomerName', 'senderName', 'senderPhone', 'senderAddress', 'recipientName', 'recipientPhone', 'recipientAddress', 'weightKg', 'lengthCm', 'widthCm', 'heightCm', 'status', 'description']);
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
                ->when($this->search !== '', fn ($query) => $query->where(fn ($names) => $names->where('code', 'like', '%'.$this->search.'%')->orWhere('customer_name', 'like', '%'.$this->search.'%')->orWhere('sender_name', 'like', '%'.$this->search.'%')->orWhere('recipient_name', 'like', '%'.$this->search.'%')))
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
                <input wire:model.live.debounce.300ms="search" placeholder="Cari nomor, pengirim, atau penerima..."
                    class="w-full rounded-xl border px-4 py-2.5 text-sm sm:max-w-xs">
                <button type="button" wire:click="openCreate" wire:loading.attr="disabled"
                    class="rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-bold text-white">
                    <span wire:loading.remove wire:target="openCreate">Tambah</span>
                    <span wire:loading wire:target="openCreate">Membuka...</span>
                </button>
            </div>
        </div>

        <div class="relative">
            <div wire:loading.flex wire:target="search,save,delete" class="absolute inset-0 z-10 items-center justify-center bg-white/80" role="status">
                <span class="rounded-xl bg-white px-4 py-3 text-sm font-semibold shadow-lg">Memuat data...</span>
            </div>
            <table class="block w-full text-left text-sm lg:table lg:table-fixed">
                <thead class="hidden bg-slate-50 text-xs uppercase text-slate-500 lg:table-header-group">
                    <tr>
                        <th class="w-[22%] px-4 py-3">Paket</th>
                        <th class="w-[27%] px-4 py-3">Pengiriman</th>
                        <th class="w-[16%] px-4 py-3">Kategori</th>
                        <th class="w-[18%] px-4 py-3">Ongkos</th>
                        <th class="w-[17%] px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="block divide-y divide-slate-100 lg:table-row-group">
                    @forelse ($packages as $package)
                        @php
                            $statusLabels = ['pending' => 'Pending', 'pickup' => 'Pickup', 'in_transit' => 'Dalam Perjalanan', 'delivered' => 'Terkirim', 'cancelled' => 'Dibatalkan'];
                            $statusClass = match ($package->status) {
                                'delivered' => 'bg-green-50 text-green-700',
                                'cancelled' => 'bg-red-50 text-red-700',
                                default => 'bg-brand-50 text-brand-700',
                            };
                        @endphp
                        <tr wire:key="package-list-{{ $package->id }}" class="grid grid-cols-2 gap-3 p-4 lg:table-row lg:p-0">
                            <td class="col-span-2 min-w-0 lg:px-4 lg:py-4 lg:align-top">
                                <div class="flex flex-wrap items-center gap-2 lg:block">
                                    <p class="break-words font-extrabold text-slate-900">{{ $package->code }}</p>
                                    <span class="inline-block rounded-full px-2 py-1 text-[11px] font-bold lg:mt-2 {{ $statusClass }}">{{ $statusLabels[$package->status] ?? $package->status }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $package->created_at?->format('d M Y, H:i') }}</p>
                            </td>
                            <td class="col-span-2 min-w-0 lg:px-4 lg:py-4 lg:align-top">
                                <p class="text-xs text-slate-500">Pengirim → Penerima</p>
                                <p class="mt-1 break-words font-semibold">{{ $package->sender_name ?? 'Belum dilengkapi' }} → {{ $package->recipient_name ?? 'Belum dilengkapi' }}</p>
                                <details wire:key="package-details-{{ $package->id }}" class="mt-2">
                                    <summary class="cursor-pointer rounded text-xs font-bold text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500">Detail Paket</summary>
                                    <dl class="mt-3 space-y-3 rounded-xl bg-slate-50 p-3 text-xs">
                                        <div><dt class="font-bold text-slate-700">No. HP Pengirim</dt><dd class="mt-1 text-slate-600">{{ $package->sender_phone ?? 'Belum dilengkapi' }}</dd></div>
                                        <div><dt class="font-bold text-slate-700">Alamat Asal</dt><dd class="mt-1 whitespace-pre-line break-words text-slate-600">{{ $package->sender_address ?? 'Alamat belum dilengkapi' }}</dd></div>
                                        <div><dt class="font-bold text-slate-700">No. HP Penerima</dt><dd class="mt-1 text-slate-600">{{ $package->recipient_phone ?? 'Belum dilengkapi' }}</dd></div>
                                        <div><dt class="font-bold text-slate-700">Alamat Tujuan</dt><dd class="mt-1 whitespace-pre-line break-words text-slate-600">{{ $package->recipient_address ?? 'Alamat belum dilengkapi' }}</dd></div>
                                        <div><dt class="font-bold text-slate-700">Berat / Dimensi</dt><dd class="mt-1 text-slate-600">{{ $package->weight_kg }} kg · {{ $package->length_cm }} × {{ $package->width_cm }} × {{ $package->height_cm }} cm</dd></div>
                                        <div><dt class="font-bold text-slate-700">Deskripsi</dt><dd class="mt-1 whitespace-pre-line break-words text-slate-600">{{ $package->description ?: 'Tidak ada keterangan tambahan.' }}</dd></div>
                                        @if (! $package->sender_name)
                                            <div><dt class="font-bold text-amber-700">Pelanggan lama</dt><dd class="mt-1 break-words text-amber-700">{{ $package->customer_name }}</dd></div>
                                        @endif
                                    </dl>
                                </details>
                            </td>
                            <td class="min-w-0 lg:px-4 lg:py-4 lg:align-top">
                                <p class="text-xs text-slate-500 lg:hidden">Kategori</p>
                                <p class="break-words font-semibold">{{ $package->category ?? 'Belum dikategorikan' }}</p>
                                <p class="mt-1 break-words text-xs text-slate-500">{{ $package->financial_category ?? 'Belum dikategorikan' }}</p>
                            </td>
                            <td class="min-w-0 lg:px-4 lg:py-4 lg:align-top">
                                <p class="text-xs text-slate-500 lg:hidden">Ongkos</p>
                                <p class="break-words font-extrabold text-brand-700">Rp{{ number_format($package->calculateTotalCost(), 0, ',', '.') }}</p>
                                <p class="mt-1 break-words text-xs text-slate-500">{{ $package->packageSetting?->name ?? 'Tarif tidak tersedia' }}</p>
                            </td>
                            <td class="col-span-2 border-t border-slate-100 pt-3 lg:border-0 lg:px-4 lg:py-4 lg:align-top">
                                <div class="flex flex-wrap items-center gap-3 text-xs font-bold">
                                    <a href="{{ route('packages.receipt', $package) }}" target="_blank" rel="noopener noreferrer" aria-label="Print resi {{ $package->code }} (PDF, tab baru)" class="rounded text-brand-700 underline-offset-2 hover:underline focus:outline-none focus:ring-2 focus:ring-brand-500">Print</a>
                                    <button type="button" wire:click="openEdit({{ $package->id }})" wire:loading.attr="disabled" wire:target="openEdit({{ $package->id }})" class="rounded text-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 disabled:cursor-wait disabled:opacity-60">
                                        <span wire:loading.remove wire:target="openEdit({{ $package->id }})">Edit</span><span wire:loading wire:target="openEdit({{ $package->id }})" role="status">Membuka...</span>
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $package->id }})" wire:loading.attr="disabled" wire:target="confirmDelete({{ $package->id }})" class="rounded text-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:cursor-wait disabled:opacity-60">
                                        <span wire:loading.remove wire:target="confirmDelete({{ $package->id }})">Hapus</span><span wire:loading wire:target="confirmDelete({{ $package->id }})" role="status">Membuka...</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="block lg:table-row"><td colspan="5" class="block px-4 py-10 text-center text-slate-500 lg:table-cell">Belum ada paket sesuai pencarian.</td></tr>
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
                                @if ($editingId && (!$senderName || !$recipientName))
                                    <p class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">Data pelanggan lama: <strong>{{ $legacyCustomerName }}</strong>. Lengkapi identitas pengirim dan penerima berdasarkan informasi yang benar.</p>
                                @endif
                                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                    <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                                        <h3 class="font-bold text-slate-900">Pengirim</h3>
                                        <div class="mt-4">
                                            <label for="package-sender-name" class="mb-1 block text-sm font-semibold">Nama Pengirim</label>
                                            <input id="package-sender-name" wire:model="senderName" maxlength="255" autocomplete="off" placeholder="Nama lengkap Pengirim"
                                                class="w-full rounded-lg border px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                                            @error('senderName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                                                                <div class="mt-4">
                                            <label for="package-sender-phone" class="mb-1 block text-sm font-semibold">No. HP Pengirim</label>
                                            <input id="package-sender-phone" wire:model="senderPhone" type="tel" inputmode="tel" maxlength="30" autocomplete="off" placeholder="0812... atau +62812..."
                                                class="w-full rounded-lg border px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                                            @error('senderPhone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
<div class="mt-4">
                                            <label for="package-sender-address" class="mb-1 block text-sm font-semibold">Alamat Asal</label>
                                            <textarea id="package-sender-address" wire:model="senderAddress" rows="4" maxlength="2000" placeholder="Jalan, nomor rumah, kelurahan/desa, kecamatan, kota/kabupaten, provinsi, dan kode pos"
                                                class="w-full rounded-lg border px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-100"></textarea>
                                            @error('senderAddress') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    </section>
                                    <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                                        <h3 class="font-bold text-slate-900">Penerima</h3>
                                        <div class="mt-4">
                                            <label for="package-recipient-name" class="mb-1 block text-sm font-semibold">Nama Penerima</label>
                                            <input id="package-recipient-name" wire:model="recipientName" maxlength="255" autocomplete="off" placeholder="Nama lengkap Penerima"
                                                class="w-full rounded-lg border px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                                            @error('recipientName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                                                                <div class="mt-4">
                                            <label for="package-recipient-phone" class="mb-1 block text-sm font-semibold">No. HP Penerima</label>
                                            <input id="package-recipient-phone" wire:model="recipientPhone" type="tel" inputmode="tel" maxlength="30" autocomplete="off" placeholder="0812... atau +62812..."
                                                class="w-full rounded-lg border px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                                            @error('recipientPhone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
<div class="mt-4">
                                            <label for="package-recipient-address" class="mb-1 block text-sm font-semibold">Alamat Tujuan</label>
                                            <textarea id="package-recipient-address" wire:model="recipientAddress" rows="4" maxlength="2000" placeholder="Jalan, nomor rumah, kelurahan/desa, kecamatan, kota/kabupaten, provinsi, dan kode pos"
                                                class="w-full rounded-lg border px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-100"></textarea>
                                            @error('recipientAddress') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    </section>
                                </div>
                                <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                                    <h3 class="font-bold text-slate-900">Informasi Paket</h3>
                                    <p class="mt-1 text-xs text-slate-500">Nomor paket dibuat otomatis saat paket baru disimpan.</p>
                                    <label for="package-description" class="mb-1 mt-4 block text-sm font-semibold">Deskripsi paket <span class="font-normal text-slate-400">(opsional)</span></label>
                                    <textarea id="package-description" wire:model="description" rows="2" placeholder="Isi paket atau keterangan penanganan" class="w-full rounded-lg border px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-100"></textarea>
                                    @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
