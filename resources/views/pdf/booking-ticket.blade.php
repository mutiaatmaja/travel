<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Tiket {{ $booking->booking_code }}</title>
    <style>
        @page {
            margin: 28px;
        }

        body {
            color: #1e293b;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }

        .header {
            border-bottom: 2px solid #f97316;
            padding-bottom: 14px;
        }

        .brand {
            color: #ea580c;
            font-size: 18px;
            font-weight: bold;
        }

        .muted {
            color: #64748b;
        }

        .code {
            color: #0f172a;
            font-size: 15px;
            font-weight: bold;
        }

        .status {
            background: #eff6ff;
            border-radius: 12px;
            color: #1d4ed8;
            display: inline-block;
            font-weight: bold;
            padding: 5px 9px;
        }

        .route {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            margin: 16px 0;
            padding: 14px;
        }

        .route-city {
            color: #0f172a;
            font-size: 16px;
            font-weight: bold;
        }

        table {
            border-collapse: collapse;
            margin-top: 12px;
            width: 100%;
        }

        th,
        td {
            border-bottom: 1px solid #e2e8f0;
            padding: 9px 4px;
            text-align: left;
            vertical-align: top;
        }

        th {
            color: #64748b;
            font-size: 9px;
            text-transform: uppercase;
            width: 38%;
        }

        .amount {
            color: #c2410c;
            font-size: 16px;
            font-weight: bold;
        }

        .footer {
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 9px;
            margin-top: 20px;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="brand">TransGo</div>
        <div class="muted">Tiket perjalanan</div>
    </div>

    <table>
        <tr>
            <th>Nomor booking</th>
            <td class="code">{{ $booking->booking_code }}</td>
        </tr>
        <tr>
            <th>Status</th>
            <td><span class="status">Terkonfirmasi</span></td>
        </tr>
    </table>

    <div class="route">
        <div class="muted">Rute utama</div>
        <div class="route-city">{{ $booking->trip->travelRoute->originCity->name }} &rarr;
            {{ $booking->trip->travelRoute->destinationCity->name }}</div>
        <div style="margin-top: 8px;">
            {{ $booking->originStop->outlet->city->name }} ({{ $booking->originStop->outlet->city->code }})
            &rarr;
            {{ $booking->destinationStop->outlet->city->name }} ({{ $booking->destinationStop->outlet->city->code }})
        </div>
    </div>

    <table>
        <tr>
            <th>Nama penumpang</th>
            <td>{{ $booking->customer_name }}</td>
        </tr>
        <tr>
            <th>Nomor telepon</th>
            <td>{{ $booking->phone ?: '-' }}</td>
        </tr>
        <tr>
            <th>Keberangkatan</th>
            <td>{{ $booking->trip->departure_date->translatedFormat('l, d F Y') }} ·
                {{ substr($booking->trip->departure_time, 0, 5) }}</td>
        </tr>
        <tr>
            <th>Armada</th>
            <td>{{ $booking->trip->vehicle->code }} · {{ $booking->trip->vehicle->license_plate }}</td>
        </tr>
        <tr>
            <th>Jumlah penumpang</th>
            <td>{{ $booking->passenger_count }} orang</td>
        </tr>
        <tr>
            <th>Nomor kursi</th>
            <td>{{ $booking->seats->pluck('vehicleSeat.seat_number')->join(', ') ?: '-' }}</td>
        </tr>
        <tr>
            <th>Dikonfirmasi pada</th>
            <td>{{ $booking->confirmed_at?->translatedFormat('d F Y, H:i') ?: '-' }}</td>
        </tr>
        <tr>
            <th>Total</th>
            <td class="amount">Rp{{ number_format($booking->total_cost, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="footer">
        Tiket ini berlaku untuk booking yang telah dikonfirmasi. Tunjukkan tiket kepada petugas sebelum keberangkatan.
    </div>
</body>

</html>
