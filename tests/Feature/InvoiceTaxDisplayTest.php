<?php

use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RbacSeeder;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

function invoiceDisplayAdmin(): User
{
    return User::query()->where('email', 'admin@sekolah.test')->firstOrFail();
}

/**
 * Build an invoice with a single line of the given product type.
 *
 * @param  array<string, string>  $totals
 */
function invoiceWithProduct(string $productType, array $totals): Invoice
{
    $price = $totals['subtotal'];
    $product = Product::factory()->create(['type' => $productType, 'price' => $price]);

    $invoice = Invoice::factory()->create($totals);

    $invoice->items()->create([
        'product_id' => $product->getKey(),
        'description' => $product->title,
        'quantity' => 1,
        'unit_price' => $price,
        'discount_amount' => '0.00',
        'subtotal' => $price,
    ]);

    return $invoice;
}

it('prints the VAT exemption note and no PPN row for a book-only invoice', function () {
    $invoice = invoiceWithProduct('book', [
        'subtotal' => '100000.00',
        'dpp_amount' => '0.00',
        'ppn_amount' => '0.00',
        'pph_amount' => '500.00',
        'tax_amount' => '500.00',
        'total_amount' => '100500.00',
    ]);

    $this->actingAs(invoiceDisplayAdmin())
        ->get('/invoices/'.$invoice->id.'/print')
        ->assertOk()
        ->assertSee('dibebaskan dari pengenaan Pajak Pertambahan Nilai')
        ->assertDontSee('Barang selain buku pelajaran')
        ->assertDontSee('Pajak (DPP)')
        ->assertDontSee('PPN');
});

it('prints a PPN row and the goods note for an invoice containing goods', function () {
    $invoice = invoiceWithProduct('goods', [
        'subtotal' => '111000.00',
        'dpp_amount' => '100000.00',
        'ppn_amount' => '12000.00',
        'pph_amount' => '2000.00',
        'tax_amount' => '14000.00',
        'total_amount' => '125000.00',
    ]);

    $this->actingAs(invoiceDisplayAdmin())
        ->get('/invoices/'.$invoice->id.'/print')
        ->assertOk()
        ->assertSee('PPN')
        ->assertSee('Barang selain buku pelajaran dikenai')
        ->assertDontSee('dibebaskan dari pengenaan Pajak Pertambahan Nilai');
});

it('shows the PPN row on the invoice page only when PPN applies', function () {
    $book = invoiceWithProduct('book', [
        'subtotal' => '100000.00',
        'dpp_amount' => '0.00',
        'ppn_amount' => '0.00',
        'pph_amount' => '500.00',
        'tax_amount' => '500.00',
        'total_amount' => '100500.00',
    ]);

    $goods = invoiceWithProduct('goods', [
        'subtotal' => '111000.00',
        'dpp_amount' => '100000.00',
        'ppn_amount' => '12000.00',
        'pph_amount' => '2000.00',
        'tax_amount' => '14000.00',
        'total_amount' => '125000.00',
    ]);

    $admin = invoiceDisplayAdmin();

    $this->actingAs($admin)
        ->get('/invoices/'.$book->id)
        ->assertOk()
        ->assertDontSee('PPN')
        ->assertSee('Pajak');

    $this->actingAs($admin)
        ->get('/invoices/'.$goods->id)
        ->assertOk()
        ->assertSee('PPN');
});
