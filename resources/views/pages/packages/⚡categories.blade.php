<?php

use App\Models\Package;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin')] class extends Component
{
    public string $title = 'Kategori Paket';

    public string $section = 'Paket';
};
?>

<div>
    @include('pages.partials.crud-header', [
        'heading' => 'Kategori Paket',
        'description' => 'Kategori untuk membedakan data paket pada laporan.',
    ])
    <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2">
        @foreach (['Kategori Paket' => Package::CATEGORIES, 'Kategori Keuangan' => Package::FINANCIAL_CATEGORIES] as $heading => $categories)
            <section wire:key="{{ $heading }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-extrabold">{{ $heading }}</h3>
                <ul class="mt-4 divide-y divide-slate-100">
                    @foreach ($categories as $category)
                        <li wire:key="{{ $heading }}-{{ $category }}" class="py-3 text-sm font-semibold text-slate-700">{{ $category }}</li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>
    <p class="mt-5 text-sm text-slate-500">Pilih kategori saat menambah atau mengedit data di Semua Paket.</p>
</div>
