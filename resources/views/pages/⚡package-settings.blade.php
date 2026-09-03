<?php

use App\Models\PackageSetting;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] class extends Component {
    use WithPagination;

    public string $title = 'Pengaturan Paket';

    public string $section = 'Paket';

    public string $search = '';

    public bool $modalOpen = false;

    public bool $confirmDeleteOpen = false;

    public ?int $editingId = null;

    public ?int $deleteId = null;

    public string $name = '';

    public string $pricingType = 'weight';

    public int $ratePerKg = 0;

    public int $ratePerM3 = 0;

    public int $volumetricDivisor = 6000;

    public int $minimumCharge = 0;

    public string $description = '';

    public bool $isActive = true;

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
        $setting = PackageSetting::findOrFail($id);

        $this->editingId = $id;
        $this->name = $setting->name;
        $this->pricingType = $setting->pricing_type;
        $this->ratePerKg = (int) ($setting->rate_per_kg ?? ($setting->rate_per_m3 ?? 0));
        $this->ratePerM3 = (int) ($setting->rate_per_m3 ?? 0);
        $this->volumetricDivisor = (int) ($setting->volumetric_divisor ?? 6000);
        $this->minimumCharge = (int) $setting->minimum_charge;
        $this->description = $setting->description ?? '';
        $this->isActive = (bool) $setting->is_active;

        $this->resetValidation();
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'max:255', Rule::unique('package_settings', 'name')->ignore($this->editingId)],
            'pricingType' => ['required', 'in:weight,volume'],
            'ratePerKg' => ['nullable', 'integer', 'min:0'],
            'ratePerM3' => ['nullable', 'integer', 'min:0'],
            'volumetricDivisor' => ['required', 'integer', 'min:1'],
            'minimumCharge' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $this->validate([
            'ratePerKg' => ['required', 'integer', 'min:0'],
        ]);

        PackageSetting::updateOrCreate(
            [
                'id' => $this->editingId,
            ],
            [
                'name' => $this->name,
                'pricing_type' => $this->pricingType,
                'rate_per_kg' => $this->ratePerKg,
                'rate_per_m3' => null,
                'volumetric_divisor' => $this->volumetricDivisor,
                'minimum_charge' => $this->minimumCharge,
                'description' => $this->description,
                'is_active' => $this->isActive,
            ],
        );

        $message = $this->editingId ? 'Pengaturan paket berhasil diperbarui.' : 'Pengaturan paket berhasil ditambahkan.';
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
        PackageSetting::findOrFail($this->deleteId)->delete();
        $this->confirmDeleteOpen = false;
        $this->deleteId = null;
        session()->flash('toast', ['type' => 'success', 'message' => 'Pengaturan paket berhasil dihapus.']);
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
        $this->reset(['modalOpen', 'editingId', 'deleteId', 'name', 'pricingType', 'ratePerKg', 'ratePerM3', 'volumetricDivisor', 'minimumCharge', 'description', 'isActive']);
        $this->pricingType = 'weight';
        $this->ratePerKg = 0;
        $this->ratePerM3 = 0;
        $this->volumetricDivisor = 6000;
        $this->minimumCharge = 0;
        $this->description = '';
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render(): mixed
    {
        return view('pages.⚡package-settings', [
            'settings' => PackageSetting::query()
                ->when($this->search !== '', fn($query) => $query->where('name', 'like', '%' . $this->search . '%'))
                ->latest()
                ->paginate(10),
        ]);
    }
};
?>

<div>
    @include('pages.partials.crud-header', [
        'heading' => 'Pengaturan Paket',
        'description' => 'Kelola tarif paket berdasarkan berat dan volume.',
    ])

    <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="font-extrabold">Daftar Pengaturan</h3>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <input wire:model.live.debounce.300ms="search" placeholder="Cari nama tarif..."
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

            <table class="w-full min-w-[820px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Nama</th>
                        <th class="px-6 py-4">Tipe</th>
                        <th class="px-6 py-4">Tarif</th>
                        <th class="px-6 py-4">Faktor pembagi</th>
                        <th class="px-6 py-4">Minimum</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($settings as $setting)
                        <tr>
                            <td class="px-6 py-4 font-bold">{{ $setting->name }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-700">
                                    {{ $setting->pricing_type === 'weight' ? 'Berat' : 'Volume' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if ($setting->pricing_type === 'weight')
                                    Rp{{ number_format((int) ($setting->rate_per_kg ?? 0), 0, ',', '.') }} / kg
                                @else
                                    Rp{{ number_format((int) ($setting->rate_per_kg ?? ($setting->rate_per_m3 ?? 0)), 0, ',', '.') }}
                                    / kg volumetrik
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                {{ number_format((int) ($setting->volumetric_divisor ?? 6000), 0, ',', '.') }}</td>
                            <td class="px-6 py-4">Rp{{ number_format((int) $setting->minimum_charge, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="rounded-full {{ $setting->is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-600' }} px-2.5 py-1 text-xs font-bold">
                                    {{ $setting->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="openEdit({{ $setting->id }})"
                                    class="px-2 text-xs font-bold text-brand-600">Edit</button>
                                <button wire:click="confirmDelete({{ $setting->id }})"
                                    class="px-2 text-xs font-bold text-red-600">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">Belum ada pengaturan paket.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-6 py-4">{{ $settings->links() }}</div>
    </div>

    @if ($modalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4">
            <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-xl font-extrabold">{{ $editingId ? 'Edit' : 'Tambah' }} Pengaturan Paket</h2>
                <form wire:submit="save" class="mt-5 space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Nama tarif</label>
                            <input wire:model="name" placeholder="Contoh: Tarif Reguler"
                                class="w-full rounded-xl border px-4 py-3 text-sm">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Tipe tarif</label>
                            <select wire:model="pricingType" class="w-full rounded-xl border px-4 py-3 text-sm">
                                <option value="weight">Berat</option>
                                <option value="volume">Volume</option>
                            </select>
                        </div>

                        <div class="flex items-end">
                            <label
                                class="flex w-full cursor-pointer items-center gap-2 rounded-xl border px-3 py-3 text-sm text-slate-700">
                                <input wire:model="isActive" type="checkbox" class="h-4 w-4">
                                <span class="font-medium">Aktif</span>
                            </label>
                        </div>

                        @if ($pricingType === 'weight')
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Tarif per kg</label>
                                <input wire:model="ratePerKg" type="number" min="0" step="1000"
                                    placeholder="12000" class="w-full rounded-xl border px-4 py-3 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Minimum charge</label>
                                <input wire:model="minimumCharge" type="number" min="0" step="1000"
                                    placeholder="30000" class="w-full rounded-xl border px-4 py-3 text-sm">
                            </div>
                        @else
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Tarif per kg
                                    volumetrik</label>
                                <input wire:model="ratePerKg" type="number" min="0" step="1000"
                                    placeholder="30000" class="w-full rounded-xl border px-4 py-3 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Faktor pembagi</label>
                                <input wire:model="volumetricDivisor" type="number" min="1" step="1"
                                    placeholder="6000" class="w-full rounded-xl border px-4 py-3 text-sm">
                                <p class="mt-1 text-xs text-slate-500">Panjang × lebar × tinggi ÷ faktor pembagi</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Minimum charge</label>
                                <input wire:model="minimumCharge" type="number" min="0" step="1000"
                                    placeholder="80000" class="w-full rounded-xl border px-4 py-3 text-sm">
                            </div>
                        @endif

                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Keterangan</label>
                            <textarea wire:model="description" rows="3" placeholder="Deskripsi tarif paket"
                                class="w-full rounded-xl border px-4 py-3 text-sm"></textarea>
                        </div>
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
                <h2 class="font-extrabold">Hapus pengaturan paket?</h2>
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
