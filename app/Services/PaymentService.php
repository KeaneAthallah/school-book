<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Record a payment (draft) and optionally allocate it to invoices.
     * When no explicit allocation is provided it is applied to the school's
     * oldest outstanding invoices first.
     *
     * @param  array<string, mixed>  $data
     */
    public function record(array $data, User $user): Payment
    {
        return DB::transaction(function () use ($data, $user): Payment {
            $payment = Payment::query()->create([
                'number' => $this->numbers->generate('PAY', 'payments', $data['payment_date'] ?? null),
                'school_id' => (int) $data['school_id'],
                'payment_date' => $data['payment_date'],
                'amount' => (string) $data['amount'],
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => PaymentStatus::Draft,
                'created_by' => $user->getKey(),
            ]);

            $allocations = $data['allocations'] ?? [];

            if ($allocations === []) {
                $this->autoAllocate($payment);
            } else {
                foreach ($allocations as $entry) {
                    $amount = (string) ($entry['amount'] ?? '0');

                    if (Money::compare($amount, '0') <= 0) {
                        continue;
                    }

                    $this->allocate($payment, Invoice::query()->findOrFail((int) $entry['invoice_id']), $amount);
                }
            }

            $this->audit->log('payment.recorded', $payment, 'Pencatatan pembayaran '.$payment->number, newValues: [
                'amount' => $payment->amount,
            ], user: $user);

            return $payment->refresh();
        });
    }

    public function allocate(Payment $payment, Invoice $invoice, string $amount): PaymentAllocation
    {
        if ($payment->school_id !== $invoice->school_id) {
            throw ValidationException::withMessages([
                'invoice_id' => 'Invoice bukan milik sekolah pada pembayaran ini.',
            ]);
        }

        if (Money::compare($amount, '0') <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Jumlah alokasi harus lebih dari nol.',
            ]);
        }

        $alreadyAllocated = (string) $payment->allocations()->sum('amount');

        if (Money::compare(Money::add($alreadyAllocated, $amount), $payment->amount) > 0) {
            throw ValidationException::withMessages([
                'amount' => 'Total alokasi melebihi jumlah pembayaran.',
            ]);
        }

        if (Money::compare($amount, $invoice->outstandingAmount()) > 0) {
            throw ValidationException::withMessages([
                'amount' => 'Alokasi melebihi sisa tagihan invoice.',
            ]);
        }

        return $payment->allocations()->create([
            'invoice_id' => $invoice->getKey(),
            'amount' => $amount,
        ]);
    }

    /**
     * Verify a payment and recalculate the affected invoices.
     */
    public function verify(Payment $payment, User $user): Payment
    {
        return DB::transaction(function () use ($payment, $user): Payment {
            $locked = Payment::query()->lockForUpdate()->findOrFail((int) $payment->getKey());

            if ($locked->status === PaymentStatus::Verified) {
                return $locked;
            }

            $locked->forceFill([
                'status' => PaymentStatus::Verified,
                'verified_by' => $user->getKey(),
                'verified_at' => now(),
            ])->save();

            foreach ($locked->allocations()->pluck('invoice_id')->unique() as $invoiceId) {
                $this->recalculateInvoice(Invoice::query()->findOrFail((int) $invoiceId));
            }

            $this->audit->log('payment.verified', $locked, 'Verifikasi pembayaran '.$locked->number, user: $user);

            return $locked->refresh();
        });
    }

    /**
     * Reverse a verified payment. Confirmed financial records are never
     * deleted; the reversal removes the payment effect and keeps history.
     */
    public function reverse(Payment $payment, User $user): Payment
    {
        return DB::transaction(function () use ($payment, $user): Payment {
            $locked = Payment::query()->lockForUpdate()->findOrFail((int) $payment->getKey());

            $locked->forceFill(['status' => PaymentStatus::Reversed])->save();

            foreach ($locked->allocations()->pluck('invoice_id')->unique() as $invoiceId) {
                $this->recalculateInvoice(Invoice::query()->findOrFail((int) $invoiceId));
            }

            $this->audit->log('payment.reversed', $locked, 'Pembalikan pembayaran '.$locked->number, user: $user);

            return $locked->refresh();
        });
    }

    private function recalculateInvoice(Invoice $invoice): void
    {
        $paid = PaymentAllocation::query()
            ->where('invoice_id', $invoice->getKey())
            ->whereHas('payment', fn ($query) => $query->where('status', PaymentStatus::Verified->value))
            ->sum('amount');

        $invoice->forceFill(['paid_amount' => Money::add((string) $paid, '0')])->save();
    }

    private function autoAllocate(Payment $payment): void
    {
        $remaining = $payment->amount;

        $invoices = Invoice::query()
            ->where('school_id', $payment->school_id)
            ->whereNotIn('status', [InvoiceStatus::Draft->value, InvoiceStatus::Cancelled->value])
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        foreach ($invoices as $invoice) {
            if (Money::compare($remaining, '0') <= 0) {
                break;
            }

            $outstanding = $invoice->outstandingAmount();

            if (Money::compare($outstanding, '0') <= 0) {
                continue;
            }

            $amount = Money::compare($remaining, $outstanding) >= 0 ? $outstanding : $remaining;

            $payment->allocations()->create([
                'invoice_id' => $invoice->getKey(),
                'amount' => $amount,
            ]);

            $remaining = Money::sub($remaining, $amount);
        }
    }
}
