<?php

use App\Models\Book;
use App\Models\Publisher;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\RbacSeeder;

beforeEach(function () {
    $this->seed(RbacSeeder::class);

    foreach ([['Z3', 'Zona 3'], ['Z4', 'Zona 4'], ['Z5', 'Zona 5']] as [$code, $name]) {
        Zona::factory()->create(['code' => $code, 'name' => $name, 'status' => 'active']);
    }
});

function bookZoneAdmin(): User
{
    return User::query()->where('email', 'admin@sekolah.test')->firstOrFail();
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function bookZonePayload(array $overrides = []): array
{
    return [
        'code' => 'BK-ZONA-1',
        'title' => 'Buku Uji Zona',
        'publisher_id' => Publisher::factory()->create()->id,
        'status' => 'active',
        'price_zona_3' => '100000',
        'price_zona_4' => '110000',
        'price_zona_5' => '120000',
        ...$overrides,
    ];
}

function bookZonePrices(Book $book): array
{
    return $book->refresh()->product->prices()->with('zona')->get()
        ->mapWithKeys(fn ($price): array => [(string) $price->zona?->code => (string) $price->price])
        ->all();
}

it('stores the three zone prices and mirrors zona 3 into the product price', function () {
    $this->actingAs(bookZoneAdmin())
        ->post('/master/books', bookZonePayload())
        ->assertRedirect();

    $book = Book::query()
        ->whereHas('product', fn ($query) => $query->where('code', 'BK-ZONA-1'))
        ->firstOrFail();

    // The catalog listing and the order fallback still read products.price.
    expect((string) $book->product->price)->toBe('100000.00');

    expect(bookZonePrices($book))->toBe([
        'Z3' => '100000.00',
        'Z4' => '110000.00',
        'Z5' => '120000.00',
    ]);
});

it('updates zone prices and removes the ones that are cleared', function () {
    $this->actingAs(bookZoneAdmin())
        ->post('/master/books', bookZonePayload())
        ->assertRedirect();

    $book = Book::query()
        ->whereHas('product', fn ($query) => $query->where('code', 'BK-ZONA-1'))
        ->firstOrFail();

    $this->actingAs(bookZoneAdmin())
        ->put('/master/books/'.$book->id, bookZonePayload([
            'price_zona_3' => '105000',
            'price_zona_4' => null,
            'price_zona_5' => '125000',
        ]))
        ->assertRedirect();

    expect(bookZonePrices($book))->toBe([
        'Z3' => '105000.00',
        'Z5' => '125000.00',
    ]);

    expect((string) $book->refresh()->product->price)->toBe('105000.00');
});

it('requires at least one zone price', function () {
    $this->actingAs(bookZoneAdmin())
        ->post('/master/books', bookZonePayload([
            'price_zona_3' => null,
            'price_zona_4' => null,
            'price_zona_5' => null,
        ]))
        ->assertSessionHasErrors(['price_zona_3', 'price_zona_4', 'price_zona_5']);
});

it('no longer asks for a zone on the school form', function () {
    $this->actingAs(bookZoneAdmin())
        ->get('/master/schools/create')
        ->assertOk()
        ->assertDontSee('name="zona_id"', false);
});

it('ships zone prices to the order form so the zone selector works', function () {
    $this->actingAs(bookZoneAdmin())
        ->post('/master/books', bookZonePayload())
        ->assertRedirect();

    $this->actingAs(bookZoneAdmin())
        ->get('/orders/create')
        ->assertOk()
        ->assertSee('zona_prices', false);
});
