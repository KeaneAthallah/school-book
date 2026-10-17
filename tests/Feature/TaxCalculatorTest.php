<?php

use App\Models\Setting;
use App\Services\TaxCalculator;

it('charges books only the book tax and no PPN', function () {
    $result = app(TaxCalculator::class)->calculate([
        ['type' => 'book', 'unit_price' => '100000', 'quantity' => 1],
    ]);

    expect($result['dpp'])->toBe('0.00')
        ->and($result['ppn'])->toBe('0.00')
        ->and($result['pph'])->toBe('500.00');
});

it('charges other goods both PPN and the goods tax, calculated from the DPP', function () {
    $result = app(TaxCalculator::class)->calculate([
        ['type' => 'goods', 'unit_price' => '111000', 'quantity' => 1],
    ]);

    expect($result['dpp'])->toBe('100000.00')
        ->and($result['ppn'])->toBe('12000.00')
        ->and($result['pph'])->toBe('2000.00');
});

it('sums book and goods lines into a single invoice tax', function () {
    $result = app(TaxCalculator::class)->calculate([
        ['type' => 'book', 'unit_price' => '50000', 'quantity' => 2],
        ['type' => 'goods', 'unit_price' => '222000', 'quantity' => 1],
    ]);

    expect($result['dpp'])->toBe('200000.00')
        ->and($result['ppn'])->toBe('24000.00')
        ->and($result['pph'])->toBe('4500.00');
});

it('reads the tax rates from the company settings', function () {
    Setting::updateOrCreate(['key' => 'finance.book_tax_rate'], ['value' => '1', 'group' => 'finance']);
    Setting::updateOrCreate(['key' => 'finance.goods_tax_rate'], ['value' => '3', 'group' => 'finance']);
    Setting::updateOrCreate(['key' => 'finance.ppn_rate'], ['value' => '11', 'group' => 'finance']);

    $calculator = app(TaxCalculator::class);

    expect($calculator->calculate([['type' => 'book', 'unit_price' => '100000', 'quantity' => 1]])['pph'])
        ->toBe('1000.00');

    $goods = $calculator->calculate([['type' => 'goods', 'unit_price' => '111000', 'quantity' => 1]]);

    expect($goods['ppn'])->toBe('11000.00')
        ->and($goods['pph'])->toBe('3000.00');
});

it('falls back to the default rates when no settings are stored', function () {
    expect(Setting::query()->where('key', 'like', 'finance.%tax%')->exists())->toBeFalse();

    $result = app(TaxCalculator::class)->calculate([
        ['type' => 'goods', 'unit_price' => '111000', 'quantity' => 1],
    ]);

    expect($result['ppn'])->toBe('12000.00')
        ->and($result['pph'])->toBe('2000.00');
});
