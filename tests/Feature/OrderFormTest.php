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
    $response->assertSee('money(grossSubtotal())', false);
    $response->assertSee('money(itemDiscount())', false);
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

it('keeps the item discount percent when editing an order', function () {
    $admin = orderFormAdmin();
    $product = Product::factory()->create(['price' => '100000.00']);
    $school = School::factory()->create();
    $source = OrderSource::factory()->create();

    $this->actingAs($admin)->post('/orders', [
        'order_date' => now()->toDateString(),
        'school_id' => $school->getKey(),
        'order_source_id' => $source->getKey(),
        'discount_percent' => '0',
        'items' => [[
            'product_id' => $product->getKey(),
            'quantity' => 10,
            'unit_price' => '100000.00',
            'discount_percent' => '10',
            'availability_status' => 'available',
        ]],
    ])->assertRedirect();

    $order = Order::query()->latest('id')->firstOrFail();

    // The stored discount is applied to the line total.
    $item = $order->items()->firstOrFail();
    expect($item->discount_percent)->toBe('10.00')
        ->and($item->discount_amount)->toBe('100000.00')
        ->and($item->subtotal)->toBe('900000.00');

    // The edit form must carry the percent so it is not lost on the next save.
    $this->actingAs($admin)
        ->get('/orders/'.$order->getKey().'/edit')
        ->assertOk()
        ->assertSee('items[${index}][discount_percent]', false);

    $this->actingAs($admin)->put('/orders/'.$order->getKey(), [
        'order_date' => $order->order_date->toDateString(),
        'school_id' => $school->getKey(),
        'order_source_id' => $source->getKey(),
        'discount_percent' => '0',
        'items' => [[
            'product_id' => $product->getKey(),
            'quantity' => 10,
            'unit_price' => '100000.00',
            'discount_percent' => '10',
            'availability_status' => 'available',
        ]],
    ])->assertRedirect();

    $item = $order->items()->firstOrFail();
    expect($item->discount_percent)->toBe('10.00')
        ->and($item->discount_amount)->toBe('100000.00')
        ->and($item->subtotal)->toBe('900000.00');
});

it('stores the selected zone on the order and restores it when editing', function () {
    $admin = orderFormAdmin();
    $product = Product::factory()->create(['price' => '100000.00']);
    $school = School::factory()->create();
    $source = OrderSource::factory()->create();

    $zona5 = Zona::query()->create(['code' => 'Z5', 'name' => 'Zona 5', 'status' => 'active']);
    $product->prices()->create(['zona_id' => $zona5->getKey(), 'price' => '120000.00']);

    $this->actingAs($admin)->post('/orders', [
        'order_date' => now()->toDateString(),
        'school_id' => $school->getKey(),
        'order_source_id' => $source->getKey(),
        'zona_id' => $zona5->getKey(),
        'discount_percent' => '0',
        'items' => [[
            'product_id' => $product->getKey(),
            'quantity' => 2,
            'unit_price' => '',
            'discount_percent' => '0',
            'availability_status' => 'available',
        ]],
    ])->assertRedirect();

    $order = Order::query()->latest('id')->firstOrFail();

    expect($order->zona_id)->toBe($zona5->getKey());

    // Blank price falls back to the zone price of the chosen zone.
    expect($order->items()->firstOrFail()->unit_price)->toBe('120000.00');

    $this->actingAs($admin)
        ->get('/orders/'.$order->getKey().'/edit')
        ->assertOk()
        ->assertSee('name="zona_id"', false)
        ->assertSee('Zona 5');
});

it('rejects an unknown zone', function () {
    $admin = orderFormAdmin();
    $product = Product::factory()->create(['price' => '100000.00']);
    $school = School::factory()->create();
    $source = OrderSource::factory()->create();

    $this->actingAs($admin)->post('/orders', [
        'order_date' => now()->toDateString(),
        'school_id' => $school->getKey(),
        'order_source_id' => $source->getKey(),
        'zona_id' => 9999,
        'discount_percent' => '0',
        'items' => [[
            'product_id' => $product->getKey(),
            'quantity' => 1,
            'unit_price' => '100000.00',
            'discount_percent' => '0',
            'availability_status' => 'available',
        ]],
    ])->assertSessionHasErrors('zona_id');
});

it('shows the item discount on the order detail page', function () {
    $admin = orderFormAdmin();
    $product = Product::factory()->create(['price' => '100000.00']);
    $school = School::factory()->create();
    $source = OrderSource::factory()->create();

    $this->actingAs($admin)->post('/orders', [
        'order_date' => now()->toDateString(),
        'school_id' => $school->getKey(),
        'order_source_id' => $source->getKey(),
        'discount_percent' => '0',
        'items' => [[
            'product_id' => $product->getKey(),
            'quantity' => 10,
            'unit_price' => '100000.00',
            'discount_percent' => '10',
            'availability_status' => 'available',
        ]],
    ])->assertRedirect();

    $order = Order::query()->latest('id')->firstOrFail();

    $this->actingAs($admin)
        ->get('/orders/'.$order->getKey())
        ->assertOk()
        ->assertSee('Diskon Item')
        ->assertSee('10%');
});

it('breaks the order total into gross subtotal, item discount and order discount', function () {
    $admin = orderFormAdmin();
    $product = Product::factory()->create(['price' => '100000.00']);
    $school = School::factory()->create();
    $source = OrderSource::factory()->create();

    $this->actingAs($admin)->post('/orders', [
        'order_date' => now()->toDateString(),
        'school_id' => $school->getKey(),
        'order_source_id' => $source->getKey(),
        // 5% of the net subtotal, matching how the service calculates it.
        'discount_percent' => '5',
        'items' => [[
            'product_id' => $product->getKey(),
            'quantity' => 10,
            'unit_price' => '100000.00',
            'discount_percent' => '10',
            'availability_status' => 'available',
        ]],
    ])->assertRedirect();

    $order = Order::query()->latest('id')->firstOrFail();

    $gross = 100000.00 * 10;          // 1,000,000
    $itemDiscount = $gross * 0.10;    // 100,000
    $net = $gross - $itemDiscount;    // 900,000
    $orderDiscount = $net * 0.05;     // 45,000

    expect($order->subtotal)->toBe(number_format($net, 2, '.', ''))
        ->and($order->discount_amount)->toBe(number_format($orderDiscount, 2, '.', ''));

    $view = $this->actingAs($admin)->get('/orders/'.$order->getKey());
    $view->assertOk();

    // The breakdown must add up to the stored total.
    expect((float) $order->total_amount)
        ->toBe($net - $orderDiscount + (float) $order->tax_amount);
});
