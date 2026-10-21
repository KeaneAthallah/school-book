<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <title>Surat Mutasi Stok</title>
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
        </div>
        <div class="doc">
            <h2>SURAT MUTASI STOK</h2>
            <table>
                <tr><td>Tanggal</td><td>{{ $mutation->created_at?->format('d/m/Y H:i') }}</td></tr>
                <tr><td>Jenis</td><td>{{ match($mutation->type) { 'lost' => 'Hilang', 'damaged' => 'Rusak', 'borrowed' => 'Dipinjam', default => $mutation->type } }}</td></tr>
            </table>
        </div>
    </div>
    <table style="width:100%; border-collapse:collapse; margin-bottom:16px;">
        <thead>
            <tr>
                <th style="border:1px solid #d1d5db; padding:6px 8px; background:#f3f4f6;">Kode</th>
                <th style="border:1px solid #d1d5db; padding:6px 8px; background:#f3f4f6;">Barang</th>
                <th style="border:1px solid #d1d5db; padding:6px 8px; background:#f3f4f6; text-align:right;">Jumlah</th>
                <th style="border:1px solid #d1d5db; padding:6px 8px; background:#f3f4f6;">Alasan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border:1px solid #d1d5db; padding:6px 8px;">{{ $mutation->product?->code ?? '-' }}</td>
                <td style="border:1px solid #d1d5db; padding:6px 8px;">{{ $mutation->product?->title ?? '-' }}</td>
                <td style="border:1px solid #d1d5db; padding:6px 8px; text-align:right;">{{ $mutation->quantity }}</td>
                <td style="border:1px solid #d1d5db; padding:6px 8px;">{{ $mutation->reason ?? '-' }}</td>
            </tr>
        </tbody>
    </table>
    <div style="margin-top: 40px; display: flex; justify-content: flex-end;">
        <div style="text-align: center; width: 220px;">
            <div>Palu, Tgl. ...............</div>
            <div style="margin-top: 8px;">Petugas Gudang</div>
            <div style="margin-top: 60px;">( Nama )</div>
        </div>
    </div>
</body>
</html>