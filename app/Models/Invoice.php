<?php

namespace App\Models;

use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceStatus;
use App\Support\Money;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $number
 * @property int|null $order_id
 * @property int|null $shipment_id
 * @property int $school_id
 * @property Carbon $invoice_date
 * @property Carbon $due_date
 * @property InvoiceStatus $status
 * @property string $subtotal
 * @property string $discount_amount
 * @property string $tax_amount
 * @property string $total_amount
 * @property string $paid_amount
 */
#[Fillable([
    'number', 'order_id', 'shipment_id', 'school_id', 'invoice_date', 'due_date',
    'status', 'subtotal', 'discount_amount', 'tax_amount', 'total_amount',
    'paid_amount', 'notes', 'created_by',
])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'status' => InvoiceStatus::class,
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * @return HasMany<PaymentAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Bukti foto pembayaran.
     *
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function outstandingAmount(): string
    {
        return Money::sub($this->total_amount, $this->paid_amount);
    }

    public function paymentStatus(): InvoicePaymentStatus
    {
        if (Money::compare($this->paid_amount, $this->total_amount) >= 0) {
            return InvoicePaymentStatus::Paid;
        }

        if ($this->due_date->isPast()) {
            return InvoicePaymentStatus::Overdue;
        }

        if (Money::compare($this->paid_amount, '0') > 0) {
            return InvoicePaymentStatus::Partial;
        }

        return InvoicePaymentStatus::Unpaid;
    }

    public function isIssued(): bool
    {
        return in_array($this->status, [InvoiceStatus::Issued, InvoiceStatus::Sent], true);
    }
}
