<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; margin: 0; padding: 32px; font-size: 13px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #111827; padding-bottom: 12px; margin-bottom: 20px; }
        .company h1 { margin: 0 0 4px; font-size: 18px; }
        .company p { margin: 0; color: #4b5563; }
        .doc h2 { margin: 0 0 8px; font-size: 22px; letter-spacing: 1px; }
        .doc table { border-collapse: collapse; }
        .doc td { padding: 2px 0; }
        .doc td:first-child { color: #4b5563; padding-right: 12px; }
        .parties { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .parties h3 { margin: 0 0 4px; font-size: 12px; text-transform: uppercase; color: #6b7280; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.items th, table.items td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        table.items th { background: #f3f4f6; }
        table.items td.num, table.items th.num { text-align: right; }
        .totals { width: 260px; margin-left: auto; }
        .totals table { width: 100%; border-collapse: collapse; }
        .totals td { padding: 4px 0; }
        .totals td:last-child { text-align: right; }
        .totals tr.grand td { border-top: 2px solid #111827; font-weight: bold; padding-top: 8px; }
        .footer { margin-top: 40px; color: #6b7280; font-size: 11px; }
        .print-button { margin-bottom: 16px; }
        @media print { .print-button { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">Cetak</button>

    <div class="header">
        <div class="company">
            <h1>{{ $company['name'] }}</h1>
            <p>{{ $company['address'] }}</p>
            <p>{{ $company['phone'] }}</p>
            @if (! empty($company['email']))
                <p>{{ $company['email'] }}</p>
            @endif
        </div>
        <div class="doc">
            <h2>INVOICE</h2>
            <table>
                <tr><td>No. Invoice</td><td><strong>{{ $invoice->number }}</strong></td></tr>
                <tr><td>Tanggal</td><td>{{ $invoice->invoice_date->format('d/m/Y') }}</td></tr>
                <tr><td>Jatuh Tempo</td><td>{{ $invoice->due_date->format('d/m/Y') }}</td></tr>
                <tr><td>Status</td><td>{{ $invoice->status->label() }}</td></tr>
            </table>
        </div>
    </div>

    <div class="parties">
        <div>
            <h3>Ditagihkan kepada</h3>
            <div><strong>{{ $invoice->school?->name }}</strong></div>
            <div>NPSN: {{ $invoice->school?->npsn ?? '-' }}</div>
            <div>{{ $invoice->school?->address ?? '-' }}</div>
        </div>
        <div>
            <h3>Referensi</h3>
            <div>Pesanan: {{ $invoice->order?->number ?? '-' }}</div>
            <div>Pengiriman: {{ $invoice->shipment?->number ?? '-' }}</div>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th class="num">Qty</th>
                <th class="num">Harga</th>
                <th class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                    <td class="num">Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr><td>Subtotal</td><td>Rp {{ number_format((float) $invoice->subtotal, 0, ',', '.') }}</td></tr>
            <tr><td>Diskon</td><td>Rp {{ number_format((float) $invoice->discount_amount, 0, ',', '.') }}</td></tr>
            <tr><td>Pajak</td><td>Rp {{ number_format((float) $invoice->tax_amount, 0, ',', '.') }}</td></tr>
            <tr class="grand"><td>Total</td><td>Rp {{ number_format((float) $invoice->total_amount, 0, ',', '.') }}</td></tr>
            <tr><td>Dibayar</td><td>Rp {{ number_format((float) $invoice->paid_amount, 0, ',', '.') }}</td></tr>
            <tr><td><strong>Sisa</strong></td><td><strong>Rp {{ number_format((float) $invoice->outstandingAmount(), 0, ',', '.') }}</strong></td></tr>
        </table>
    </div>

    <div class="footer">
        Dicetak pada {{ now()->format('d/m/Y H:i') }}.
    </div>
</body>
</html>
