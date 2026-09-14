<?php

use App\Models\BookingSetting;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] class extends Component {
    use WithPagination;

    public string $title = 'Pengaturan Booking';

    public string $section = 'Booking';

    public string $search = '';

    public bool $modalOpen = false;

    public bool $confirmDeleteOpen = false;

    public ?int $editingId = null;

    public ?int $deleteId = null;

    public string $name = '';

    public string $bookingPrefix = 'BKG';

    public string $defaultStatus = 'pending';

    public int $maxPassengers = 8;

    public int $paymentDeadlineMinutes = 30;

    public bool $seatSelectionEnabled = true;

    public bool $cancellationAllowed = true;

    public int $cancellationDeadlineMinutes = 60;

    public int $refundPercentage = 100;

    public float $baggageLimitKg = 15;

    public bool $notificationEnabled = true;

    public bool $isActive = true;

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
        $setting = BookingSetting::findOrFail($id);

        $this->editingId = $setting->id;
        $this->name = $setting->name;
        $this->bookingPrefix = $setting->booking_prefix;
        $this->defaultStatus = $setting->default_status;
        $this->maxPassengers = $setting->max_passengers;
        $this->paymentDeadlineMinutes = $setting->payment_deadline_minutes;
        $this->seatSelectionEnabled = $setting->seat_selection_enabled;
        $this->cancellationAllowed = $setting->cancellation_allowed;
        $this->cancellationDeadlineMinutes = $setting->cancellation_deadline_minutes;
        $this->refundPercentage = $setting->refund_percentage;
        $this->baggageLimitKg = (float) $setting->baggage_limit_kg;
        $this->notificationEnabled = $setting->notification_enabled;
        $this->isActive = $setting->is_active;
        $this->description = $setting->description ?? '';
        $this->resetValidation();
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'max:255', Rule::unique('booking_settings', 'name')->ignore($this->editingId)],
            'bookingPrefix' => ['required', 'alpha_dash', 'max:10'],
            'defaultStatus' => ['required', Rule::in(['pending', 'confirmed'])],
            'maxPassengers' => ['required', 'integer', 'min:1', 'max:99'],
            'paymentDeadlineMinutes' => ['required', 'integer', 'min:0'],
            'cancellationDeadlineMinutes' => ['required', 'integer', 'min:0'],
            'refundPercentage' => ['required', 'integer', 'min:0', 'max:100'],
            'baggageLimitKg' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        BookingSetting::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $this->name,
                'booking_prefix' => strtoupper($this->bookingPrefix),
                'default_status' => $this->defaultStatus,
                'max_passengers' => $this->maxPassengers,
                'payment_deadline_minutes' => $this->paymentDeadlineMinutes,
                'seat_selection_enabled' => $this->seatSelectionEnabled,
                'cancellation_allowed' => $this->cancellationAllowed,
                'cancellation_deadline_minutes' => $this->cancellationDeadlineMinutes,
                'refund_percentage' => $this->refundPercentage,
                'baggage_limit_kg' => $this->baggageLimitKg,
                'notification_enabled' => $this->notificationEnabled,
                'is_active' => $this->isActive,
                'description' => $this->description,
            ],
        );

        $message = $this->editingId ? 'Pengaturan booking berhasil diperbarui.' : 'Pengaturan booking berhasil ditambahkan.';
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
        BookingSetting::findOrFail($this->deleteId)->delete();
        $this->confirmDeleteOpen = false;
        $this->deleteId = null;
        session()->flash('toast', ['type' => 'success', 'message' => 'Pengaturan booking berhasil dihapus.']);
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
        $this->reset(['modalOpen', 'editingId', 'deleteId', 'name', 'bookingPrefix', 'defaultStatus', 'maxPassengers', 'paymentDeadlineMinutes', 'seatSelectionEnabled', 'cancellationAllowed', 'cancellationDeadlineMinutes', 'refundPercentage', 'baggageLimitKg', 'notificationEnabled', 'isActive', 'description']);
        $this->bookingPrefix = 'BKG';
        $this->defaultStatus = 'pending';
        $this->maxPassengers = 8;
        $this->paymentDeadlineMinutes = 30;
        $this->seatSelectionEnabled = true;
        $this->cancellationAllowed = true;
        $this->cancellationDeadlineMinutes = 60;
        $this->refundPercentage = 100;
        $this->baggageLimitKg = 15;
        $this->notificationEnabled = true;
        $this->isActive = true;
        $this->description = '';
        $this->resetValidation();
    }

    public function render(): mixed
    {
        return view('pages.booking.⚡settings', [
            'settings' => BookingSetting::query()
                ->when($this->search !== '', fn($query) => $query->where('name', 'like', '%' . $this->search . '%'))
                ->latest()
                ->paginate(10),
        ]);
    }
};
?>

<div>
    @include('pages.partials.crud-header', [
        'heading' => 'Pengaturan Booking',
        'description' => 'Siapkan aturan default booking. Kapasitas kursi tetap mengikuti armada yang dipilih.',
    ])

    <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="font-extrabold">Daftar Pengaturan</h3>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <input wire:model.live.debounce.300ms="search" placeholder="Cari nama pengaturan..."
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
            <table class="w-full min-w-250 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Nama</th>
                        <th class="px-6 py-4">Prefix</th>
                        <th class="px-6 py-4">Maks./booking</th>
                        <th class="px-6 py-4">Pembayaran</th>
                        <th class="px-6 py-4">Kursi</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($settings as $setting)
                        <tr>
                            <td class="px-6 py-4">
                                <p class="font-bold">{{ $setting->name }}</p>
                                <p class="text-xs text-slate-400">{{ $setting->description ?: 'Tanpa keterangan' }}</p>
                            </td>
                            <td class="px-6 py-4 font-semibold">{{ $setting->booking_prefix }}</td>
                            <td class="px-6 py-4">{{ $setting->max_passengers }} orang</td>
                            <td class="px-6 py-4">{{ $setting->payment_deadline_minutes }} menit</td>
                            <td class="px-6 py-4">{{ $setting->seat_selection_enabled ? 'Aktif' : 'Nonaktif' }}</td>
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
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">Belum ada pengaturan
                                booking.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-6 py-4">{{ $settings->links() }}</div>
    </div>

    @if ($modalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4">
            <div x-data="{ activeTab: 'general' }" class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-xl font-extrabold">{{ $editingId ? 'Edit' : 'Tambah' }} Pengaturan Booking</h2>
                <div class="mt-5 grid grid-cols-2 gap-2 rounded-xl bg-slate-50 p-1 sm:grid-cols-4">
                    @foreach ([['key' => 'general', 'label' => 'Umum'], ['key' => 'payment', 'label' => 'Pembayaran'], ['key' => 'cancellation', 'label' => 'Pembatalan'], ['key' => 'passenger', 'label' => 'Penumpang']] as $tab)
                        <button type="button" @click="activeTab = '{{ $tab['key'] }}'"
                            :class="activeTab === '{{ $tab['key'] }}' ? 'bg-white text-brand-700 shadow-sm' :
                                'text-slate-500 hover:text-slate-700'"
                            class="rounded-lg px-2 py-2 text-xs font-bold transition sm:text-sm">
                            {{ $tab['label'] }}
                        </button>
                    @endforeach
                </div>

                <form wire:submit="save" class="mt-5 space-y-5">
                    <div x-show="activeTab === 'general'" x-cloak class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Nama pengaturan</label>
                            <input wire:model="name" placeholder="Contoh: Booking Reguler"
                                class="w-full rounded-xl border px-4 py-3 text-sm">
                            @error('name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Prefix nomor
                                    booking</label>
                                <input wire:model="bookingPrefix" placeholder="BKG"
                                    class="w-full rounded-xl border px-4 py-3 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Status awal</label>
                                <select wire:model="defaultStatus" class="w-full rounded-xl border px-4 py-3 text-sm">
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Dikonfirmasi</option>
                                </select>
                            </div>
                        </div>
                        <label
                            class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-3 text-sm text-slate-700">
                            <input wire:model="isActive" type="checkbox" class="h-4 w-4">
                            <span class="font-medium">Pengaturan aktif</span>
                        </label>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Keterangan</label>
                            <textarea wire:model="description" rows="3" placeholder="Jelaskan penggunaan pengaturan ini"
                                class="w-full rounded-xl border px-4 py-3 text-sm"></textarea>
                        </div>
                    </div>

                    <div x-show="activeTab === 'payment'" x-cloak class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Batas pembayaran
                                (menit)</label>
                            <input wire:model="paymentDeadlineMinutes" type="number" min="0"
                                class="w-full rounded-xl border px-4 py-3 text-sm">
                            <p class="mt-1 text-xs text-slate-500">Durasi pembayaran sebelum booking dianggap
                                kedaluwarsa.</p>
                        </div>
                        <label
                            class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-3 text-sm text-slate-700">
                            <input wire:model="notificationEnabled" type="checkbox" class="h-4 w-4">
                            <span class="font-medium">Aktifkan notifikasi booking dan pembayaran</span>
                        </label>
                    </div>

                    <div x-show="activeTab === 'cancellation'" x-cloak class="space-y-4">
                        <label
                            class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-3 text-sm text-slate-700">
                            <input wire:model="cancellationAllowed" type="checkbox" class="h-4 w-4">
                            <span class="font-medium">Izinkan pembatalan booking</span>
                        </label>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Batas pembatalan
                                    (menit)</label>
                                <input wire:model="cancellationDeadlineMinutes" type="number" min="0"
                                    class="w-full rounded-xl border px-4 py-3 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Refund (%)</label>
                                <input wire:model="refundPercentage" type="number" min="0" max="100"
                                    class="w-full rounded-xl border px-4 py-3 text-sm">
                            </div>
                        </div>
                    </div>

                    <div x-show="activeTab === 'passenger'" x-cloak class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Maksimal penumpang per
                                booking</label>
                            <input wire:model="maxPassengers" type="number" min="1" max="99"
                                class="w-full rounded-xl border px-4 py-3 text-sm">
                            <p class="mt-1 text-xs text-slate-500">Batas jumlah orang dalam satu transaksi, bukan
                                kapasitas armada.</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Batas bagasi (kg)</label>
                            <input wire:model="baggageLimitKg" type="number" min="0" step="0.01"
                                class="w-full rounded-xl border px-4 py-3 text-sm">
                        </div>
                        <label
                            class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-3 text-sm text-slate-700">
                            <input wire:model="seatSelectionEnabled" type="checkbox" class="h-4 w-4">
                            <span class="font-medium">Izinkan penumpang memilih kursi</span>
                        </label>
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
                <h2 class="font-extrabold">Hapus pengaturan booking?</h2>
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
