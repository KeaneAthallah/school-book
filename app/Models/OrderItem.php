<?php

namespace App\Models;

use App\Enums\OrderItemStatus;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int $quantity
 * @property string $unit_price
 * @property string $discount_amount
 * @property string $subtotal
 * @property int $reserved_quantity
 * @property int $shipped_quantity
 * @property int $backorder_quantity
 * @property OrderItemStatus $status
 */
#[Fillable([
    'order_id', 'product_id', 'warehouse_id', 'quantity', 'unit_price', 'discount_amount',
    'subtotal', 'reserved_quantity', 'shipped_quantity', 'backorder_quantity', 'status',
])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderItemStatus::class,
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'shipped_quantity' => 'integer',
            'backorder_quantity' => 'integer',
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
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function outstandingQuantity(): int
    {
        return max(0, $this->quantity - $this->shipped_quantity);
    }

    public function unbackorderedQuantity(): int
    {
        return max(0, $this->quantity - $this->shipped_quantity - $this->backorder_quantity);
    }
}
