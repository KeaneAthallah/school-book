<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <title>Nota Pesanan {{ $order->number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; margin: 0; padding: 32px; font-size: 13px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #111827; padding-bottom: 12px; margin-bottom: 20px; }
        .company h1 { margin: 0 0 4px; font-size: 18px; }
        .company p { margin: 0; color: #4b5563; }
        .doc h2 { margin: 0 0 8px; font-size: 20px; letter-spacing: 1px; }
        .doc table { border-collapse: collapse; }
        .doc td { padding: 2px 0; }
        .doc td:first-child { color: #4b5563; padding-right: 12px; }
        .parties { display: flex; justify-content: space-between; margin-bottom: 20px; gap: 24px; }
        .parties h3 { margin: 0 0 4px; font-size: 12px; text-transform: uppercase; color: #6b7280; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.items th, table.items td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        table.items th { background: #f3f4f6; }
        table.items td.num, table.items th.num { text-align: right; }
        .print-button { margin-bottom: 16px; }
        @media print { .print-button { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">Cetak</button>

    <div class="header">
        <div class="company">
            @if (! empty($company['logo']))
                <img src="{{ $company['logo'] }}" alt="{{ $company['name'] }}" style="max-height: 52px; margin-bottom: 6px;" />
            @endif
            <h1>{{ $company['name'] }}</h1>
            <p>{{ $company['address'] }}</p>
            <p>{{ $company['phone'] }}</p>
            @if (! empty($company['email']))
                <p>{{ $company['email'] }}</p>
            @endif
        </div>
        <div class="doc">
            <h2>NOTA PESANAN (GUDANG)</h2>
            <table>
                <tr><td>No. Pesanan</td><td><strong>{{ $order->number }}</strong></td></tr>
                <tr><td>Tanggal</td><td>{{ $order->order_date?->format('d/m/Y') }}</td></tr>
                <tr><td>Status</td><td>{{ $order->status->label() }}</td></tr>
            </table>
        </div>
    </div>

    <div class="parties">
        <div>
            <h3>Sekolah</h3>
            <div><strong>{{ $order->school?->name ?? '-' }}</strong></div>
            <div>NPSN: {{ $order->school?->npsn ?? '-' }}</div>
            <div>{{ $order->school?->address ?? '-' }}</div>
        </div>
        <div>
            <h3>Penerbit</h3>
            <div>
                @php
                    $publishers = $order->items->map(fn ($item) => $item->product?->book?->publisher?->name)->filter()->unique()->values();
                @endphp
                @forelse ($publishers as $publisher)
                    <div>{{ $publisher }}</div>
                @empty
                    <div>-</div>
                @endforelse
            </div>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:40px">No</th>
                <th>Kode</th>
                <th>Barang</th>
                <th class="num">Jumlah</th>
                <th class="num">Stok Gudang</th>
                <th class="num">Selisih</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $index => $item)
                @php
                    $stock = $stockMap[$item->product_id ?? 0] ?? 0;
                    $diff = $stock - (int) $item->quantity;
                @endphp
                <tr>
                    <td class="num">{{ $index + 1 }}</td>
                    <td>{{ $item->product?->code ?? '-' }}</td>
                    <td>{{ $item->product?->title ?? '-' }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ $stock }}</td>
                    <td class="num">{{ $diff }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($order->notes)
        <div style="margin-top:12px"><strong>Catatan:</strong> {{ $order->notes }}</div>
    @endif

    <div style="margin-top: 40px; display: flex; justify-content: flex-end;">
        <div style="text-align: center; width: 220px;">
            <div>Palu, Tgl. ...............</div>
            <div style="margin-top: 8px;">Petugas Picking</div>
            <div style="margin-top: 60px;">( {{ $operatorName ?? 'Nama' }} )</div>
        </div>
    </div>
</body>
</html>