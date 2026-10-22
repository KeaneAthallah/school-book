<?php

use App\Support\NumberToWords;

it('writes rupiah amounts in words', function () {
    expect(NumberToWords::rupiah(100000))->toBe('Seratus Ribu Rupiah')
        ->and(NumberToWords::rupiah(0))->toBe('Nol Rupiah')
        ->and(NumberToWords::rupiah(1))->toBe('Satu Rupiah')
        ->and(NumberToWords::rupiah(21))->toBe('Dua Puluh Satu Rupiah')
        ->and(NumberToWords::rupiah(100))->toBe('Seratus Rupiah')
        ->and(NumberToWords::rupiah(296000))->toBe('Dua Ratus Sembilan Puluh Enam Ribu Rupiah')
        ->and(NumberToWords::rupiah(1250000))->toBe('Satu Juta Dua Ratus Lima Puluh Ribu Rupiah')
        ->and(NumberToWords::rupiah(2000000000))->toBe('Dua Miliar Rupiah');
});
