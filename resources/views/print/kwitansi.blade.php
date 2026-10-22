<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <title>Kwitansi {{ $allocation->receipt_number ?? $payment->number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; margin: 0; padding: 24px; font-size: 13px; }
        .sheet { max-width: 900px; margin: 0 auto; }

        .doc-title { text-align: center; font-size: 22px; letter-spacing: 4px; font-weight: bold; margin: 0 0 18px; }
        .title-box { border: 3px double #111827; padding: 6px 0; text-align: center; margin-bottom: 16px; }

        .meta { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .meta td { padding: 3px 0; vertical-align: top; }
        .meta .label { width: 170px; color: #4b5563; white-space: nowrap; }
        .meta .sep { width: 10px; color: #4b5563; }
        .meta .value { font-style: italic; font-weight: bold; }

        .middle { display: flex; gap: 16px; align-items: flex-start; margin-bottom: 14px; }
        .amount-box { flex: 0 0 auto; width: 230px; }
        .amount-box .label { text-align: right; font-style: italic; font-weight: bold; margin-bottom: 2px; }
        .amount-box .value { border: 1px solid #111827; padding: 5px 10px; text-align: center; font-weight: bold; font-size: 15px; }

        .note-box { flex: 1 1 auto; min-width: 0; border: 1px solid #111827; }
        .note-box .head { background: #111827; color: #ffffff; text-align: center; font-weight: bold; font-style: italic; padding: 5px 8px; font-size: 12px; }
        .note-box table { width: 100%; border-collapse: collapse; }
        .note-box td { padding: 4px 8px; border-bottom: 1px solid #e5e7eb; }
        .note-box td.num { text-align: right; white-space: nowrap; }
        .note-box tr:last-child td { border-bottom: none; font-weight: bold; }

        .side { flex: 0 0 210px; text-align: center; }
        .side .place { font-size: 13px; margin-bottom: 2px; }
        .side .role { font-size: 13px; }
        .side .qr { margin-top: 6px; }
        .side .qr svg { width: 108px; height: 108px; }

        .breakdown { border: 1px solid #111827; margin-bottom: 10px; }
        .breakdown .head { background: #111827; color: #ffffff; text-align: center; font-weight: bold; font-style: italic; padding: 5px 8px; font-size: 12px; }
        .breakdown table { width: 100%; border-collapse: collapse; }
        .breakdown th { background: #f3f4f6; font-size: 12px; padding: 5px 8px; text-align: left; border-bottom: 1px solid #9ca3af; }
        .breakdown td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
        .breakdown td.num { text-align: right; white-space: nowrap; }
        .breakdown tr:last-child td { border-bottom: none; font-weight: bold; }

        .foot-note { font-style: italic; margin-bottom: 22px; }

        .signatures { display: flex; justify-content: space-between; gap: 16px; margin-top: 8px; }
        .signatures .block { flex: 1 1 0; text-align: center; font-size: 12px; }
        .signatures .block .role { font-weight: bold; text-decoration: underline; }
        .signatures .block .name { font-weight: bold; margin-top: 54px; text-decoration: underline; }
        .signatures .block .meta { margin: 0; font-size: 11px; }
        .signatures .block .meta td { padding: 0; }

        .footer { margin-top: 28px; color: #6b7280; font-size: 11px; text-align: center; }
        .print-button { margin-bottom: 16px; }
        @media print {
            .print-button { display: none; }
            body { padding: 0; }
            .sheet { max-width: none; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <button class="print-button" onclick="window.print()">Cetak</button>

        <h1 class="doc-title title-box">KWITANSI</h1>

        <table class="meta">
            <tr>
                <td class="label">NO.</td>
                <td class="sep">:</td>
                <td class="value">{{ $allocation->receipt_number ?? $payment->number }}</td>
            </tr>
            <tr>
                <td class="label">Telah Terima Dari</td>
                <td class="sep">:</td>
                <td class="value">{{ $payerName }}</td>
            </tr>
            <tr>
                <td class="label">Jumlah Uang</td>
                <td class="sep">:</td>
                <td class="value">{{ $amountInWords }}</td>
            </tr>
            <tr>
                <td class="label">Untuk Pembayaran</td>
                <td class="sep">:</td>
                <td class="value">{{ $purpose }}</td>
            </tr>
        </table>

        <div class="middle">
            <div class="amount-box">
                <div class="label">Terbilang Rp.</div>
                <div class="value">{{ $format($allocation->amount) }}</div>
            </div>

            <div class="note-box">
                <div class="head">Catatan Pembayaran Herculer<br>(Remaining Balance Calculation)</div>
                <table>
                    <tr>
                        <td>Total Invoice Asli</td>
                        <td class="num">Rp. {{ $format($invoice->total_amount) }}</td>
                    </tr>
                    <tr>
                        <td>Pembayaran Saat Ini</td>
                        <td class="num">Rp. {{ $format($allocation->amount) }}</td>
                    </tr>
                    <tr>
                        <td>Sisa Hutang</td>
                        <td class="num">Rp. {{ $format($remaining) }}</td>
                    </tr>
                </table>
            </div>

            <div class="side">
                <div class="place">{{ $place }}, {{ $payment->payment_date->format('d F Y') }}</div>
                <div class="role">Rekanan,</div>
                <div class="qr">{!! $qrCode !!}</div>
            </div>
        </div>

        <div class="breakdown">
            <div class="head">Rincian Pembayaran Sebagian</div>
            <table>
                <thead>
                    <tr>
                        <th>Deskripsi</th>
                        <th style="text-align: right;">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Total Tagihan (Invoice) {{ $invoice->number }}</td>
                        <td class="num">Rp. {{ $format($invoice->total_amount) }}</td>
                    </tr>
                    <tr>
                        <td>Pembayaran Ini</td>
                        <td class="num">Rp. {{ $format($allocation->amount) }}</td>
                    </tr>
                    <tr>
                        <td>Sisa Hutang Berjalan</td>
                        <td class="num">Rp. {{ $format($remaining) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="foot-note">Mohon lunasi sisa hutang sesuai ketentuan.</p>

        <div class="signatures">
            <div class="block">
                <div class="role">Kepala Sekolah</div>
                <div class="name">{{ $school->principal_name ?? '-' }}</div>
                <table class="meta">
                    <tr><td>NIP: {{ $school->principal_nip ?? '' }}</td></tr>
                </table>
            </div>

            <div class="block">
                <div class="role">Bendahara</div>
                <div class="name">{{ $financeName }}</div>
                <table class="meta">
                    <tr><td>NIP: {{ $financeNip }}</td></tr>
                </table>
            </div>

            <div class="block">
                <div class="role">Pj. Physiolis</div>
                <div class="name">{{ $representativeName }}</div>
                <table class="meta">
                    <tr><td>NIK. {{ $representativeNik }}</td></tr>
                </table>
            </div>
        </div>

        <div class="footer">
            Dicetak pada {{ now()->format('d/m/Y H:i') }}.
        </div>
    </div>
</body>
</html>
