<?php

use App\Models\Attachment;
use App\Models\Book;
use App\Models\GoodsReceipt;
use App\Models\OrderSource;
use App\Models\Product;
use App\Models\Publisher;
use App\Models\School;
use App\Models\Shipment;
use App\Models\User;
use App\Services\GoodsReceiptService;
use App\Services\OrderService;
use App\Services\ShipmentService;
use Database\Seeders\RbacSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

function shippingAdmin(): User
{
    return User::query()->where('email', 'admin@sekolah.test')->firstOrFail();
}

function proofImage(string $name): UploadedFile
{
    $path = sys_get_temp_dir().'/'.$name;
    copy(__DIR__.'/../Fixtures/proof.png', $path);

    return new UploadedFile($path, $name, 'image/png', null, true);
}

function draftShipment(): Shipment
{
    $publisher = Publisher::factory()->create();
    $product = Product::factory()->create(['price' => '1000.00', 'cost_price' => '600.00']);
    $book = Book::factory()->for($publisher)->create(['product_id' => $product->id]);
    $school = School::factory()->create();
    $source = OrderSource::factory()->create();
    $user = User::factory()->create();

    $order = app(OrderService::class)->create([
        'order_date' => now()->toDateString(),
        'school_id' => $school->id,
        'order_source_id' => $source->id,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 10, 'unit_price' => '1000.00'],
        ],
    ], $user);
    app(OrderService::class)->confirm($order, $user);

    $receipt = GoodsReceipt::factory()->for($publisher)->create();
    $receipt->items()->create(['product_id' => $product->id, 'quantity' => 60, 'unit_cost' => '600.00']);
    app(GoodsReceiptService::class)->confirm($receipt, $user);

    $order->refresh()->load('items');

    return app(ShipmentService::class)->create($order, [
        'items' => [['order_item_id' => $order->items->first()->id, 'quantity' => 10]],
    ], $user);
}

it('requires a proof photo when confirming a shipment', function () {
    $shipment = draftShipment();

    $this->actingAs(shippingAdmin())
        ->post('/shipments/'.$shipment->id.'/confirm')
        ->assertSessionHasErrors('proof_photo');

    expect($shipment->refresh()->status->value)->toBe('draft');
});

it('stores the proof photo when confirming a shipment', function () {
    Storage::fake('public');

    $shipment = draftShipment();

    $this->actingAs(shippingAdmin())
        ->post('/shipments/'.$shipment->id.'/confirm', [
            'proof_photo' => proofImage('bukti-pengiriman.png'),
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('attachments', [
        'attachable_type' => Shipment::class,
        'attachable_id' => $shipment->id,
        'original_name' => 'bukti-pengiriman.png',
    ]);

    $attachment = Attachment::query()->firstOrFail();
    Storage::disk('public')->assertExists($attachment->path);

    expect($shipment->refresh()->status->value)->toBe('shipped');
});

it('shows the proof photos on the shipment page', function () {
    Storage::fake('public');

    $shipment = draftShipment();

    $this->actingAs(shippingAdmin())
        ->post('/shipments/'.$shipment->id.'/confirm', [
            'proof_photo' => proofImage('bukti-pengiriman.png'),
        ])
        ->assertRedirect();

    $this->actingAs(shippingAdmin())
        ->get('/shipments/'.$shipment->id)
        ->assertOk()
        ->assertSee('Bukti Pengiriman')
        ->assertSee('bukti-pengiriman.png')
        ->assertSee('Pesanan telah dikirim berikut bukti foto.');
});

it('adds another proof photo after the shipment is confirmed', function () {
    Storage::fake('public');

    $shipment = draftShipment();

    $this->actingAs(shippingAdmin())
        ->post('/shipments/'.$shipment->id.'/confirm', [
            'proof_photo' => proofImage('pertama.png'),
        ])
        ->assertRedirect();

    $this->actingAs(shippingAdmin())
        ->post('/shipments/'.$shipment->id.'/attachments', [
            'photo' => proofImage('kedua.png'),
        ])
        ->assertRedirect();

    expect(Attachment::query()->where('attachable_id', $shipment->id)->count())->toBe(2);
});

it('deletes a proof photo', function () {
    Storage::fake('public');

    $shipment = draftShipment();

    $this->actingAs(shippingAdmin())
        ->post('/shipments/'.$shipment->id.'/confirm', [
            'proof_photo' => proofImage('bukti.png'),
        ])
        ->assertRedirect();

    $attachment = Attachment::query()->firstOrFail();

    $this->actingAs(shippingAdmin())
        ->delete('/attachments/'.$attachment->id)
        ->assertRedirect();

    $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    Storage::disk('public')->assertMissing($attachment->path);
});
