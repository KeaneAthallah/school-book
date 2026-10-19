<?php

use App\Models\Attachment;
use App\Models\Book;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use App\Models\OrderSource;
use App\Models\Product;
use App\Models\Publisher;
use App\Models\School;
use App\Models\User;
use App\Services\GoodsReceiptService;
use App\Services\InvoiceService;
use App\Services\OrderService;
use App\Services\ShipmentService;
use Database\Seeders\RbacSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

function financeUser(): User
{
    return User::query()->where('email', 'keuangan@sekolah.test')->firstOrFail();
}

function paymentProofImage(string $name): UploadedFile
{
    $path = sys_get_temp_dir().'/'.$name;
    copy(__DIR__.'/../Fixtures/proof.png', $path);

    return new UploadedFile($path, $name, 'image/png', null, true);
}

function invoicedOrder(): Invoice
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

    $shipment = app(ShipmentService::class)->create($order, [
        'items' => [['order_item_id' => $order->items->first()->id, 'quantity' => 10]],
    ], $user);
    app(ShipmentService::class)->confirm($shipment, $user, paymentProofImage('surat-jalan.png'));

    return app(InvoiceService::class)->generateForOrder($order, $user);
}

it('stores a payment proof photo on the invoice', function () {
    Storage::fake('public');

    $invoice = invoicedOrder();

    $this->actingAs(financeUser())
        ->post('/invoices/'.$invoice->id.'/attachments', [
            'photo' => paymentProofImage('bukti-pembayaran.png'),
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('attachments', [
        'attachable_type' => Invoice::class,
        'attachable_id' => $invoice->id,
        'original_name' => 'bukti-pembayaran.png',
    ]);

    $attachment = Attachment::query()->where('attachable_type', Invoice::class)->firstOrFail();
    Storage::disk('public')->assertExists($attachment->path);
});

it('shows the payment proof photos on the invoice page', function () {
    Storage::fake('public');

    $invoice = invoicedOrder();

    $this->actingAs(financeUser())
        ->post('/invoices/'.$invoice->id.'/attachments', [
            'photo' => paymentProofImage('bukti-pembayaran.png'),
        ])
        ->assertRedirect();

    $this->actingAs(financeUser())
        ->get('/invoices/'.$invoice->id)
        ->assertOk()
        ->assertSee('Bukti Pembayaran')
        ->assertSee('bukti-pembayaran.png');
});

it('rejects a payment proof that is not an image', function () {
    Storage::fake('public');

    $invoice = invoicedOrder();

    $path = sys_get_temp_dir().'/bukti.txt';
    file_put_contents($path, 'bukan gambar');

    $this->actingAs(financeUser())
        ->post('/invoices/'.$invoice->id.'/attachments', [
            'photo' => new UploadedFile($path, 'bukti.txt', 'text/plain', null, true),
        ])
        ->assertSessionHasErrors('photo');

    expect(Attachment::query()->where('attachable_type', Invoice::class)->count())->toBe(0);
});

it('adds another payment proof photo to the same invoice', function () {
    Storage::fake('public');

    $invoice = invoicedOrder();

    $this->actingAs(financeUser())
        ->post('/invoices/'.$invoice->id.'/attachments', [
            'photo' => paymentProofImage('pertama.png'),
        ])
        ->assertRedirect();

    $this->actingAs(financeUser())
        ->post('/invoices/'.$invoice->id.'/attachments', [
            'photo' => paymentProofImage('kedua.png'),
        ])
        ->assertRedirect();

    expect(Attachment::query()->where('attachable_type', Invoice::class)->where('attachable_id', $invoice->id)->count())->toBe(2);
});

it('deletes a payment proof photo', function () {
    Storage::fake('public');

    $invoice = invoicedOrder();

    $this->actingAs(financeUser())
        ->post('/invoices/'.$invoice->id.'/attachments', [
            'photo' => paymentProofImage('bukti.png'),
        ])
        ->assertRedirect();

    $attachment = Attachment::query()->where('attachable_type', Invoice::class)->firstOrFail();

    $this->actingAs(financeUser())
        ->delete('/attachments/'.$attachment->id)
        ->assertRedirect();

    $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    Storage::disk('public')->assertMissing($attachment->path);
});

it('forbids a warehouse user from uploading payment proof', function () {
    Storage::fake('public');

    $invoice = invoicedOrder();

    $gudang = User::query()->where('email', 'gudang@sekolah.test')->firstOrFail();

    $this->actingAs($gudang)
        ->post('/invoices/'.$invoice->id.'/attachments', [
            'photo' => paymentProofImage('bukti.png'),
        ])
        ->assertForbidden();

    expect(Attachment::query()->where('attachable_type', Invoice::class)->count())->toBe(0);
});
