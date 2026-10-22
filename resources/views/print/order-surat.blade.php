<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <title>Surat Pesanan {{ $order->number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; margin: 0; padding: 32px; font-size: 13px; }
        .doc-title { text-align: center; font-size: 24px; letter-spacing: 2px; margin: 0 0 20px; font-weight: bold; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; margin-bottom: 22px; }
        .fields { flex: 1 1 auto; min-width: 0; }
        .fields table { border-collapse: collapse; }
        .fields td { padding: 1.5px 0; vertical-align: top; }
        .fields .label { color: #4b5563; white-space: nowrap; }
        .fields .sep { color: #4b5563; padding: 0 6px; }
        .fields .group { margin-top: 12px; }
        .company { text-align: right; flex: 0 0 auto; }
        .company img { max-height: 52px; margin-bottom: 6px; }
        .company .name { font-size: 16px; font-weight: bold; margin: 0 0 2px; }
        .company p { margin: 0; color: #4b5563; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.items th, table.items td { border: 1px solid #9ca3af; padding: 5px 8px; }
        table.items th { background: #f3f4f6; text-align: left; }
        table.items th.num, table.items td.num { text-align: right; }
        table.items th.c-no, table.items td.c-no { text-align: center; width: 6%; }
        table.items th.col-title { width: 53%; }
        table.items tfoot td { font-weight: bold; background: #f9fafb; }
        .bottom { display: flex; justify-content: space-between; gap: 24px; align-items: flex-start; }
        .keterangan { flex: 1 1 55%; min-width: 0; font-size: 11px; color: #4b5563; line-height: 1.5; }
        .keterangan h4 { margin: 0 0 4px; font-size: 12px; color: #111827; }
        .keterangan p { margin: 0 0 6px; }
        .summary { flex: 0 0 260px; }
        .summary table { width: 100%; border-collapse: collapse; }
        .summary td { padding: 2.5px 0; }
        .summary td:last-child { text-align: right; white-space: nowrap; }
        .summary tr.total td { font-weight: bold; }
        .summary tr.grand td { border-top: 2px solid #111827; font-weight: bold; padding-top: 6px; margin-top: 4px; }
        .signature { margin-top: 44px; display: flex; justify-content: space-between; gap: 24px; }
        .signature .box { text-align: center; flex: 1 1 0; }
        .signature .name { margin-top: 60px; font-weight: bold; }
        .footer { margin-top: 36px; color: #6b7280; font-size: 11px; }
        .print-button { margin-bottom: 16px; }
        @media print {
            .print-button { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">Cetak</button>

    <h1 class="doc-title">SURAT PESANAN</h1>

    <div class="header">
        <div class="fields">
            <table>
                <tr><td class="label">No. Pesanan</td><td class="sep">:</td><td><strong>{{ $order->number }}</strong></td></tr>
                <tr><td class="label">Tanggal</td><td class="sep">:</td><td>{{ $order->order_date?->format('d/m/Y') }}</td></tr>
                <tr><td class="label">Status</td><td class="sep">:</td><td>{{ $order->status->label() }}</td></tr>
            </table>

            <table class="group">
                <tr><td class="label">Sekolah</td><td class="sep">:</td><td><strong>{{ $order->school?->name ?? '-' }}</strong></td></tr>
                <tr><td class="label">NPSN</td><td class="sep">:</td><td>{{ $order->school?->npsn ?? '-' }}</td></tr>
                <tr><td class="label">Alamat</td><td class="sep">:</td><td>{{ $order->school?->address ?? '-' }}</td></tr>
                <tr><td class="label">Operator</td><td class="sep">:</td><td>{{ $order->operator?->name ?? '-' }}</td></tr>
            </table>
        </div>

        <div class="company">
            @if (! empty($company['logo']))
                <img src="{{ $company['logo'] }}" alt="{{ $company['name'] }}" />
            @endif
            <p class="name">{{ $company['name'] }}</p>
            <p>{{ $company['address'] }}</p>
            <p>{{ $company['phone'] }}</p>
            @if (! empty($company['email']))
                <p>{{ $company['email'] }}</p>
            @endif
        </div>
    </div>

    @php
        $totalQty = 0;
        $format = static fn (mixed $value): string => number_format((float) $value, 0, ',', '.');
    @endphp

    <table class="items">
        <thead>
            <tr>
                <th class="c-no">NO</th>
                <th class="col-title">NAMA BARANG</th>
                <th class="num">HARGA</th>
                <th class="num">JUMLAH</th>
                <th class="num">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                @php $totalQty += $item->quantity; @endphp
                <tr>
                    <td class="c-no">{{ $loop->index + 1 }}</td>
                    <td>{{ $item->product?->title ?? '-' }}</td>
                    <td class="num">{{ $format($item->unit_price) }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ $format($item->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">JUMLAH</td>
                <td class="num">{{ $totalQty }}</td>
                <td class="num">{{ $format($order->total_amount) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="bottom">
        <div class="keterangan">
            <h4>Keterangan</h4>
            @if (! empty($order->notes))
                <p>{{ $order->notes }}</p>
            @endif
        </div>

        <div class="summary">
            <table>
                <tr><td>Subtotal</td><td>{{ \App\Support\Format::idr($order->subtotal) }}</td></tr>
                <tr><td>Diskon</td><td>{{ \App\Support\Format::idr($order->discount_amount) }}</td></tr>
                <tr><td>Pajak</td><td>{{ \App\Support\Format::idr($order->tax_amount) }}</td></tr>
                <tr class="total"><td>Total</td><td>{{ \App\Support\Format::idr($order->total_amount) }}</td></tr>
            </table>
        </div>
    </div>

    <div class="signature">
        <div class="box">
            <div>{{ \App\Support\Format::date($order->order_date) }}</div>
            <div style="margin-top: 24px;">Pemesan</div>
            <div class="name">( .................... )</div>
        </div>
        <div class="box">
            <div>{{ \App\Support\Format::date($order->order_date) }}</div>
            <div style="margin-top: 24px;">Operator</div>
            <div class="name">{{ $operatorName ?? '-' }}</div>
        </div>
    </div>

    <div class="footer">
        Dicetak pada {{ now()->format('d/m/Y H:i') }}.
    </div>
</body>
</html>