<?php

namespace App\Services;

use App\Support\Money;

/**
 * Hitung PPN / PPh berdasarkan jenis produk.
 *
 * Aturan:
 *  - Barang lainnya / jasa (type != "book"): DPP = harga × 100/111,
 *    PVN = 12% × DPP, PPh = 2% × DPP.
 *  - Buku (type = "book"): dibebaskan PPN, PPh = 0,5% × harga.
 *
 * Semua nilai dikelompokkan per order/item dan dikembalikan sebagai
 * string desimal siap simpan ke kolom DECIMAL(15,2).
 */
class TaxCalculator
{
    /**
     * @param  iterable<array{type: string, unit_price: string|int|float, quantity: int|string}>  $lines
     * @return array{dpp: string, ppn: string, pph: string}
     */
    public function calculate(iterable $lines): array
    {
        $dpp = '0.00';
        $ppn = '0.00';
        $pph = '0.00';

        foreach ($lines as $line) {
            $type = (string) $line['type'];
            $price = (string) $line['unit_price'];
            $qty = (string) $line['quantity'];
            $lineTotal = Money::mul($price, $qty);

            if ($type === 'book') {
                $linePph = Money::percentage($lineTotal, '0.5');
                $pph = Money::add($pph, $linePph);

                continue;
            }

            $lineDpp = Money::mul($lineTotal, '100');
            $lineDpp = Money::div($lineDpp, '111');
            $linePpn = Money::percentage($lineDpp, '12');
            $linePph = Money::percentage($lineDpp, '2');

            $dpp = Money::add($dpp, $lineDpp);
            $ppn = Money::add($ppn, $linePpn);
            $pph = Money::add($pph, $linePph);
        }

        return [
            'dpp' => $dpp,
            'ppn' => $ppn,
            'pph' => $pph,
        ];
    }
}
