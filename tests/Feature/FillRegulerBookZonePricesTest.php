<?php

use App\Models\Book;
use App\Models\Product;
use App\Models\Zona;

beforeEach(function () {
    foreach ([['Z3', 'Zona 3'], ['Z4', 'Zona 4'], ['Z5', 'Zona 5']] as [$code, $name]) {
        Zona::factory()->create(['code' => $code, 'name' => $name, 'status' => 'active']);
    }
});

function regulerZoneBook(string $classification = 'Reguler', string $price = '90000'): Book
{
    return Book::factory()->create([
        'classification' => $classification,
        'product_id' => Product::factory()->create(['price' => $price])->id,
    ]);
}

/**
 * @return array<string, string>
 */
function regulerZonePrices(Book $book): array
{
    return $book->refresh()->product->prices()->with('zona')->get()
        ->mapWithKeys(fn ($price): array => [(string) $price->zona?->code => (string) $price->price])
        ->all();
}

function regulerZoneId(string $code): int
{
    return (int) Zona::query()->where('code', $code)->value('id');
}

it('sets all three zone prices to the existing book price', function () {
    $book = regulerZoneBook();

    $this->artisan('books:fill-reguler-zone-prices')->assertSuccessful();

    expect(regulerZonePrices($book))->toBe([
        'Z3' => '90000.00',
        'Z4' => '90000.00',
        'Z5' => '90000.00',
    ]);
});

it('marks every book as reguler regardless of its previous classification', function () {
    $book = regulerZoneBook(classification: 'Het');

    $this->artisan('books:fill-reguler-zone-prices')->assertSuccessful();

    expect($book->refresh()->classification)->toBe('Reguler');

    expect(regulerZonePrices($book))->toBe([
        'Z3' => '90000.00',
        'Z4' => '90000.00',
        'Z5' => '90000.00',
    ]);
});

it('uses the existing zona 3 price as the common value and evens out the others', function () {
    $book = regulerZoneBook();
    $book->product->prices()->create(['zona_id' => regulerZoneId('Z3'), 'price' => '88000']);
    $book->product->prices()->create(['zona_id' => regulerZoneId('Z4'), 'price' => '95000']);

    $this->artisan('books:fill-reguler-zone-prices')->assertSuccessful();

    expect(regulerZonePrices($book))->toBe([
        'Z3' => '88000.00',
        'Z4' => '88000.00',
        'Z5' => '88000.00',
    ]);
});

it('leaves zone prices untouched when they already carry the same value', function () {
    $book = regulerZoneBook();

    foreach (['Z3', 'Z4', 'Z5'] as $code) {
        $book->product->prices()->create(['zona_id' => regulerZoneId($code), 'price' => '90000']);
    }

    $this->artisan('books:fill-reguler-zone-prices')
        ->expectsOutputToContain('0')
        ->assertSuccessful();

    expect(regulerZonePrices($book))->toBe([
        'Z3' => '90000.00',
        'Z4' => '90000.00',
        'Z5' => '90000.00',
    ]);
});

it('skips books that have no usable price and reports them', function () {
    $book = regulerZoneBook(price: '0');

    $this->artisan('books:fill-reguler-zone-prices')
        ->expectsOutputToContain('fill them manually')
        ->assertSuccessful();

    expect(regulerZonePrices($book))->toBe([]);
});

it('writes nothing on a dry run', function () {
    $book = regulerZoneBook(classification: 'Het');

    $this->artisan('books:fill-reguler-zone-prices', ['--dry-run' => true])->assertSuccessful();

    expect($book->refresh()->classification)->toBe('Het');
    expect(regulerZonePrices($book))->toBe([]);
});

it('fails when the zone master data is missing', function () {
    Zona::query()->delete();

    $this->artisan('books:fill-reguler-zone-prices')->assertFailed();
});
