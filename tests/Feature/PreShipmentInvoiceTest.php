<?php

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Models\Book;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderSource;
use App\Models\Product;
use App\Models\Publisher;
use App\Models\School;
use App\Models\User;
use App\Services\GoodsReceiptService;
use App\Services\InvoiceService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\ShipmentService;
use Database\Seeders\RbacSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

function preShipmentAdmin(): User
{
    return User::query()->where('email', 'admin@sekolah.test')->firstOrFail();
}

/**
 * @return array{publisher: Publisher, product: Product, school: School, source: OrderSource, user: User, order: Order}
 */
function createPreShipmentOrder(int $quantity = 10, string $discountPercent = '0'): array
{
    $publisher = Publisher::factory()->create();
    $product = Product::factory()->create(['price' => '1000.00', 'cost_price' => '600.00']);
    Book::factory()->for($publisher)->create(['product_id' => $product->id]);
    $school = School::factory()->create();
    $source = OrderSource::factory()->create();
    $user = User::factory()->create();

    $order = app(OrderService::class)->create([
        'order_date' => now()->toDateString(),
        'school_id' => $school->id,
        'order_source_id' => $source->id,
        'discount_percent' => $discountPercent,
        'items' => [
            ['product_id' => $product->id, 'quantity' => $quantity, 'unit_price' => '1000.00'],
        ],
    ], $user);

    return compact('publisher', 'product', 'school', 'source', 'user', 'order');
}

function receiveIntoStock(Publisher $publisher, Product $product, int $quantity, User $user): void
{
    $receipt = GoodsReceipt::factory()->for($publisher)->create();
    $receipt->items()->create([
        'product_id' => $product->id,
        'quantity' => $quantity,
        'unit_cost' => '600.00',
    ]);

    app(GoodsReceiptService::class)->confirm($receipt, $user);
}

function settleInvoice(Invoice $invoice, School $school, User $user): void
{
    $payment = app(PaymentService::class)->record([
        'school_id' => $school->id,
        'payment_date' => now()->toDateString(),
        'amount' => $invoice->total_amount,
        'allocations' => [
            ['invoice_id' => $invoice->id, 'amount' => $invoice->total_amount],
        ],
    ], $user);

    app(PaymentService::class)->verify($payment, $user);
}

it('keeps a paid invoice on the unshipped list while order items still have remaining quantity', function () {
    ['publisher' => $publisher, 'product' => $product, 'school' => $school, 'user' => $user, 'order' => $order] = createPreShipmentOrder(100);

    app(OrderService::class)->confirm($order, $user);

    receiveIntoStock($publisher, $product, 60, $user);

    $order->refresh()->load('items');
    $item = $order->items->first();

    $shipment = app(ShipmentService::class)->create($order, [
        'items' => [['order_item_id' => $item->id, 'quantity' => 60]],
    ], $user);
    app(ShipmentService::class)->confirm($shipment, $user);

    $invoice = app(InvoiceService::class)->generateForShipment($shipment, $user);
    settleInvoice($invoice, $school, $user);

    expect(Invoice::query()->whereKey($invoice->id)->paidNotShipped()->exists())->toBeTrue();

    receiveIntoStock($publisher, $product, 40, $user);

    $order->refresh()->load('items');
    $item = $order->items->first();

    $secondShipment = app(ShipmentService::class)->create($order, [
        'items' => [['order_item_id' => $item->id, 'quantity' => 40]],
    ], $user);
    app(ShipmentService::class)->confirm($secondShipment, $user);

    expect(Invoice::query()->whereKey($invoice->id)->paidNotShipped()->exists())->toBeFalse();
});

it('issues a pre-shipment invoice from a processing order and lists it after settlement', function () {
    ['school' => $school, 'publisher' => $publisher, 'product' => $product, 'user' => $user, 'order' => $order] = createPreShipmentOrder(10, '10');

    app(OrderService::class)->confirm($order, $user);
    expect($order->refresh()->status)->toBe(OrderStatus::Processing);

    $admin = preShipmentAdmin();

    $this->actingAs($admin)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('Terbitkan Invoice');

    $response = $this->actingAs($admin)->post('/invoices', [
        'order_id' => $order->id,
        'mode' => 'pre_shipment',
    ]);

    $invoice = Invoice::query()->latest('id')->firstOrFail();

    $response->assertRedirect(route('invoices.show', $invoice));

    expect($invoice->shipment_id)->toBeNull()
        ->and($invoice->status)->toBe(InvoiceStatus::Issued)
        ->and($invoice->total_amount)->toBe($order->refresh()->total_amount)
        ->and((int) $invoice->items()->sum('quantity'))->toBe(10);

    settleInvoice($invoice, $school, $admin);

    $this->actingAs($admin)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertDontSee('Terbitkan Invoice');

    $hutang = $this->actingAs($admin)
        ->get('/finance/hutang')
        ->assertOk()
        ->assertSee($invoice->number)
        ->assertSee($school->name);

    expect($hutang->viewData('invoices')[0]['remaining_quantity'])->toBe(10);

    expect(Invoice::query()->whereKey($invoice->id)->paidNotShipped()->exists())->toBeTrue();

    receiveIntoStock($publisher, $product, 10, $user);

    $order->refresh()->load('items');
    $item = $order->items->first();

    $shipment = app(ShipmentService::class)->create($order, [
        'items' => [['order_item_id' => $item->id, 'quantity' => 10]],
    ], $user);
    app(ShipmentService::class)->confirm($shipment, $user);

    expect(Invoice::query()->whereKey($invoice->id)->paidNotShipped()->exists())->toBeFalse();
});

it('prevents a second invoice once the order has been fully invoiced before shipping', function () {
    ['publisher' => $publisher, 'product' => $product, 'user' => $user, 'order' => $order] = createPreShipmentOrder(10);

    app(OrderService::class)->confirm($order, $user);

    app(InvoiceService::class)->generateForOrderBeforeShipment($order, $user);

    receiveIntoStock($publisher, $product, 10, $user);

    $order->refresh()->load('items');
    $item = $order->items->first();

    $shipment = app(ShipmentService::class)->create($order, [
        'items' => [['order_item_id' => $item->id, 'quantity' => 10]],
    ], $user);
    app(ShipmentService::class)->confirm($shipment, $user);

    expect(fn () => app(InvoiceService::class)->generateForShipment($shipment, $user))
        ->toThrow(ValidationException::class);
});

it('blocks reverting to draft or cancelling an order that has an active invoice', function () {
    ['user' => $user, 'order' => $order] = createPreShipmentOrder(10);

    app(OrderService::class)->confirm($order, $user);
    $invoice = app(InvoiceService::class)->generateForOrderBeforeShipment($order, $user);

    expect(fn () => app(OrderService::class)->revertToDraft($order, $user))
        ->toThrow(ValidationException::class)
        ->and(fn () => app(OrderService::class)->cancel($order, $user))
        ->toThrow(ValidationException::class);

    app(InvoiceService::class)->cancel($invoice, $user);

    app(OrderService::class)->revertToDraft($order, $user);

    expect($order->refresh()->status)->toBe(OrderStatus::Draft);
});
