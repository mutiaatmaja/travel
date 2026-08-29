<?php

use App\Models\Package;
use App\Models\PackageSetting;
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
            'code' => ['required', 'max:100', Rule::unique('packages', 'code')->ignore($this->editingId)],
            'customerName' => ['required', 'max:255'],
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
                'code' => strtoupper($this->code),
                'customer_name' => $this->customerName,
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
        $this->description = '';
        $this->resetValidation();
    }

    public function render(): mixed
    {
        return view('pages.⚡packages', [
            'packages' => Package::with('packageSetting')
                ->when($this->search !== '', fn($query) => $query->where('code', 'like', '%' . $this->search . '%')->orWhere('customer_name', 'like', '%' . $this->search . '%'))
                ->latest()
                ->paginate(10),
            'packageSettings' => PackageSetting::where('is_active', true)->orderBy('name')->get(),
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
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">Belum ada paket.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-6 py-4">{{ $packages->links() }}</div>
    </div>

    @if ($modalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4">
            <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-xl font-extrabold">{{ $editingId ? 'Edit' : 'Tambah' }} Paket</h2>
                <form wire:submit="save" class="mt-5 space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <select wire:model="packageSettingId" class="rounded-xl border px-4 py-3 text-sm">
                            <option value="">Pilih pengaturan tarif</option>
                            @foreach ($packageSettings as $setting)
                                <option value="{{ $setting->id }}">{{ $setting->name }}</option>
                            @endforeach
                        </select>

                        <input wire:model="code" placeholder="Kode paket" class="rounded-xl border px-4 py-3 text-sm">
                        <input wire:model="customerName" placeholder="Nama pelanggan"
                            class="rounded-xl border px-4 py-3 text-sm sm:col-span-2">
                        <input wire:model="weightKg" type="number" step="0.01" min="0"
                            placeholder="Berat (kg)" class="rounded-xl border px-4 py-3 text-sm">
                        <input wire:model="lengthCm" type="number" step="0.01" min="0"
                            placeholder="Panjang (cm)" class="rounded-xl border px-4 py-3 text-sm">
                        <input wire:model="widthCm" type="number" step="0.01" min="0"
                            placeholder="Lebar (cm)" class="rounded-xl border px-4 py-3 text-sm">
                        <input wire:model="heightCm" type="number" step="0.01" min="0"
                            placeholder="Tinggi (cm)" class="rounded-xl border px-4 py-3 text-sm">
                        <select wire:model="status" class="rounded-xl border px-4 py-3 text-sm sm:col-span-2">
                            <option value="pending">Pending</option>
                            <option value="pickup">Pickup</option>
                            <option value="in_transit">Dalam perjalanan</option>
                            <option value="delivered">Terkirim</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                        <textarea wire:model="description" rows="3" placeholder="Deskripsi paket"
                            class="rounded-xl border px-4 py-3 text-sm sm:col-span-2"></textarea>
                    </div>

                    <div class="rounded-xl border border-brand-100 bg-brand-50 p-3 text-sm">
                        <p class="font-bold text-brand-700">Estimasi biaya</p>
                        @if ($packageSettingId)
                            @php($selectedSetting = App\Models\PackageSetting::find($packageSettingId))
                            @if ($selectedSetting)
                                <p class="mt-1 text-slate-700">
                                    @php(
    $estimated = new App\Models\Package([
        'package_setting_id' => $selectedSetting->id,
        'weight_kg' => $weightKg,
        'length_cm' => $lengthCm,
        'width_cm' => $widthCm,
        'height_cm' => $heightCm
    ])->calculateTotalCost()
)
                                    Rp{{ number_format($estimated, 0, ',', '.') }}
                                </p>
                            @endif
                        @else
                            <p class="mt-1 text-slate-500">Pilih pengaturan tarif untuk melihat estimasi biaya.</p>
                        @endif
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="$set('modalOpen', false)"
                            class="rounded-xl border px-5 py-3 text-sm font-bold">Batal</button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="rounded-xl bg-brand-500 px-5 py-3 text-sm font-bold text-white">
                            <span wire:loading.remove wire:target="save">Simpan</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
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
