<?php

use App\Enums\BackorderStatus;
use App\Enums\InvoicePaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\RebateStatus;
use App\Models\Backorder;
use App\Models\Book;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderSource;
use App\Models\Product;
use App\Models\Publisher;
use App\Models\Rebate;
use App\Models\RebateRule;
use App\Models\School;
use App\Models\Shipment;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\GoodsReceiptService;
use App\Services\InvoiceService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\RebateService;
use App\Services\ShipmentService;

it('runs the full order lifecycle with partial stock, back orders, invoicing, payments, and rebates', function () {
    $warehouse = Warehouse::factory()->create(['code' => 'GDG-TEST', 'status' => 'active']);
    $publisher = Publisher::factory()->create();
    $product = Product::factory()->create(['price' => '1000.00', 'cost_price' => '600.00']);
    $book = Book::factory()->for($publisher)->create(['product_id' => $product->id]);
    $school = School::factory()->create();
    $source = OrderSource::factory()->create();
    $user = User::factory()->create();

    $inventory = app(GoodsReceiptService::class);

    // Only 60 of the 100 ordered books are in stock.
    $receipt = GoodsReceipt::factory()->for($publisher)->for($warehouse)->create();
    $receipt->items()->create(['product_id' => $product->id, 'quantity' => 60, 'unit_cost' => '600.00']);
    $inventory->confirm($receipt, $user);

    $order = app(OrderService::class)->create([
        'order_date' => now()->toDateString(),
        'school_id' => $school->id,
        'order_source_id' => $source->id,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 100, 'unit_price' => '1000.00'],
        ],
    ], $user);

    app(OrderService::class)->confirm($order, $user);

    $order->refresh()->load('items');
    $item = $order->items->first();

    expect($item->reserved_quantity)->toBe(60)
        ->and($item->backorder_quantity)->toBe(40)
        ->and($order->status)->toBe(OrderStatus::PartialReady);

    $backorder = Backorder::query()->where('order_item_id', $item->id)->firstOrFail();
    expect($backorder->quantity)->toBe(40)
        ->and($backorder->status)->toBe(BackorderStatus::Open);

    // Warehouse ships the 60 available books.
    $shipment = app(ShipmentService::class)->create($order, [
        'items' => [['order_item_id' => $item->id, 'quantity' => 60]],
    ], $user);
    app(ShipmentService::class)->confirm($shipment, $user);

    $order->refresh()->load('items');
    $item = $order->items->first();

    expect($item->shipped_quantity)->toBe(60)
        ->and($item->reserved_quantity)->toBe(0)
        ->and($order->status)->toBe(OrderStatus::PartialShipped);

    $stock = Stock::query()->where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->firstOrFail();
    expect($stock->quantity)->toBe(0)->and($stock->reserved_quantity)->toBe(0);

    // Invoice the shipped quantity only.
    $invoice = app(InvoiceService::class)->generateForShipment($shipment, $user);
    expect($invoice->total_amount)->toBe('60300.00')
        ->and($invoice->items()->sum('quantity'))->toBe(60);

    // Partial payment keeps the invoice partially paid.
    $payment = app(PaymentService::class)->record([
        'school_id' => $school->id,
        'payment_date' => now()->toDateString(),
        'amount' => '30000.00',
        'allocations' => [['invoice_id' => $invoice->id, 'amount' => '30000.00']],
    ], $user);
    app(PaymentService::class)->verify($payment, $user);

    $invoice->refresh();
    expect($invoice->paid_amount)->toBe('30000.00')
        ->and($invoice->paymentStatus())->toBe(InvoicePaymentStatus::Partial);

    // The remaining 40 books arrive and the back order becomes shippable.
    $secondReceipt = GoodsReceipt::factory()->for($publisher)->for($warehouse)->create();
    $secondReceipt->items()->create(['product_id' => $product->id, 'quantity' => 40, 'unit_cost' => '600.00']);
    $inventory->confirm($secondReceipt, $user);

    $order->refresh()->load('items');
    $item = $order->items->first();
    $backorder->refresh();

    expect($item->reserved_quantity)->toBe(40)
        ->and($item->backorder_quantity)->toBe(0)
        ->and($backorder->status)->toBe(BackorderStatus::Fulfilled);

    // Ship the remaining 40 books.
    $secondShipment = app(ShipmentService::class)->create($order, [
        'items' => [['order_item_id' => $item->id, 'quantity' => 40]],
    ], $user);
    app(ShipmentService::class)->confirm($secondShipment, $user);

    $order->refresh()->load('items');
    expect($order->items->first()->shipped_quantity)->toBe(100)
        ->and($order->status)->toBe(OrderStatus::FullyShipped);

    // Invoice the second shipment and settle both invoices.
    $secondInvoice = app(InvoiceService::class)->generateForShipment($secondShipment, $user);
    expect($secondInvoice->total_amount)->toBe('40200.00');

    $settlementOne = app(PaymentService::class)->record([
        'school_id' => $school->id,
        'payment_date' => now()->toDateString(),
        'amount' => '30300.00',
        'allocations' => [['invoice_id' => $invoice->id, 'amount' => '30300.00']],
    ], $user);
    app(PaymentService::class)->verify($settlementOne, $user);

    $settlementTwo = app(PaymentService::class)->record([
        'school_id' => $school->id,
        'payment_date' => now()->toDateString(),
        'amount' => '40200.00',
        'allocations' => [['invoice_id' => $secondInvoice->id, 'amount' => '40200.00']],
    ], $user);
    app(PaymentService::class)->verify($settlementTwo, $user);

    expect($invoice->refresh()->paymentStatus())->toBe(InvoicePaymentStatus::Paid)
        ->and($secondInvoice->refresh()->paymentStatus())->toBe(InvoicePaymentStatus::Paid);

    // Rebate eligibility requires a fully paid invoice (2% of the first invoice).
    $rule = RebateRule::factory()->create(['type' => 'percentage', 'value' => '2.00', 'requires_full_payment' => true]);
    $rebate = app(RebateService::class)->request([
        'invoice_id' => $invoice->id,
        'rebate_rule_id' => $rule->id,
    ], $user);

    expect($rebate->amount)->toBe('1206.00')
        ->and($rebate->status)->toBe(RebateStatus::Submitted);

    app(RebateService::class)->approve($rebate, $user);
    app(RebateService::class)->markPaid($rebate->refresh(), 'TRF-REBATE-1', $user);

    expect($rebate->refresh()->status)->toBe(RebateStatus::Paid);

    // Final reconciliation.
    expect((float) Invoice::query()->sum('total_amount'))->toBe(100500.0)
        ->and((float) Invoice::query()->sum('paid_amount'))->toBe(100500.0)
        ->and(Shipment::query()->count())->toBe(2)
        ->and(OrderItem::query()->sum('shipped_quantity'))->toBe(100);
});
