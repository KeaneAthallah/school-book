<?php

use App\Models\Book;
use App\Models\Goods;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RbacSeeder;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

function goodsAdmin(): User
{
    return User::query()->where('email', 'admin@sekolah.test')->firstOrFail();
}

function goodsRecord(array $attributes = []): Goods
{
    $product = Product::query()->create([
        'type' => 'goods',
        'code' => 'BRG-'.strtoupper(uniqid()),
        'title' => 'Kipas Angin',
        'price' => '75000.00',
        'cost_price' => '50000.00',
        'status' => 'active',
        ...$attributes,
    ]);

    return Goods::query()->create([
        'product_id' => $product->id,
        'unit' => 'pcs',
    ]);
}

it('lists barang lainnya for an administrator', function () {
    goodsRecord(['title' => 'Kipas Angin']);

    $this->actingAs(goodsAdmin())
        ->get('/master/barang-lainnya')
        ->assertOk()
        ->assertSee('Kipas Angin');
});

it('creates barang lainnya with the goods type', function () {
    $this->actingAs(goodsAdmin())
        ->post('/master/barang-lainnya', [
            'code' => 'BRG-001',
            'title' => 'Meja Lipat',
            'price' => '75000.00',
            'cost_price' => '50000.00',
            'rack_location' => 'RAK-A1',
            'unit' => 'pcs',
            'status' => 'active',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('products', [
        'code' => 'BRG-001',
        'title' => 'Meja Lipat',
        'type' => 'goods',
    ]);

    $this->assertDatabaseHas('goods', ['unit' => 'pcs']);
});

it('keeps barang lainnya out of the books listing', function () {
    goodsRecord(['title' => 'Kipas Angin']);

    $this->actingAs(goodsAdmin())
        ->get('/master/books')
        ->assertOk()
        ->assertDontSee('Kipas Angin');
});

it('edits barang lainnya only from its own screen', function () {
    $goods = goodsRecord();

    $this->actingAs(goodsAdmin())
        ->get('/master/barang-lainnya/'.$goods->id.'/edit')
        ->assertOk();

    $this->actingAs(goodsAdmin())
        ->get('/master/books/'.$goods->id.'/edit')
        ->assertNotFound();
});

it('updates a barang lainnya record', function () {
    $goods = goodsRecord(['title' => 'Nama Lama']);

    $this->actingAs(goodsAdmin())
        ->put('/master/barang-lainnya/'.$goods->id, [
            'code' => $goods->product->code,
            'title' => 'Nama Baru',
            'price' => '50000.00',
            'cost_price' => '30000.00',
            'unit' => 'pcs',
            'status' => 'active',
        ])
        ->assertRedirect();

    expect($goods->refresh()->product->title)->toBe('Nama Baru')
        ->and($goods->product->type)->toBe('goods');
});

it('deletes a barang lainnya record', function () {
    $goods = goodsRecord();

    $this->actingAs(goodsAdmin())
        ->delete('/master/barang-lainnya/'.$goods->id)
        ->assertRedirect();

    $this->assertDatabaseMissing('goods', ['id' => $goods->id]);
    $this->assertDatabaseMissing('products', ['id' => $goods->product_id]);
});

it('validates required fields when creating barang lainnya', function () {
    $this->actingAs(goodsAdmin())
        ->post('/master/barang-lainnya', ['title' => ''])
        ->assertSessionHasErrors(['code', 'title', 'price']);
});

it('lists books under their own ids even when product ids diverge', function () {
    // An unrelated product first, so the product id no longer matches the book id.
    Product::factory()->create(['type' => 'book']);

    $product = Product::factory()->create(['type' => 'book', 'title' => 'Buku Beda Id']);
    $book = Book::factory()->create(['product_id' => $product->id]);

    $this->actingAs(goodsAdmin())
        ->get('/master/books')
        ->assertOk()
        ->assertSee('Buku Beda Id')
        ->assertSee('/master/books/'.$book->id, false);
});
