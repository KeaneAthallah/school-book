<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $picking_id
 * @property int $order_item_id
 * @property int $book_id
 * @property int $quantity
 * @property int $picked_quantity
 */
#[Fillable(['picking_id', 'order_item_id', 'book_id', 'quantity', 'picked_quantity'])]
class PickingItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'picked_quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Picking, $this>
     */
    public function picking(): BelongsTo
    {
        return $this->belongsTo(Picking::class);
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * @return BelongsTo<Book, $this>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
