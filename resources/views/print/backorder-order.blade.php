<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <title>Surat Pesanan ke Penerbit {{ $order->number ?? $order->id }}</title>
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
            <h2>SURAT PESANAN KE PENERBIT</h2>
            <table>
                <tr><td>No. Pesanan Penerbit</td><td><strong>{{ $order->number ?? '-' }}</strong></td></tr>
                <tr><td>Tanggal Order</td><td>{{ $order->ordered_at?->format('d/m/Y') ?? '-' }}</td></tr>
                <tr><td>Status</td><td>{{ $order->status ?? '-' }}</td></tr>
            </table>
        </div>
    </div>

    <div class="parties">
        <div>
            <h3>Penerbit</h3>
            <div><strong>{{ $order->publisher?->name ?? '-' }}</strong></div>
            <div>{{ $order->publisher?->address ?? '' }}</div>
            <div>{{ $order->publisher?->phone ?? '' }}</div>
        </div>
        <div>
            <h3>Referensi</h3>
            <div>Jumlah item: {{ $order->items->count() }}</div>
            @if ($order->note)
                <div>Catatan: {{ $order->note }}</div>
            @endif
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:40px">No</th>
                <th>Kode</th>
                <th>Barang</th>
                <th>Penerbit</th>
                <th class="num">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $index => $item)
                <tr>
                    <td class="num">{{ $index + 1 }}</td>
                    <td>{{ $item->backorder?->product?->code ?? '-' }}</td>
                    <td>{{ $item->backorder?->product?->title ?? '-' }}</td>
                    <td>{{ $order->publisher?->name ?? '-' }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($order->note)
        <div style="margin-top:12px"><strong>Catatan:</strong> {{ $order->note }}</div>
    @endif

    <div style="margin-top: 40px; display: flex; justify-content: flex-end;">
        <div style="text-align: center; width: 220px;">
            <div>Palu, Tgl. ...............</div>
            <div style="margin-top: 8px;">Petugas Pengadaan</div>
            <div style="margin-top: 60px;">( Nama )</div>
        </div>
    </div>
</body>
</html>