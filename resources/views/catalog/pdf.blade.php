<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Katalog {{ $catalog->name }}</title>
    <style>
        * { box-sizing: border-box; }

        /* A4 page system: the cover bleeds edge to edge, interior pages keep
           generous editorial margins for the stamped header and footer. */
        @page { size: A4; margin: 80px 56px 76px; }
        @page :first { margin: 0; }

        body {
            margin: 0;
            padding: 0;
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 10px;
            line-height: 1.5;
            color: #26384C;
        }

        /* ==================== Cover ==================== */
        .cover {
            position: relative;
            height: 297mm;
            background-color: #0E1F33;
            color: #F6F2E9;
            page-break-after: always;
        }
        .cover-frame {
            position: absolute;
            top: 26px;
            right: 26px;
            bottom: 26px;
            left: 26px;
            border: 1px solid #A98545;
        }
        .cover-frame-inner {
            position: absolute;
            top: 33px;
            right: 33px;
            bottom: 33px;
            left: 33px;
            border: 1px solid #33475F;
        }
        .cover-body { padding: 66px 64px 0; }
        .cover-brand { text-align: center; }
        .logo-panel {
            display: inline-block;
            background-color: #FFFFFF;
            padding: 15px 26px;
        }
        .logo-panel img { display: block; height: 82px; width: auto; }
        .logo-missing {
            display: inline-block;
            border: 1px solid #A98545;
            padding: 16px 30px;
            font-family: "DejaVu Serif", Georgia, serif;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 4px;
            color: #D9C79A;
            text-transform: uppercase;
        }
        .cover-company {
            margin-top: 20px;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 5px;
            text-transform: uppercase;
            color: #D9C79A;
        }
        .cover-title { margin-top: 218px; text-align: center; }
        .cover-kicker {
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 6px;
            text-transform: uppercase;
            color: #A98545;
        }
        .cover-title h1 {
            font-family: "DejaVu Serif", Georgia, serif;
            font-size: 42px;
            font-weight: bold;
            letter-spacing: 6px;
            margin: 24px 0 0;
            color: #F6F2E9;
        }
        .cover-rule {
            width: 170px;
            height: 0;
            border-top: 1px solid #A98545;
            margin: 30px auto 0;
        }
        .cover-sub {
            font-family: "DejaVu Serif", Georgia, serif;
            font-size: 17px;
            font-style: italic;
            color: #C8D2DE;
            margin-top: 24px;
        }
        .cover-desc {
            font-size: 10.5px;
            color: #93A4B8;
            margin-top: 10px;
        }
        .cover-foot {
            position: absolute;
            left: 64px;
            right: 64px;
            bottom: 54px;
            text-align: center;
            font-size: 9.5px;
            line-height: 1.75;
            color: #8FA3B8;
        }
        .cover-foot-rule {
            height: 0;
            border-top: 1px solid #33475F;
            margin: 0 auto 16px;
        }
        .cover-date {
            margin-top: 10px;
            font-size: 9px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #D9C79A;
        }

        /* ==================== Interior ==================== */
        .doc-head { margin-bottom: 30px; }
        .doc-kicker {
            font-size: 9.5px;
            font-weight: bold;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: #A98545;
        }
        .doc-title {
            font-family: "DejaVu Serif", Georgia, serif;
            font-size: 23px;
            font-weight: bold;
            color: #142A42;
            letter-spacing: 1px;
            margin: 8px 0 0;
        }
        .doc-desc { font-size: 10.5px; color: #6E7A88; margin-top: 6px; }
        .doc-rule {
            height: 0;
            border-top: 1px solid #A98545;
            margin-top: 18px;
        }
        .doc-count {
            font-size: 9px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #6E7A88;
            margin-top: 8px;
        }

        table.entry {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        table.entry td {
            padding: 9px 0;
            vertical-align: top;
            border-bottom: 1px solid #E4DED1;
        }
        table.entry td.entry-no {
            width: 38px;
            padding-top: 11px;
            font-family: "DejaVu Serif", Georgia, serif;
            font-size: 10.5px;
            font-weight: bold;
            color: #A98545;
        }
        table.entry td.entry-price {
            width: 96px;
            padding-top: 10px;
            text-align: right;
            white-space: nowrap;
            font-size: 11.5px;
            font-weight: bold;
            color: #142A42;
        }
        table.entry td.entry-qty {
            width: 64px;
            padding-top: 11px;
            padding-left: 14px;
            text-align: right;
        }
        .qty-label {
            width: 42px;
            margin-left: auto;
            margin-bottom: 3px;
            text-align: center;
            font-size: 6.5px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #6E7A88;
        }
        .qty-box {
            width: 42px;
            height: 26px;
            margin-left: auto;
            border: 0.75px solid #A98545;
            font-size: 8px;
            line-height: 26px;
            color: #6E7A88;
        }
        .entry-title {
            font-family: "DejaVu Serif", Georgia, serif;
            font-size: 12.5px;
            font-weight: bold;
            line-height: 1.45;
            color: #142A42;
        }
        .entry-meta {
            font-size: 9px;
            line-height: 1.55;
            color: #6E7A88;
            margin-top: 3px;
        }

        .doc-end {
            text-align: center;
            margin-top: 34px;
            page-break-inside: avoid;
        }
        .doc-end-rule {
            width: 70px;
            height: 0;
            border-top: 1px solid #A98545;
            margin: 0 auto;
        }
        .doc-end-text {
            font-size: 9px;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: #6E7A88;
            margin-top: 12px;
        }
        .doc-end-company {
            font-family: "DejaVu Serif", Georgia, serif;
            font-size: 11px;
            color: #142A42;
            margin-top: 6px;
        }
    </style>
</head>
<body>
    {{-- Cover: full-bleed navy, typographic composition, no photographs. --}}
    <div class="cover">
        <div class="cover-frame"></div>
        <div class="cover-frame-inner"></div>

        <div class="cover-body">
            <div class="cover-brand">
                @if ($logo !== null)
                    <div class="logo-panel">
                        <img src="{{ $logo }}" alt="Logo {{ $company['name'] }}" />
                    </div>
                @else
                    <div class="logo-missing">{{ $company['name'] }}</div>
                @endif

                <div class="cover-company">{{ $company['name'] }}</div>
            </div>

            <div class="cover-title">
                <div class="cover-kicker">Edisi {{ now()->year }}</div>
                <h1>KATALOG BUKU</h1>
                <div class="cover-rule"></div>
                <div class="cover-sub">{{ $catalog->name }}</div>
                @if ($catalog->description)
                    <div class="cover-desc">{{ $catalog->description }}</div>
                @endif
            </div>
        </div>

        <div class="cover-foot">
            <div class="cover-foot-rule"></div>
            @if ($company['address'] !== '')<div>{{ $company['address'] }}</div>@endif
            @if ($company['phone'] !== '')<div>Telp. {{ $company['phone'] }}</div>@endif
            @if ($company['email'] !== '')<div>{{ $company['email'] }}</div>@endif
            <div class="cover-date">Dicetak pada {{ $today }}</div>
        </div>
    </div>

    {{-- Interior: editorial entries in the original sort order. --}}
    @php
        $items = $catalog->items->sortBy('sort_order')->values();
        $numberWidth = max(2, strlen((string) $items->count()));
    @endphp

    <div class="doc-head">
        <div class="doc-kicker">Katalog Buku</div>
        <div class="doc-title">{{ $catalog->name }}</div>
        @if ($catalog->description)
            <div class="doc-desc">{{ $catalog->description }}</div>
        @endif
        <div class="doc-rule"></div>
        <div class="doc-count">{{ $items->count() }} Produk {{ $catalog->zona !== null ? '· Harga '.$catalog->zona->name : '' }}</div>
    </div>

    @foreach ($items as $index => $item)
        @php
            $product = $item->product;
            $book = $product?->book;
            $goods = $product?->goods;

            $meta = [];
            if ($product?->code !== null && $product->code !== '') {
                $meta[] = $product->code;
            }
            if ($book !== null) {
                if ($book->publisher?->name !== null && $book->publisher->name !== '') {
                    $meta[] = $book->publisher->name;
                }
                if ($book->category?->name !== null && $book->category->name !== '') {
                    $meta[] = $book->category->name;
                }
                if ($book->grade !== null && $book->grade !== '') {
                    $meta[] = 'Kelas '.$book->grade;
                }
                if ($book->isbn !== null && $book->isbn !== '') {
                    $meta[] = 'ISBN '.$book->isbn;
                }
            } elseif ($goods !== null) {
                if ($goods->description !== null && $goods->description !== '') {
                    $meta[] = $goods->description;
                }
                if ($goods->store_name !== null && $goods->store_name !== '') {
                    $meta[] = $goods->store_name;
                }
                if ($goods->unit !== null && $goods->unit !== '') {
                    $meta[] = 'Satuan: '.$goods->unit;
                }
            }

            // The catalog prints the selected zone's price when one is set and
            // the product has a price for it, otherwise the base price.
            $price = (float) ($product?->price ?? 0);
            if ($catalog->zona_id !== null && $product !== null) {
                $zonaPrice = $product->prices->firstWhere('zona_id', $catalog->zona_id);
                if ($zonaPrice !== null) {
                    $price = (float) $zonaPrice->price;
                }
            }
        @endphp
        <table class="entry">
            <tr>
                <td class="entry-no">{{ str_pad((string) ($index + 1), $numberWidth, '0', STR_PAD_LEFT) }}</td>
                <td class="entry-main">
                    <div class="entry-title">{{ $product?->title ?? '-' }}</div>
                    @if ($meta !== [])
                        <div class="entry-meta">{{ implode(' · ', $meta) }}</div>
                    @endif
                </td>
                <td class="entry-price">{{ $price > 0 ? 'Rp '.number_format($price, 0, ',', '.') : '—' }}</td>
                <td class="entry-qty">
                    <div class="qty-label">Jumlah</div>
                    <div class="qty-box">&#8203;</div>
                </td>
            </tr>
        </table>
    @endforeach

    <div class="doc-end">
        <div class="doc-end-rule"></div>
        <div class="doc-end-text">Akhir Katalog</div>
        <div class="doc-end-company">{{ $company['name'] }}</div>
    </div>
</body>
</html>
