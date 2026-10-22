<?php

namespace App\Support;

/**
 * Terbilang (angka jadi kata) dalam bahasa Indonesia.
 *
 * Dipakai pada kwitansi untuk menulis nominal pembayaran secara verbal,
 * misalnya 100000 menjadi "Seratus Ribu Rupiah".
 */
final class NumberToWords
{
    private const ONES = [
        '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh',
        'Sebelas', 'Dua Belas', 'Tiga Belas', 'Empat Belas', 'Lima Belas', 'Enam Belas', 'Tujuh Belas',
        'Delapan Belas', 'Sembilan Belas', 'Dua Puluh',
    ];

    private const TENS = [
        2 => 'Dua Puluh', 3 => 'Tiga Puluh', 4 => 'Empat Puluh', 5 => 'Lima Puluh',
        6 => 'Enam Puluh', 7 => 'Tujuh Puluh', 8 => 'Delapan Puluh', 9 => 'Sembilan Puluh',
    ];

    private const HUNDREDS = [
        1 => 'Seratus', 2 => 'Dua Ratus', 3 => 'Tiga Ratus', 4 => 'Empat Ratus', 5 => 'Lima Ratus',
        6 => 'Enam Ratus', 7 => 'Tujuh Ratus', 8 => 'Delapan Ratus', 9 => 'Sembilan Ratus',
    ];

    private const SCALES = [
        1_000_000_000 => 'Miliar',
        1_000_000 => 'Juta',
        1_000 => 'Ribu',
    ];

    /**
     * Terbilang untuk nominal uang, contoh: 100000 => "Seratus Ribu Rupiah".
     */
    public static function rupiah(float|int|string $amount): string
    {
        $value = (int) round((float) $amount);

        if ($value === 0) {
            return 'Nol Rupiah';
        }

        $words = self::words($value);

        return $words.' Rupiah';
    }

    /**
     * Terbilang tanpa satuan mata uang.
     */
    public static function words(int $value): string
    {
        if ($value === 0) {
            return 'Nol';
        }

        if ($value < 0) {
            return 'Minus '.self::words(-$value);
        }

        $parts = [];

        foreach (self::SCALES as $scale => $name) {
            $chunk = intdiv($value, $scale);
            $value %= $scale;

            if ($chunk > 0) {
                $parts[] = self::words($chunk).' '.$name;
            }
        }

        if ($value > 0) {
            $parts[] = self::belowThousand($value);
        }

        return implode(' ', $parts);
    }

    private static function belowThousand(int $value): string
    {
        $parts = [];

        $hundred = intdiv($value, 100);
        $rest = $value % 100;

        if ($hundred > 0) {
            $parts[] = self::HUNDREDS[$hundred];
        }

        if ($rest > 0) {
            $parts[] = self::belowHundred($rest);
        }

        return implode(' ', $parts);
    }

    private static function belowHundred(int $value): string
    {
        if ($value < 21) {
            return self::ONES[$value];
        }

        $tens = intdiv($value, 10);
        $rest = $value % 10;

        $words = self::TENS[$tens];

        if ($rest > 0) {
            $words .= ' '.self::ONES[$rest];
        }

        return $words;
    }
}
