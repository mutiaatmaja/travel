@if ($modalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4" role="dialog" aria-modal="true">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl sm:p-8">
            <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-wide text-brand-600">{{ $editingId ? 'Edit' : 'Tambah' }}</p><h2 class="mt-1 text-xl font-extrabold text-slate-900">{{ $entity }}</h2></div><button type="button" wire:click="$set('modalOpen', false)" class="rounded-lg p-2 text-slate-400 hover:bg-slate-50" aria-label="Tutup modal">&times;</button></div>
            <form wire:submit="save" class="mt-6 space-y-4">
                @foreach ($fields as $field => $label)
                    <div><label for="{{ $field }}" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ $label }}</label><input id="{{ $field }}" type="text" wire:model="{{ $field }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">@error($field)<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror</div>
                @endforeach
                @if (property_exists($this, 'isActive'))
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-600"><input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-brand-500 focus:ring-brand-500"> Aktif</label>
                @endif
                <div class="flex flex-col-reverse gap-3 pt-3 sm:flex-row sm:justify-end"><button type="button" wire:click="$set('modalOpen', false)" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600">Batal</button><button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-brand-500 px-5 py-3 text-sm font-bold text-white disabled:opacity-60"><span wire:loading.remove wire:target="save">Simpan</span><span wire:loading wire:target="save">Menyimpan...</span></button></div>
            </form>
        </div>
    </div>
@endif
@if ($confirmDeleteOpen)
    <div class="fixed inset-0 z-60 flex items-center justify-center bg-slate-900/50 p-4"><div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl"><h2 class="text-lg font-extrabold text-slate-900">Hapus {{ $entity }}?</h2><p class="mt-2 text-sm text-slate-500">Data yang dihapus tidak dapat dikembalikan.</p><div class="mt-6 flex justify-end gap-3"><button type="button" wire:click="$set('confirmDeleteOpen', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600">Batal</button><button type="button" wire:click="delete" wire:loading.attr="disabled" class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-60"><span wire:loading.remove wire:target="delete">Ya, Hapus</span><span wire:loading wire:target="delete">Menghapus...</span></button></div></div></div>
@endif
