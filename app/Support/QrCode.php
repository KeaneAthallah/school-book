<?php

namespace App\Support;

/**
 * Pembuat QR code (mode byte, level koreksi M) dalam bentuk SVG.
 *
 * Diimplementasikan mengikuti spesifikasi QR Code (ISO/IEC 18004) sehingga
 * hasilnya dapat dipindai oleh aplikasi QR di ponsel.
 *
 * Algoritma mengikuti referensiqrcode (python) yang telah dipakai luas:
 * pola fungsi -> penempatan data -> pemilihan mask -> informasi format.
 */
final class QrCode
{
    /** Level koreksi galat: M (15%). */
    private const EC_LEVEL_M = 0b00;

    /** Mask penanda informasi format (BCH). */
    private const FORMAT_MASK = 0x5412;

    /** Polinomial generator BCH untuk informasi format. */
    private const FORMAT_GENERATOR = 0x537;

    /** Polinomial prima GF(256) yang dipakai QR. */
    private const FIELD_POLYNOMIAL = 0x11D;

    /**
     * Blok Reed-Solomon untuk level M, versi 1 sampai 10.
     *
     * Setiap entri: [jumlah blok, total codeword per blok, data codeword per blok].
     *
     * @var array<int, list<array{int, int, int}>>
     */
    private const BLOCKS = [
        1 => [[1, 26, 16]],
        2 => [[1, 44, 28]],
        3 => [[1, 70, 44]],
        4 => [[2, 50, 32]],
        5 => [[2, 33, 15], [2, 34, 16]],
        6 => [[4, 43, 27]],
        7 => [[4, 49, 31]],
        8 => [[2, 60, 38], [2, 61, 39]],
        9 => [[3, 58, 36], [2, 59, 37]],
        10 => [[4, 69, 43], [1, 70, 44]],
    ];

    /**
     * Posisi pusat pola penyelarasan untuk versi 1 sampai 10.
     *
     * @var array<int, list<int>>
     */
    private const ALIGNMENT = [
        1 => [],
        2 => [6, 18],
        3 => [6, 22],
        4 => [6, 26],
        5 => [6, 30],
        6 => [6, 34],
        7 => [6, 22, 38],
        8 => [6, 24, 42],
        9 => [6, 26, 46],
        10 => [6, 28, 50],
    ];

    /**
     * Render QR code sebagai markup SVG.
     */
    public static function svg(string $text, int $moduleSize = 4, int $quietZone = 2): string
    {
        $matrix = self::matrix($text);

        $dimension = count($matrix) + $quietZone * 2;
        $size = $dimension * $moduleSize;

        $paths = [];
        foreach ($matrix as $row => $columns) {
            foreach ($columns as $col => $dark) {
                if (! $dark) {
                    continue;
                }

                $x = ($col + $quietZone) * $moduleSize;
                $y = ($row + $quietZone) * $moduleSize;

                $paths[] = sprintf('M%d %dh%dv%dh-%dz', $x, $y, $moduleSize, $moduleSize, $moduleSize);
            }
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %1$d %1$d" width="%1$d" height="%1$d" shape-rendering="crispEdges" role="img" aria-label="QR code"><rect width="%1$d" height="%1$d" fill="#ffffff"/><path d="%2$s" fill="#000000"/></svg>',
            $size,
            implode(' ', $paths)
        );
    }

    /**
     * Matriks modul QR. Dipakai untuk pengujian.
     *
     * @return list<list<bool>>
     */
    public static function matrix(string $text, ?int $mask = null): array
    {
        $version = self::versionFor($text);
        $codewords = self::codewords($text, $version);

        if ($mask !== null) {
            return self::build($codewords, $version, $mask);
        }

        $best = null;
        $bestPenalty = PHP_INT_MAX;

        for ($candidate = 0; $candidate < 8; $candidate++) {
            $matrix = self::build($codewords, $version, $candidate, true);
            $penalty = self::penalty($matrix);

            if ($penalty < $bestPenalty) {
                $bestPenalty = $penalty;
                $best = $candidate;
            }
        }

        return self::build($codewords, $version, $best ?? 0);
    }

    /**
     * Pilih versi terkecil yang muat memuat data.
     */
    private static function versionFor(string $text): int
    {
        $bytes = strlen($text);

        foreach (self::BLOCKS as $version => $blocks) {
            $capacity = 0;
            foreach ($blocks as [$count, $total, $data]) {
                $capacity += $count * $data;
            }

            // Panjang data mode byte: 8 bit untuk versi 1-9, 16 bit untuk versi 10+.
            $lengthBits = $version < 10 ? 8 : 16;
            $bits = 4 + $lengthBits + ($bytes * 8);

            if ($bits <= $capacity * 8) {
                return $version;
            }
        }

        return 10;
    }

    /**
     * Codeword data + koreksi galat, sudah diinterleave.
     *
     * @return list<int>
     */
    private static function codewords(string $text, int $version): array
    {
        $bytes = array_values(unpack('C*', $text) ?: []);

        $lengthBits = $version < 10 ? 8 : 16;

        $bits = array_merge(self::toBits(0b0100, 4), self::toBits(count($bytes), $lengthBits));
        foreach ($bytes as $byte) {
            $bits = array_merge($bits, self::toBits($byte, 8));
        }

        $blocks = self::BLOCKS[$version];
        $dataCount = 0;
        foreach ($blocks as [$count, $total, $data]) {
            $dataCount += $count * $data;
        }

        // Terminator.
        $bits = array_merge($bits, array_fill(0, min(4, ($dataCount * 8) - count($bits)), 0));

        // Pad ke batas byte.
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        $codewords = [];
        for ($i = 0; $i < count($bits); $i += 8) {
            $byte = 0;
            for ($j = 0; $j < 8; $j++) {
                $byte = ($byte << 1) | $bits[$i + $j];
            }
            $codewords[] = $byte;
        }

        // Pad byte 0xEC / 0x11.
        $pad = [0xEC, 0x11];
        $index = 0;
        while (count($codewords) < $dataCount) {
            $codewords[] = $pad[$index % 2];
            $index++;
        }

        return self::interleave($codewords, $blocks);
    }

    /**
     * Hitung kode koreksi galat per blok lalu interleave data dan EC.
     *
     * @param  list<int>  $codewords
     * @param  list<array{int, int, int}>  $blocks
     * @return list<int>
     */
    private static function interleave(array $codewords, array $blocks): array
    {
        $dataBlocks = [];
        $ecBlocks = [];
        $offset = 0;

        foreach ($blocks as [$count, $total, $data]) {
            for ($i = 0; $i < $count; $i++) {
                $block = array_slice($codewords, $offset, $data);
                $offset += $data;

                $dataBlocks[] = $block;
                $ecBlocks[] = self::reedSolomon($block, $total - $data);
            }
        }

        $result = [];

        $maxData = 0;
        foreach ($dataBlocks as $block) {
            $maxData = max($maxData, count($block));
        }

        for ($i = 0; $i < $maxData; $i++) {
            foreach ($dataBlocks as $block) {
                if (isset($block[$i])) {
                    $result[] = $block[$i];
                }
            }
        }

        $maxEc = 0;
        foreach ($ecBlocks as $block) {
            $maxEc = max($maxEc, count($block));
        }

        for ($i = 0; $i < $maxEc; $i++) {
            foreach ($ecBlocks as $block) {
                if (isset($block[$i])) {
                    $result[] = $block[$i];
                }
            }
        }

        return $result;
    }

    /**
     * Codeword koreksi galat Reed-Solomon untuk satu blok.
     *
     * @param  list<int>  $data
     * @return list<int>
     */
    private static function reedSolomon(array $data, int $ecCount): array
    {
        $generator = self::generatorPolynomial($ecCount);
        $message = array_merge($data, array_fill(0, $ecCount, 0));

        for ($i = 0; $i < count($data); $i++) {
            $coefficient = $message[$i];

            if ($coefficient === 0) {
                continue;
            }

            foreach ($generator as $j => $value) {
                $message[$i + $j] ^= self::multiply($coefficient, $value);
            }
        }

        return array_slice($message, count($data), $ecCount);
    }

    /**
     * Polinomial generator (x + a^0)(x + a^1)...(x + a^(n-1)).
     *
     * Koefisien disimpan dari pangkat tertinggi ke terendah.
     *
     * @return list<int>
     */
    private static function generatorPolynomial(int $degree): array
    {
        $polynomial = [1];

        for ($i = 0; $i < $degree; $i++) {
            $next = array_fill(0, count($polynomial) + 1, 0);

            foreach ($polynomial as $j => $coefficient) {
                $next[$j] ^= $coefficient;
                $next[$j + 1] ^= self::multiply($coefficient, self::exp($i));
            }

            $polynomial = array_values($next);
        }

        return $polynomial;
    }

    /** a^i dalam GF(256). */
    private static function exp(int $power): int
    {
        $result = 1;

        for ($i = 0; $i < $power; $i++) {
            $result = self::multiply($result, 2);
        }

        return $result;
    }

    /** Perkalian GF(256) dengan prima 0x11D. */
    private static function multiply(int $a, int $b): int
    {
        $result = 0;

        for ($i = 0; $i < 8; $i++) {
            if (($b & 1) !== 0) {
                $result ^= $a;
            }

            $b >>= 1;
            $a <<= 1;

            if (($a & 0x100) !== 0) {
                $a ^= self::FIELD_POLYNOMIAL;
            }
        }

        return $result & 0xFF;
    }

    /**
     * @param  list<int>  $codewords
     * @return list<list<bool>>
     */
    private static function build(array $codewords, int $version, int $mask, bool $test = false): array
    {
        $size = $version * 4 + 17;

        /** @var array<int, array<int, bool|null>> $matrix */
        $matrix = array_fill(0, $size, array_fill(0, $size, null));

        // Pola finder + separator.
        self::probe($matrix, 0, 0);
        self::probe($matrix, $size - 7, 0);
        self::probe($matrix, 0, $size - 7);

        // Pola penyelarasan.
        foreach (self::ALIGNMENT[$version] as $row) {
            foreach (self::ALIGNMENT[$version] as $col) {
                if ($matrix[$row][$col] !== null) {
                    continue;
                }

                for ($r = -2; $r <= 2; $r++) {
                    for ($c = -2; $c <= 2; $c++) {
                        $matrix[$row + $r][$col + $c] = $r === -2 || $r === 2 || $c === -2 || $c === 2 || ($r === 0 && $c === 0);
                    }
                }
            }
        }

        // Pola timing.
        for ($i = 8; $i < $size - 8; $i++) {
            if ($matrix[$i][6] === null) {
                $matrix[$i][6] = $i % 2 === 0;
            }
            if ($matrix[6][$i] === null) {
                $matrix[6][$i] = $i % 2 === 0;
            }
        }

        self::formatInfo($matrix, $mask, $test);
        self::mapData($matrix, $codewords, $mask);

        return array_map(
            static fn (array $row): array => array_values(array_map(static fn (?bool $cell): bool => (bool) $cell, $row)),
            array_values($matrix)
        );
    }

    /**
     * @param  array<int, array<int, bool|null>>  $matrix
     */
    private static function probe(array &$matrix, int $row, int $col): void
    {
        for ($r = -1; $r <= 7; $r++) {
            if ($row + $r < 0 || $row + $r >= count($matrix)) {
                continue;
            }

            for ($c = -1; $c <= 7; $c++) {
                if ($col + $c < 0 || $col + $c >= count($matrix)) {
                    continue;
                }

                $dark = (($r >= 0 && $r <= 6) && ($c === 0 || $c === 6))
                    || (($c >= 0 && $c <= 6) && ($r === 0 || $r === 6))
                    || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4);

                $matrix[$row + $r][$col + $c] = $dark;
            }
        }
    }

    /**
     * @param  array<int, array<int, bool|null>>  $matrix
     */
    private static function formatInfo(array &$matrix, int $mask, bool $test): void
    {
        $size = count($matrix);
        $bits = self::formatBits($mask);

        for ($i = 0; $i < 15; $i++) {
            $mod = (! $test) && (($bits >> $i) & 1) === 1;

            // Salinan vertikal.
            if ($i < 6) {
                $matrix[$i][8] = $mod;
            } elseif ($i < 8) {
                $matrix[$i + 1][8] = $mod;
            } else {
                $matrix[$size - 15 + $i][8] = $mod;
            }

            // Salinan horizontal.
            if ($i < 8) {
                $matrix[8][$size - $i - 1] = $mod;
            } elseif ($i < 9) {
                $matrix[8][15 - $i] = $mod;
            } else {
                $matrix[8][15 - $i - 1] = $mod;
            }
        }

        // Modul gelap tetap.
        $matrix[$size - 8][8] = ! $test;
    }
    /**
     * Nilai 15 bit informasi format (BCH + mask).
     */
    private static function formatBits(int $mask): int
    {
        $data = (self::EC_LEVEL_M << 3) | $mask;

        $remainder = $data << 10;
        while (self::bitLength($remainder) - self::bitLength(self::FORMAT_GENERATOR) >= 0) {
            $remainder ^= self::FORMAT_GENERATOR << (self::bitLength($remainder) - self::bitLength(self::FORMAT_GENERATOR));
        }

        return (($data << 10) | $remainder) ^ self::FORMAT_MASK;
    }

    private static function bitLength(int $value): int
    {
        $length = 0;

        while ($value !== 0) {
            $length++;
            $value >>= 1;
        }

        return $length;
    }

    /**
     * @param  array<int, array<int, bool|null>>  $matrix
     * @param  list<int>  $codewords
     */
    private static function mapData(array &$matrix, array $codewords, int $mask): void
    {
        $size = count($matrix);

        $byteIndex = 0;
        $bitIndex = 7;

        // Data diisi dalam pola zigzag: kolom pairs berjalan naik-turun bergantian.
        $row = $size - 1;
        $step = -1;

        for ($col = $size - 1; $col > 0; $col -= 2) {
            $pair = $col <= 6 ? $col - 1 : $col;

            while (true) {
                foreach ([$pair, $pair - 1] as $c) {
                    if ($matrix[$row][$c] !== null) {
                        continue;
                    }

                    $dark = false;

                    if ($byteIndex < count($codewords)) {
                        $dark = (($codewords[$byteIndex] >> $bitIndex) & 1) === 1;
                    }

                    if (self::maskBit($mask, $row, $c)) {
                        $dark = ! $dark;
                    }

                    $matrix[$row][$c] = $dark;

                    $bitIndex--;
                    if ($bitIndex === -1) {
                        $byteIndex++;
                        $bitIndex = 7;
                    }
                }

                $row += $step;

                if ($row < 0 || $row >= $size) {
                    $row -= $step;
                    $step = -$step;
                    break;
                }
            }
        }
    }

    private static function maskBit(int $mask, int $row, int $col): bool
    {
        return match ($mask) {
            0 => ($row + $col) % 2 === 0,
            1 => $row % 2 === 0,
            2 => $col % 3 === 0,
            3 => ($row + $col) % 3 === 0,
            4 => (intdiv($row, 2) + intdiv($col, 3)) % 2 === 0,
            5 => (($row * $col) % 2) + (($row * $col) % 3) === 0,
            6 => ((($row * $col) % 2) + (($row * $col) % 3)) % 2 === 0,
            7 => ((($row + $col) % 2) + (($row * $col) % 3)) % 2 === 0,
            default => false,
        };
    }

    /**
     * Aturan penalti pemilihan mask (ISO/IEC 18004).
     *
     * @param  list<list<bool>>  $matrix
     */
    private static function penalty(array $matrix): int
    {
        $size = count($matrix);
        $penalty = 0;

        // Aturan 1: deret module sejenis sepanjang 5 atau lebih.
        for ($i = 0; $i < $size; $i++) {
            $penalty += self::runPenalty(array_map(static fn (bool $cell): int => $cell ? 1 : 0, $matrix[$i]));
            $penalty += self::runPenalty(array_map(static fn (bool $cell): int => $cell ? 1 : 0, array_column($matrix, $i)));
        }

        // Aturan 2: blok 2x2 dengan warna sama.
        for ($row = 0; $row < $size - 1; $row++) {
            for ($col = 0; $col < $size - 1; $col++) {
                $value = $matrix[$row][$col];

                if ($value === $matrix[$row][$col + 1]
                    && $value === $matrix[$row + 1][$col]
                    && $value === $matrix[$row + 1][$col + 1]) {
                    $penalty += 3;
                }
            }
        }

        // Aturan 3: pola 1:1:3:1:1 dengan 4 module terang di sekitarnya.
        for ($i = 0; $i < $size; $i++) {
            $penalty += self::patternPenalty(array_map(static fn (bool $cell): int => $cell ? 1 : 0, $matrix[$i]));
            $penalty += self::patternPenalty(array_map(static fn (bool $cell): int => $cell ? 1 : 0, array_column($matrix, $i)));
        }

        // Aturan 4: proporsi module gelap.
        $dark = 0;
        foreach ($matrix as $row) {
            foreach ($row as $cell) {
                $dark += $cell ? 1 : 0;
            }
        }

        $percent = ($dark * 100) / ($size * $size);
        $penalty += (int) (abs($percent - 50) / 5) * 10;

        return $penalty;
    }

    /**
     * @param  list<int>  $line
     */
    private static function runPenalty(array $line): int
    {
        $penalty = 0;
        $run = 1;

        for ($i = 1; $i < count($line); $i++) {
            if ($line[$i] === $line[$i - 1]) {
                $run++;
                continue;
            }

            if ($run >= 5) {
                $penalty += $run - 2;
            }

            $run = 1;
        }

        if ($run >= 5) {
            $penalty += $run - 2;
        }

        return $penalty;
    }

    /**
     * @param  list<int>  $line
     */
    private static function patternPenalty(array $line): int
    {
        $penalty = 0;
        $length = count($line);

        $patterns = [
            [1, 0, 1, 1, 1, 0, 1, 0, 0, 0, 0],
            [0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 1],
        ];

        for ($i = 0; $i + 10 < $length; $i++) {
            foreach ($patterns as $pattern) {
                $match = true;

                for ($j = 0; $j < 11; $j++) {
                    if ($line[$i + $j] !== $pattern[$j]) {
                        $match = false;
                        break;
                    }
                }

                if ($match) {
                    $penalty += 40;
                }
            }
        }

        return $penalty;
    }

    /**
     * @return list<int>
     */
    private static function toBits(int $value, int $length): array
    {
        $bits = [];
        for ($i = $length - 1; $i >= 0; $i--) {
            $bits[] = ($value >> $i) & 1;
        }

        return $bits;
    }
}
