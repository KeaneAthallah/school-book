<?php

namespace App\Services;

use App\Enums\InvoicePaymentStatus;
use App\Enums\RebateStatus;
use App\Models\Invoice;
use App\Models\Rebate;
use App\Models\RebateRule;
use App\Models\Setting;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RebateService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Submit a rebate request for an invoice, enforcing eligibility and
     * duplicate-claim protection.
     *
     * @param  array<string, mixed>  $data
     */
    public function request(array $data, User $user): Rebate
    {
        return DB::transaction(function () use ($data, $user): Rebate {
            $invoice = Invoice::query()->findOrFail((int) $data['invoice_id']);
            $rule = $this->resolveRule($data);

            $requiresFullPayment = $rule === null
                ? Setting::boolean('finance.rebate_requires_full_payment', true)
                : $rule->requires_full_payment;

            if ($requiresFullPayment && $invoice->paymentStatus() !== InvoicePaymentStatus::Paid) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'Invoice harus lunas sebelum rebate dapat diajukan.',
                ]);
            }

            if ($rule?->min_invoice_amount !== null
                && Money::compare($invoice->total_amount, $rule->min_invoice_amount) < 0) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'Nilai invoice di bawah minimum aturan rebate.',
                ]);
            }

            $duplicate = Rebate::query()
                ->where('invoice_id', $invoice->getKey())
                ->where('rebate_rule_id', $rule?->getKey())
                ->whereNotIn('status', [RebateStatus::Rejected->value])
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'Rebate untuk invoice dan aturan ini sudah pernah diajukan.',
                ]);
            }

            $type = $rule === null ? (string) ($data['type'] ?? 'percentage') : $rule->type;
            $value = (string) ($rule === null ? ($data['value'] ?? '0') : $rule->value);
            $base = $invoice->total_amount;

            $rebate = Rebate::query()->create([
                'number' => $this->numbers->generate('RBT', 'rebates'),
                'school_id' => $invoice->school_id,
                'invoice_id' => $invoice->getKey(),
                'publisher_id' => $rule === null ? ($data['publisher_id'] ?? null) : $rule->publisher_id,
                'rebate_rule_id' => $rule?->getKey(),
                'type' => $type,
                'base_amount' => $base,
                'value' => $value,
                'amount' => Rebate::calculateAmount($type, $base, $value),
                'status' => RebateStatus::Submitted,
                'requested_by' => $user->getKey(),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->audit->log('rebate.requested', $rebate, 'Pengajuan rebate '.$rebate->number, user: $user);

            return $rebate->refresh();
        });
    }

    public function approve(Rebate $rebate, User $user): Rebate
    {
        return DB::transaction(function () use ($rebate, $user): Rebate {
            $locked = Rebate::query()->lockForUpdate()->findOrFail((int) $rebate->getKey());

            if (! in_array($locked->status, [RebateStatus::Submitted, RebateStatus::PendingApproval, RebateStatus::Draft], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Rebate ini tidak dapat disetujui pada status saat ini.',
                ]);
            }

            $locked->forceFill([
                'status' => RebateStatus::Approved,
                'approved_by' => $user->getKey(),
                'approved_at' => now(),
                'rejection_reason' => null,
            ])->save();

            $this->audit->log('rebate.approved', $locked, 'Persetujuan rebate '.$locked->number, user: $user);

            return $locked->refresh();
        });
    }

    public function reject(Rebate $rebate, string $reason, User $user): Rebate
    {
        return DB::transaction(function () use ($rebate, $reason, $user): Rebate {
            $locked = Rebate::query()->lockForUpdate()->findOrFail((int) $rebate->getKey());

            if ($locked->status === RebateStatus::Paid) {
                throw ValidationException::withMessages([
                    'status' => 'Rebate yang sudah dibayarkan tidak dapat ditolak.',
                ]);
            }

            $locked->forceFill([
                'status' => RebateStatus::Rejected,
                'rejection_reason' => $reason,
            ])->save();

            $this->audit->log('rebate.rejected', $locked, 'Penolakan rebate '.$locked->number, user: $user);

            return $locked->refresh();
        });
    }

    public function markPaid(Rebate $rebate, string $reference, User $user): Rebate
    {
        return DB::transaction(function () use ($rebate, $reference, $user): Rebate {
            $locked = Rebate::query()->lockForUpdate()->findOrFail((int) $rebate->getKey());

            if ($locked->status !== RebateStatus::Approved) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya rebate yang disetujui yang dapat dibayarkan.',
                ]);
            }

            $locked->forceFill([
                'status' => RebateStatus::Paid,
                'payment_reference' => $reference,
                'paid_at' => now(),
            ])->save();

            $this->audit->log('rebate.paid', $locked, 'Pembayaran rebate '.$locked->number, user: $user);

            return $locked->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveRule(array $data): ?RebateRule
    {
        $value = $data['rebate_rule_id'] ?? null;

        if (! is_numeric($value)) {
            return null;
        }

        return RebateRule::query()->find((int) $value);
    }
}
