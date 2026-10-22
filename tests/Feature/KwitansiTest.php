<?php

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\School;
use App\Models\User;
use App\Services\PaymentService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

function kwitansiAdmin(): User
{
    return User::query()->where('email', 'admin@sekolah.test')->firstOrFail();
}

function kwitansiInvoice(int $total = 296000, ?School $school = null): Invoice
{
    $school ??= School::query()->create([
        'name' => 'TK Nurul Yaqin '.uniqid(),
        'npsn' => '1080'.random_int(100000, 999999),
        'principal_name' => 'Mufida Olii, S.Pd',
        'principal_nip' => '198501012010012001',
        'status' => 'active',
    ]);

    $invoice = new Invoice();
    $invoice->number = 'INV-KW-'.random_int(1000, 9999);
    $invoice->school_id = $school->getKey();
    $invoice->invoice_date = now();
    $invoice->due_date = now()->addDays(30);
    $invoice->status = 'issued';
    $invoice->subtotal = (string) $total;
    $invoice->discount_amount = '0.00';
    $invoice->tax_amount = '0.00';
    $invoice->dpp_amount = (string) $total;
    $invoice->ppn_amount = '0.00';
    $invoice->pph_amount = '0.00';
    $invoice->total_amount = (string) $total;
    $invoice->paid_amount = '0.00';
    $invoice->save();

    $invoice->items()->create([
        'description' => 'Buku TK A Deep Learning',
        'quantity' => 1,
        'unit_price' => (string) $total,
        'discount_amount' => '0.00',
        'subtotal' => (string) $total,
    ]);

    return $invoice->refresh();
}

it('generates a receipt number for every payment allocation', function () {
    $admin = kwitansiAdmin();
    $invoice = kwitansiInvoice();
    $service = app(PaymentService::class);

    $payment = $service->record([
        'school_id' => $invoice->school_id,
        'payment_date' => now()->toDateString(),
        'amount' => '100000.00',
        'allocations' => [['invoice_id' => $invoice->id, 'amount' => '100000.00']],
    ], $admin);

    $allocation = $payment->allocations()->firstOrFail();

    expect($allocation->receipt_number)->not->toBeNull()
        ->and($allocation->receipt_number)->toStartWith('KW-');
});

it('prints a kwitansi for a payment allocation', function () {
    $admin = kwitansiAdmin();
    $invoice = kwitansiInvoice();
    $service = app(PaymentService::class);

    $payment = $service->record([
        'school_id' => $invoice->school_id,
        'payment_date' => now()->toDateString(),
        'amount' => '100000.00',
        'allocations' => [['invoice_id' => $invoice->id, 'amount' => '100000.00']],
    ], $admin);

    $service->verify($payment, $admin);

    $allocation = $payment->allocations()->firstOrFail();

    $html = $this->actingAs($admin)
        ->get(route('payments.receipt', ['payment' => $payment->id, 'allocation' => $allocation->id]))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('KWITANSI')
        ->and($html)->toContain($allocation->receipt_number)
        ->and($html)->toContain('MUFIDA OLII, S.PD')
        ->and($html)->toContain('Seratus Ribu Rupiah')
        ->and($html)->toContain('100.000')
        ->and($html)->toContain('296.000')
        ->and($html)->toContain('196.000')
        ->and($html)->toContain('BUKU TK A DEEP LEARNING')
        ->and($html)->toContain('<svg');
});

it('issues a separate kwitansi for every installment on one invoice', function () {
    $admin = kwitansiAdmin();
    $invoice = kwitansiInvoice();
    $service = app(PaymentService::class);

    $first = $service->record([
        'school_id' => $invoice->school_id,
        'payment_date' => now()->toDateString(),
        'amount' => '100000.00',
        'allocations' => [['invoice_id' => $invoice->id, 'amount' => '100000.00']],
    ], $admin);
    $service->verify($first, $admin);

    $second = $service->record([
        'school_id' => $invoice->school_id,
        'payment_date' => now()->toDateString(),
        'amount' => '96000.00',
        'allocations' => [['invoice_id' => $invoice->id, 'amount' => '96000.00']],
    ], $admin);
    $service->verify($second, $admin);

    $allocations = $invoice->allocations()->orderBy('id')->get();

    expect($allocations)->toHaveCount(2)
        ->and($allocations[0]->receipt_number)->not->toBe($allocations[1]->receipt_number);

    // Kwitansi pertama menunjukkan sisa hutang 196.000, kedua menunjukkan 100.000.
    $firstHtml = $this->actingAs($admin)
        ->get(route('payments.receipt', ['payment' => $first->id, 'allocation' => $allocations[0]->id]))
        ->assertOk()
        ->getContent();

    expect($firstHtml)->toContain('196.000');

    $secondHtml = $this->actingAs($admin)
        ->get(route('payments.receipt', ['payment' => $second->id, 'allocation' => $allocations[1]->id]))
        ->assertOk()
        ->getContent();

    expect($secondHtml)->toContain('100.000');
});

it('refuses a receipt for an allocation of another payment', function () {
    $admin = kwitansiAdmin();
    $invoice = kwitansiInvoice();

    $payment = Payment::query()->create([
        'number' => 'PAY-KW-1',
        'school_id' => $invoice->school_id,
        'payment_date' => now()->toDateString(),
        'amount' => '100000.00',
        'status' => 'draft',
    ]);

    $other = Payment::query()->create([
        'number' => 'PAY-KW-2',
        'school_id' => $invoice->school_id,
        'payment_date' => now()->toDateString(),
        'amount' => '50000.00',
        'status' => 'draft',
    ]);

    $allocation = $other->allocations()->create([
        'invoice_id' => $invoice->id,
        'amount' => '50000.00',
        'receipt_number' => 'KW-20260101-0001',
    ]);

    $this->actingAs($admin)
        ->get(route('payments.receipt', ['payment' => $payment->id, 'allocation' => $allocation->id]))
        ->assertNotFound();
});

it('shows the kwitansi link in the payment list and invoice history', function () {
    $admin = kwitansiAdmin();
    $invoice = kwitansiInvoice();
    $service = app(PaymentService::class);

    $payment = $service->record([
        'school_id' => $invoice->school_id,
        'payment_date' => now()->toDateString(),
        'amount' => '100000.00',
        'allocations' => [['invoice_id' => $invoice->id, 'amount' => '100000.00']],
    ], $admin);

    $allocation = $payment->allocations()->firstOrFail();
    $url = route('payments.receipt', ['payment' => $payment->id, 'allocation' => $allocation->id]);

    $this->actingAs($admin)
        ->get(route('payments.index'))
        ->assertOk()
        ->assertSee($url);

    $this->actingAs($admin)
        ->get(route('invoices.show', $invoice->id))
        ->assertOk()
        ->assertSee($url);
});
