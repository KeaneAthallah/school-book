<?php

use App\Models\Order;
use App\Models\OrderSource;
use App\Models\Product;
use App\Models\School;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\RbacSeeder;

beforeEach(function () {
    $this->seed(RbacSeeder::class);

    Zona::query()->create(['code' => 'Z3', 'name' => 'Zona 3', 'status' => 'active']);
});

function orderFormAdmin(): User
{
    return User::query()->where('email', 'admin@sekolah.test')->firstOrFail();
}

it('renders the order form with the live calculation summary', function () {
    $response = $this->actingAs(orderFormAdmin())->get('/orders/create');

    $response->assertOk();
    // The item card and the summary card share one Alpine scope, so the
    // per-line and order-level totals can be computed client-side.
    $response->assertSee('lineItems', false);
    $response->assertSee('money(lineTotal(item))', false);
    $response->assertSee('money(subtotal())', false);
    $response->assertSee('money(grandTotal())', false);
});

it('carries existing items and the school discount into the form when editing', function () {
    $school = School::factory()->create(['rebate_percent' => '5.00']);
    $source = OrderSource::factory()->create();

    $this->actingAs(orderFormAdmin())
        ->post('/orders', [
            'order_date' => now()->toDateString(),
            'school_id' => $school->getKey(),
            'order_source_id' => $source->getKey(),
            'discount_percent' => '5',
            'items' => [
                ['product_id' => Product::factory()->create()->getKey(), 'quantity' => 2, 'unit_price' => '10000'],
            ],
        ])
        ->assertRedirect();

    $order = Order::query()->latest('id')->firstOrFail();

    $this->actingAs(orderFormAdmin())
        ->get('/orders/'.$order->getKey().'/edit')
        ->assertOk()
        ->assertSee('discountPercent', false)
        ->assertSee('10000.00', false);
});
