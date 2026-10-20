<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <title>Surat Jalan {{ $shipment->delivery_note_number ?? $shipment->number }}</title>
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
        .note { border: 1px solid #d1d5db; padding: 8px; margin-bottom: 20px; color: #374151; }
        .signatures { display: flex; justify-content: space-between; margin-top: 56px; text-align: center; }
        .signatures div { width: 30%; }
        .signatures .line { margin-top: 56px; border-top: 1px solid #9ca3af; padding-top: 4px; color: #4b5563; }
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
            <h2>SURAT JALAN</h2>
            <table>
                <tr><td>No. Surat Jalan</td><td><strong>{{ $shipment->delivery_note_number ?? $shipment->number }}</strong></td></tr>
                <tr><td>No. Pengiriman</td><td>{{ $shipment->number }}</td></tr>
                <tr><td>Tanggal</td><td>{{ $shipment->ship_date?->format('d/m/Y') ?? '-' }}</td></tr>
                <tr><td>Status</td><td>{{ $shipment->status->label() }}</td></tr>
            </table>
        </div>
    </div>

    <div class="parties">
        <div>
            <h3>Penerima</h3>
            <div><strong>{{ $shipment->order?->school?->name ?? '-' }}</strong></div>
            <div>NPSN: {{ $shipment->order?->school?->npsn ?? '-' }}</div>
            <div>{{ $shipment->address ?? $shipment->order?->school?->address ?? '-' }}</div>
            <div>Up: {{ $shipment->recipient_name ?? '-' }}</div>
        </div>
        <div>
            <h3>Pengiriman</h3>
            <div>Kurir: {{ $shipment->courier?->name ?? '-' }}</div>
            <div>Metode: {{ $shipment->shippingMethod?->name ?? '-' }}</div>
            <div>No. Resi: {{ $shipment->tracking_number ?? '-' }}</div>
            <div>Pesanan: {{ $shipment->order?->number ?? '-' }}</div>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:40px">No</th>
                <th>Kode</th>
                <th>Judul Barang</th>
                <th class="num">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($shipment->items as $index => $item)
                <tr>
                    <td class="num">{{ $index + 1 }}</td>
                    <td>{{ $item->product?->code ?? '-' }}</td>
                    <td>{{ $item->product?->title ?? '-' }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="num">Total</th>
                <th class="num">{{ $shipment->items->sum('quantity') }}</th>
            </tr>
        </tfoot>
    </table>

    @if ($shipment->notes)
        <div class="note"><strong>Catatan:</strong> {{ $shipment->notes }}</div>
    @endif

    <div class="signatures">
        <div>
            <div>Disiapkan oleh</div>
            <div class="line">{{ $shipment->createdBy?->name ?? 'Gudang' }}</div>
        </div>
        <div>
            <div>Dibawa oleh</div>
            <div class="line">{{ $shipment->courier?->name ?? 'Kurir' }}</div>
        </div>
        <div>
            <div>Diterima oleh</div>
            <div class="line">{{ $shipment->recipient_name ?? 'Penerima' }}</div>
        </div>
    </div>
</body>
</html>
