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
        .totals { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .totals td { padding: 4px 8px; }
        .totals td.label { text-align: right; color: #374151; }
        .totals td.value { text-align: right; font-weight: 600; }
        .status { display: inline-block; padding: 4px 8px; border: 2px solid #111827; font-weight: bold; }
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
            <h2>NOTA PESANAN</h2>
            <table>
                <tr><td>No. Pesanan</td><td><strong>{{ $order->number }}</strong></td></tr>
                <tr><td>Tanggal</td><td>{{ $order->order_date?->format('d/m/Y') }}</td></tr>
                <tr><td>Status</td><td>{{ $order->status->label() }}</td></tr>
                <tr><td>Status Bayar</td><td><span class="status">{{ $order->isPaid() ? 'LUNAS' : 'BELUM LUNAS' }}</span></td></tr>
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
            <h3>Pesanan</h3>
            <div>Sumber: {{ $order->orderSource?->name ?? '-' }}</div>
            <div>Operator: {{ $order->operator?->name ?? '-' }}</div>
            @if ($order->discount_percent)
                <div>Diskon: {{ $order->discount_percent }}%</div>
            @endif
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:40px">No</th>
                <th>Kode</th>
                <th>Judul</th>
                <th>Jenis</th>
                <th class="num">Jumlah</th>
                <th class="num">Harga</th>
                <th class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $index => $item)
                <tr>
                    <td class="num">{{ $index + 1 }}</td>
                    <td>{{ $item->product?->code ?? '-' }}</td>
                    <td>{{ $item->product?->title ?? '-' }}</td>
                    <td>{{ ($item->product?->type === 'book') ? 'Buku' : 'Barang Lainnya/Jasa' }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>
                    <td class="num">{{ number_format((float) $item->subtotal, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php
        use App\Support\Format;
    @endphp
    <table class="totals">
        <tr><td class="label">Subtotal</td><td class="value">{{ Format::idr($order->subtotal) }}</td></tr>
        <tr><td class="label">Diskon @if($order->discount_percent)({{ $order->discount_percent }}%)@endif</td><td class="value">{{ Format::idr($order->discount_amount) }}</td></tr>
        <tr><td class="label">DPP</td><td class="value">{{ Format::idr($order->dpp_amount) }}</td></tr>
        <tr><td class="label">PPN</td><td class="value">{{ Format::idr($order->ppn_amount) }}</td></tr>
        <tr><td class="label">PPh</td><td class="value">{{ Format::idr($order->pph_amount) }}</td></tr>
        <tr><td class="label">Total</td><td class="value">{{ Format::idr($order->total_amount) }}</td></tr>
    </table>

    <div style="margin-top:12px">
        <strong>Status invoice:</strong>
        @forelse ($order->invoices as $invoice)
            {{ $invoice->number }} ({{ $invoice->paymentStatus()->label() }})@if(!$loop->last), @endif
        @empty
            Belum ada invoice.
        @endforelse
    </div>

    @if ($order->notes)
        <div class="note" style="margin-top:12px"><strong>Catatan:</strong> {{ $order->notes }}</div>
    @endif
</body>
</html>