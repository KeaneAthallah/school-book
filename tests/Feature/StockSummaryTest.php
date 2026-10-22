<?php

use App\Models\GoodsReceipt;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Publisher;
use App\Models\Shipment;
use App\Models\StockMutation;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

function summaryAdmin(): User
{
    return User::query()->where('email', 'admin@sekolah.test')->firstOrFail();
}

it('renders the stock summary page', function () {
    $this->actingAs(summaryAdmin())
        ->get(route('stock-summary.index'))
        ->assertOk()
        ->assertSee('Rangkuman Stok');
});

it('computes in, out and remaining stock per product', function () {
    $product = Product::factory()->create(['code' => 'SUM-001', 'cost_price' => '100.00']);

    // 40 in via a confirmed receipt.
    $publisher = Publisher::factory()->create();
    $receipt = GoodsReceipt::factory()->for($publisher)->create(['status' => 'confirmed']);
    $receipt->items()->create(['product_id' => $product->id, 'quantity' => 40, 'unit_cost' => '100.00']);

    // 12 out via a delivered shipment.
    $orderItem = OrderItem::factory()->for($product, 'product')->create();
    $shipment = Shipment::factory()->create(['status' => 'delivered']);
    $shipment->items()->create(['order_item_id' => $orderItem->id, 'product_id' => $product->id, 'quantity' => 12]);

    // 3 lost via a stock mutation.
    StockMutation::query()->create([
        'product_id' => $product->id,
        'type' => 'lost',
        'quantity' => 3,
    ]);

    $html = $this->actingAs(summaryAdmin())
        ->get(route('stock-summary.index', ['search' => 'SUM-001']))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('SUM-001');

    // 40 - 12 - 3 = 25 remaining.
    $row = $this->actingAs(summaryAdmin())
        ->get(route('stock-summary.index', ['search' => 'SUM-001']))
        ->viewData('rows')
        ->getCollection()
        ->first();

    expect($row['masuk'])->toBe(40)
        ->and($row['keluar'])->toBe(12)
        ->and($row['mutasi'])->toBe(3)
        ->and($row['sisa'])->toBe(25)
        ->and($row['nilai_sisa'])->toBe('2500.00');
});

it('ignores draft receipts when computing inbound stock', function () {
    $product = Product::factory()->create(['code' => 'SUM-002']);
    $publisher = Publisher::factory()->create();
    $receipt = GoodsReceipt::factory()->for($publisher)->create(['status' => 'draft']);
    $receipt->items()->create(['product_id' => $product->id, 'quantity' => 99, 'unit_cost' => '100.00']);

    $rows = $this->actingAs(summaryAdmin())
        ->get(route('stock-summary.index', ['search' => 'SUM-002']))
        ->viewData('rows')
        ->getCollection();

    expect($rows)->toBeEmpty();
});

it('filters the summary by remaining stock', function () {
    $available = Product::factory()->create(['code' => 'SUM-003']);
    $publisher = Publisher::factory()->create();
    $receipt = GoodsReceipt::factory()->for($publisher)->create(['status' => 'confirmed']);
    $receipt->items()->create(['product_id' => $available->id, 'quantity' => 5, 'unit_cost' => '100.00']);

    $rows = $this->actingAs(summaryAdmin())
        ->get(route('stock-summary.index', ['filter' => 'tersedia']))
        ->viewData('rows')
        ->getCollection();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['code'])->toBe('SUM-003');
});
