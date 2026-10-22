<?php

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

function paymentAdmin(): User
{
    return User::query()->where('email', 'admin@sekolah.test')->firstOrFail();
}

function makePaymentInvoice(): Invoice
{
    $school = School::query()->create([
        'name' => 'SD Negeri Test '.uniqid(),
        'npsn' => '1080'.random_int(100000, 999999),
        'status' => 'active',
    ]);

    $invoice = new Invoice();
    $invoice->number = 'INV-TEST-'.random_int(1000, 9999);
    $invoice->school_id = $school->getKey();
    $invoice->invoice_date = now();
    $invoice->due_date = now()->addDays(30);
    $invoice->status = 'issued';
    $invoice->subtotal = '100000.00';
    $invoice->discount_amount = '0.00';
    $invoice->tax_amount = '0.00';
    $invoice->dpp_amount = '100000.00';
    $invoice->ppn_amount = '0.00';
    $invoice->pph_amount = '0.00';
    $invoice->total_amount = '100000.00';
    $invoice->paid_amount = '0.00';
    $invoice->save();

    return $invoice;
}

it('shows an edit button for draft payments only', function () {
    $admin = paymentAdmin();
    $invoice = makePaymentInvoice();

    $draft = Payment::query()->create([
        'number' => 'PAY-DRAFT-1',
        'school_id' => $invoice->school_id,
        'payment_date' => now()->toDateString(),
        'amount' => '100000.00',
        'status' => 'draft',
    ]);

    $verified = Payment::query()->create([
        'number' => 'PAY-VERIFIED-1',
        'school_id' => $invoice->school_id,
        'payment_date' => now()->toDateString(),
        'amount' => '100000.00',
        'status' => 'verified',
    ]);

    $html = $this->actingAs($admin)
        ->get(route('payments.index'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain(route('payments.edit', $draft->id));
    expect($html)->not->toContain(route('payments.edit', $verified->id));
});

it('renders the payment edit form', function () {
    $admin = paymentAdmin();
    $invoice = makePaymentInvoice();

    $payment = Payment::query()->create([
        'number' => 'PAY-DRAFT-2',
        'school_id' => $invoice->school_id,
        'payment_date' => now()->toDateString(),
        'amount' => '100000.00',
        'status' => 'draft',
    ]);

    $payment->allocations()->create(['invoice_id' => $invoice->id, 'amount' => '100000.00']);

    $this->actingAs($admin)
        ->get(route('payments.edit', $payment))
        ->assertOk()
        ->assertSee('Ubah Pembayaran PAY-DRAFT-2')
        ->assertSee($invoice->number);
});

it('updates a draft payment', function () {
    $admin = paymentAdmin();
    $invoice = makePaymentInvoice();

    $payment = Payment::query()->create([
        'number' => 'PAY-DRAFT-3',
        'school_id' => $invoice->school_id,
        'payment_date' => now()->toDateString(),
        'amount' => '50000.00',
        'status' => 'draft',
    ]);

    $this->actingAs($admin)
        ->put(route('payments.update', $payment), [
            'school_id' => $invoice->school_id,
            'payment_date' => now()->toDateString(),
            'amount' => '75000.00',
            'reference' => 'REF-1',
            'allocations' => [['invoice_id' => $invoice->id, 'amount' => '75000.00']],
        ])
        ->assertRedirect(route('payments.index'));

    $payment->refresh();
    expect($payment->amount)->toBe('75000.00')
        ->and($payment->reference)->toBe('REF-1')
        ->and($payment->allocations()->count())->toBe(1);
});

it('refuses to update a verified payment', function () {
    $admin = paymentAdmin();
    $invoice = makePaymentInvoice();

    $payment = Payment::query()->create([
        'number' => 'PAY-VERIFIED-2',
        'school_id' => $invoice->school_id,
        'payment_date' => now()->toDateString(),
        'amount' => '50000.00',
        'status' => 'verified',
    ]);

    $this->actingAs($admin)
        ->put(route('payments.update', $payment), [
            'school_id' => $invoice->school_id,
            'payment_date' => now()->toDateString(),
            'amount' => '75000.00',
        ])
        ->assertSessionHasErrors('status');

    expect($payment->refresh()->amount)->toBe('50000.00');
});
