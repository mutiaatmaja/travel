<?php

use App\Models\Package;
use App\Models\PackageTrackingEvent;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin')] class extends Component {
    public string $title = 'Tracing Paket';

    public string $section = 'Paket';

    public string $search = '';

    public ?int $selectedPackageId = null;

    public string $status = 'pending';

    public string $location = '';

    public string $description = '';

    public string $occurredAt = '';

    public function mount(): void
    {
        $this->occurredAt = now()->format('Y-m-d\TH:i');
    }

    public function searchPackages(): void
    {
        $this->resetValidation();
        $this->selectedPackageId = null;
    }

    public function selectPackage(int $id): void
    {
        $package = Package::findOrFail($id);
        $this->selectedPackageId = $package->id;
        $this->status = $package->status;
        $this->occurredAt = now()->format('Y-m-d\TH:i');
        $this->resetValidation();
    }

    public function addEvent(): void
    {
        $this->validate([
            'selectedPackageId' => ['required', 'exists:packages,id'],
            'status' => ['required', Rule::in(['pending', 'pickup', 'in_transit', 'delivered', 'cancelled'])],
            'location' => ['required', 'max:255'],
            'description' => ['nullable', 'string'],
            'occurredAt' => ['required', 'date'],
        ]);

        $package = Package::findOrFail($this->selectedPackageId);

        PackageTrackingEvent::create([
            'package_id' => $package->id,
            'status' => $this->status,
            'location' => $this->location,
            'description' => $this->description,
            'occurred_at' => $this->occurredAt,
        ]);

        $package->update(['status' => $this->status]);
        $this->location = '';
        $this->description = '';
        $this->occurredAt = now()->format('Y-m-d\TH:i');
        session()->flash('toast', ['type' => 'success', 'message' => 'Riwayat tracing berhasil ditambahkan.']);
    }

    public function deleteEvent(int $id): void
    {
        $event = PackageTrackingEvent::where('package_id', $this->selectedPackageId)->findOrFail($id);
        $event->delete();
        session()->flash('toast', ['type' => 'success', 'message' => 'Riwayat tracing berhasil dihapus.']);
    }

    public function logout(): void
    {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        $this->redirectRoute('login', navigate: true);
    }

    public function render(): mixed
    {
        $packages = Package::query()
            ->with('packageSetting')
            ->when($this->search !== '', fn($query) => $query->where('code', 'like', '%' . $this->search . '%')->orWhere('customer_name', 'like', '%' . $this->search . '%'))
            ->latest()
            ->limit(20)
            ->get();

        return view('pages.⚡package-tracing', [
            'packages' => $packages,
            'selectedPackage' => $this->selectedPackageId ? Package::with(['packageSetting', 'trackingEvents'])->find($this->selectedPackageId) : null,
        ]);
    }
};
?>

<div>
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-brand-600">Paket</p>
            <h2 class="mt-2 text-3xl font-extrabold text-slate-900">Tracing Paket</h2>
            <p class="mt-2 text-sm text-slate-500">Lacak dan catat setiap perjalanan paket berdasarkan nomor paket.</p>
        </div>
    </div>
    @if (session('toast'))
        <div
            class="mt-6 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m5 12 4 4L19 6" />
            </svg>{{ session('toast.message') }}
        </div>
    @endif

    <div class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-[minmax(280px,0.8fr)_minmax(0,1.5fr)]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5">
                <h3 class="font-extrabold">Cari Nomor Paket</h3>
                <form wire:submit="searchPackages" class="mt-4 flex gap-2">
                    <input wire:model="search" placeholder="Contoh: PKG-20260901"
                        class="min-w-0 flex-1 rounded-xl border px-4 py-2.5 text-sm">
                    <button type="submit" wire:loading.attr="disabled"
                        class="rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-bold text-white">
                        <span wire:loading.remove wire:target="searchPackages">Cari</span>
                        <span wire:loading wire:target="searchPackages">Mencari...</span>
                    </button>
                </form>
            </div>
            <div class="relative max-h-[560px] overflow-y-auto">
                <div wire:loading wire:target="searchPackages"
                    class="absolute inset-0 z-10 flex items-center justify-center bg-white/70">
                    <span class="rounded-xl bg-white px-4 py-3 text-sm font-semibold shadow-lg">Mencari paket...</span>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($packages as $package)
                        <button type="button" wire:click="selectPackage({{ $package->id }})"
                            class="w-full p-4 text-left transition hover:bg-brand-50 {{ $selectedPackage?->id === $package->id ? 'bg-brand-50' : '' }}">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-bold text-slate-900">{{ $package->code }}</span>
                                <span
                                    class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-600">{{ ucfirst(str_replace('_', ' ', $package->status)) }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">{{ $package->customer_name }}</p>
                        </button>
                    @empty
                        <p class="p-6 text-center text-sm text-slate-500">Paket tidak ditemukan.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="space-y-6">
            @if ($selectedPackage)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-brand-600">Detail Paket</p>
                            <h3 class="mt-1 text-xl font-extrabold text-slate-900">{{ $selectedPackage->code }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $selectedPackage->customer_name }} ·
                                {{ $selectedPackage->weight_kg }} kg</p>
                        </div>
                        <p class="text-lg font-extrabold text-brand-700">
                            Rp{{ number_format($selectedPackage->calculateTotalCost(), 0, ',', '.') }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-extrabold">Tambah Riwayat</h3>
                    <form wire:submit="addEvent" class="mt-4 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Status baru</label>
                                <select wire:model="status" class="w-full rounded-xl border px-4 py-3 text-sm">
                                    <option value="pending">Pending</option>
                                    <option value="pickup">Pickup</option>
                                    <option value="in_transit">Dalam perjalanan</option>
                                    <option value="delivered">Terkirim</option>
                                    <option value="cancelled">Dibatalkan</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Waktu kejadian</label>
                                <input wire:model="occurredAt" type="datetime-local"
                                    class="w-full rounded-xl border px-4 py-3 text-sm">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Lokasi</label>
                                <input wire:model="location" placeholder="Contoh: Outlet Pontianak Center"
                                    class="w-full rounded-xl border px-4 py-3 text-sm">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Keterangan</label>
                                <textarea wire:model="description" rows="2" placeholder="Keterangan perjalanan paket"
                                    class="w-full rounded-xl border px-4 py-3 text-sm"></textarea>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" wire:loading.attr="disabled"
                                class="rounded-xl bg-brand-500 px-5 py-3 text-sm font-bold text-white">
                                <span wire:loading.remove wire:target="addEvent">Simpan Riwayat</span>
                                <span wire:loading wire:target="addEvent">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-extrabold">Timeline Perjalanan</h3>
                    <div class="mt-5 space-y-5">
                        @forelse ($selectedPackage->trackingEvents as $event)
                            <div class="relative border-l-2 border-brand-200 pl-5">
                                <span
                                    class="absolute -left-[7px] top-1 h-3 w-3 rounded-full bg-brand-500 ring-4 ring-white"></span>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-bold text-slate-900">
                                            {{ ucfirst(str_replace('_', ' ', $event->status)) }}</p>
                                        <p class="text-sm font-medium text-brand-700">{{ $event->location }}</p>
                                        <p class="mt-1 text-xs text-slate-400">
                                            {{ $event->occurred_at->format('d M Y, H:i') }}</p>
                                        @if ($event->description)
                                            <p class="mt-2 text-sm text-slate-600">{{ $event->description }}</p>
                                        @endif
                                    </div>
                                    <button type="button" wire:click="deleteEvent({{ $event->id }})"
                                        class="text-xs font-bold text-red-600">Hapus</button>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Belum ada riwayat tracing.</p>
                        @endforelse
                    </div>
                </div>
            @else
                <div
                    class="flex min-h-[420px] items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                    <div>
                        <h3 class="font-extrabold text-slate-900">Pilih paket untuk mulai tracing</h3>
                        <p class="mt-2 text-sm text-slate-500">Cari nomor paket di panel sebelah kiri untuk melihat
                            timeline perjalanan.</p>
                    </div>
                </div>
            @endif
        </section>
    </div>
</div>
