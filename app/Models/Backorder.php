<?php

namespace App\Models;

use App\Enums\BackorderStatus;
use Database\Factories\BackorderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_item_id
 * @property int $product_id
 * @property int $quantity
 * @property int $fulfilled_quantity
 * @property BackorderStatus $status
 */
#[Fillable(['order_item_id', 'product_id', 'quantity', 'fulfilled_quantity', 'status'])]
class Backorder extends Model
{
    /** @use HasFactory<BackorderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BackorderStatus::class,
            'quantity' => 'integer',
            'fulfilled_quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function outstandingQuantity(): int
    {
        return max(0, $this->quantity - $this->fulfilled_quantity);
    }
}
