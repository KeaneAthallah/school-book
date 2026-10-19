<?php

use App\Models\Product;
use App\Models\ProductCatalog;
use App\Models\User;
use Database\Seeders\RbacSeeder;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

function catalogPdfAdmin(): User
{
    return User::query()->where('email', 'admin@sekolah.test')->firstOrFail();
}

it('streams the catalog pdf as a printable document', function () {
    $product = Product::factory()->create([
        'title' => 'Buku Contoh Premium',
        'price' => '45000.00',
    ]);

    $catalog = ProductCatalog::query()->create([
        'name' => 'Katalog Uji Coba',
        'description' => 'Deskripsi katalog uji coba.',
    ]);
    $catalog->items()->create(['product_id' => $product->getKey(), 'sort_order' => 0]);

    $response = $this->actingAs(catalogPdfAdmin())
        ->get('/catalogs/'.$catalog->getKey().'/pdf');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
    expect(substr((string) $response->getContent(), 0, 5))->toBe('%PDF-');
});

it('forbids the pdf download without the catalog permission', function () {
    $catalog = ProductCatalog::query()->create(['name' => 'Katalog Terbatas']);

    $this->actingAs(User::query()->where('email', 'gudang@sekolah.test')->firstOrFail())
        ->get('/catalogs/'.$catalog->getKey().'/pdf')
        ->assertForbidden();
});
