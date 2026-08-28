<div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-xs font-bold uppercase tracking-widest text-brand-600">Master Data</p>
        <h2 class="mt-2 text-3xl font-extrabold text-slate-900">{{ $heading }}</h2>
        <p class="mt-2 text-sm text-slate-500">{{ $description }}</p>
    </div>
    <button type="button" wire:click="openCreate" wire:loading.attr="disabled" wire:target="openCreate"
        class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-brand-500/20 hover:bg-brand-600 disabled:opacity-60">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14" />
        </svg>
        <span wire:loading.remove wire:target="openCreate">Tambah {{ $heading }}</span><span wire:loading
            wire:target="openCreate">Membuka...</span>
    </button>
</div>
@if (session('toast'))
    <div
        class="mt-6 flex items-center gap-3 rounded-xl border px-4 py-3 text-sm font-semibold {{ session('toast.type') === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-green-200 bg-green-50 text-green-700' }}">
        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m5 12 4 4L19 6" />
        </svg>{{ session('toast.message') }}
    </div>
@endif
