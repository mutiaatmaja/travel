<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Resi {{ $package->code }}</title>
    <style>
        @page { margin: 6mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #000; line-height: 1.35; }
        h1 { font-size: 14px; margin: 0; }
        h2 { font-size: 10px; margin: 9px 0 3px; padding: 3px 0; border-top: 1px solid #000; page-break-after: avoid; }
        p { margin: 2px 0; word-wrap: break-word; }
        .header { border: 1px solid #000; padding: 7px; page-break-inside: avoid; }
        .muted { font-size: 9px; }
        .code { font-size: 17px; font-weight: bold; margin: 4px 0; }
        .address { white-space: pre-line; word-wrap: break-word; }
        .status { padding: 5px 0; border-bottom: 1px solid #000; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { padding: 3px 0; vertical-align: top; text-align: left; word-wrap: break-word; }
        th { width: 34%; font-weight: normal; }
        .amount { font-size: 15px; font-weight: bold; }
        .footer { margin-top: 9px; padding-top: 5px; border-top: 1px solid #000; font-size: 8px; }
    </style>
</head>
<body>
    @php
        $statuses = ['pending' => 'Pending', 'pickup' => 'Pickup', 'in_transit' => 'Dalam Perjalanan', 'delivered' => 'Terkirim', 'cancelled' => 'Dibatalkan'];
    @endphp
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <p class="muted">RESI PENGIRIMAN PAKET · ANTAR ALAMAT</p>
        <p class="code">{{ $package->code }}</p>
        <p class="muted">Tanggal dibuat: {{ $package->created_at?->format('d M Y, H:i') ?? 'Belum tersedia' }}</p>
    </div>
    <div class="status">Status Paket: <strong>{{ $statuses[$package->status] ?? $package->status }}</strong></div>
    <h2>PENGIRIM / ALAMAT ASAL</h2>
    <p><strong>{{ $package->sender_name ?: 'Belum dilengkapi' }}</strong></p>
    <p>No. HP: <strong>{{ $package->sender_phone ?: 'Belum dilengkapi' }}</strong></p>
    <p class="address">{{ $package->sender_address ?: 'Belum dilengkapi' }}</p>
    <h2>PENERIMA / ALAMAT TUJUAN</h2>
    <p><strong>{{ $package->recipient_name ?: 'Belum dilengkapi' }}</strong></p>
    <p>No. HP: <strong>{{ $package->recipient_phone ?: 'Belum dilengkapi' }}</strong></p>
    <p class="address">{{ $package->recipient_address ?: 'Belum dilengkapi' }}</p>
    @if (! $package->sender_name && $package->customer_name)
        <p class="muted">Data pelanggan lama: {{ $package->customer_name }}. Identitas pengirim/penerima perlu dilengkapi.</p>
    @endif
    <h2>INFORMASI PAKET</h2>
    <table>
        <tr><th>Kategori Paket</th><td>{{ $package->category ?? 'Belum dikategorikan' }}</td></tr>
        <tr><th>Kategori Keuangan</th><td>{{ $package->financial_category ?? 'Belum dikategorikan' }}</td></tr>
        <tr><th>Tarif</th><td>{{ $package->packageSetting?->name ?? 'Belum tersedia' }} — {{ $package->packageSetting?->pricing_type === 'volume' ? 'Volumetrik' : 'Berat' }}</td></tr>
        <tr><th>Berat / Dimensi</th><td>{{ $package->weight_kg ?? 0 }} kg / {{ $package->length_cm ?? 0 }} × {{ $package->width_cm ?? 0 }} × {{ $package->height_cm ?? 0 }} cm</td></tr>
        <tr><th>Ongkos Kirim</th><td class="amount">Rp{{ number_format($package->calculateTotalCost(), 0, ',', '.') }}</td></tr>
    </table>
    <h2>DESKRIPSI PAKET</h2>
    <p class="address">{{ $package->description ?: 'Tidak ada keterangan tambahan.' }}</p>
    <div class="footer">
        <p><strong>Bukti pengiriman, bukan bukti pelunasan. Dengan menyerahkan kiriman, Anda setuju Syarat dan Ketentuan</strong></p>
        <p>Nominal ongkos mengikuti pengaturan tarif saat resi dicetak. Kategori Cash/Kredit tidak menyatakan status pelunasan.</p>
        <p>Simpan nomor resi untuk pelacakan paket.</p>
    </div>
</body>
</html>
