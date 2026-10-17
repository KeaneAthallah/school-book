<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Support\Money;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $number
 * @property Carbon $order_date
 * @property int $school_id
 * @property int $order_source_id
 * @property int|null $operator_id
 * @property OrderStatus $status
 * @property string $subtotal
 * @property string $discount_amount
 * @property string $discount_percent
 * @property string $tax_amount
 * @property string $dpp_amount
 * @property string $ppn_amount
 * @property string $pph_amount
 * @property string $total_amount
 * @property string|null $external_reference
 * @property Carbon|null $created_at
 */
#[Fillable([
    'number', 'order_date', 'school_id', 'order_source_id', 'operator_id', 'status',
    'subtotal', 'discount_amount', 'discount_percent', 'tax_amount',
    'dpp_amount', 'ppn_amount', 'pph_amount', 'total_amount', 'notes',
    'external_reference', 'external_source', 'source_payload', 'confirmed_at', 'completed_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'dpp_amount' => 'decimal:2',
            'ppn_amount' => 'decimal:2',
            'pph_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'source_payload' => 'array',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return BelongsTo<OrderSource, $this>
     */
    public function orderSource(): BelongsTo
    {
        return $this->belongsTo(OrderSource::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<Shipment, $this>
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [OrderStatus::Draft, OrderStatus::Received], true);
    }

    public function outstandingQuantity(): int
    {
        return (int) $this->items->sum(fn (OrderItem $item): int => $item->outstandingQuantity());
    }

    public function totalShippedValue(): string
    {
        return $this->items->reduce(
            fn (string $carry, OrderItem $item): string => Money::add(
                $carry,
                Money::mul($item->unit_price, (string) $item->shipped_quantity),
            ),
            '0.00',
        );
    }

    /**
     * Total of all invoice totals attached to the order (lunas when paid >= this).
     */
    public function totalInvoiceAmount(): string
    {
        return $this->invoices->reduce(
            fn (string $carry, Invoice $invoice): string => Money::add($carry, $invoice->total_amount),
            '0.00',
        );
    }

    public function paidAmount(): string
    {
        return $this->invoices->reduce(
            fn (string $carry, Invoice $invoice): string => Money::add($carry, $invoice->paid_amount),
            '0.00',
        );
    }

    public function isPaid(): bool
    {
        return Money::compare($this->totalInvoiceAmount(), '0') > 0
            && Money::compare($this->paidAmount(), $this->totalInvoiceAmount()) >= 0;
    }

    public function hasUnshippedDelivered(): bool
    {
        if ($this->relationLoaded('shipments')) {
            return $this->shipments->doesntContain(fn (Shipment $s): bool => in_array(
                $s->status->value,
                [ShipmentStatus::Shipped->value, ShipmentStatus::Delivered->value],
                true,
            ));
        }

        return $this->shipments()->whereNotIn('status', [
            ShipmentStatus::Shipped->value,
            ShipmentStatus::Delivered->value,
        ])->exists();
    }

    /**
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNotIn('status', [OrderStatus::Completed->value, OrderStatus::Draft->value]);
    }
}
