<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Shared catalog entry for books and general goods.
 *
 * @property int $id
 * @property string $type
 * @property string $code
 * @property string $title
 * @property string $price
 * @property string $cost_price
 * @property string|null $rack_location
 * @property string $status
 */
#[Fillable([
    'type', 'code', 'title', 'price', 'cost_price',
    'rack_location', 'status',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
        ];
    }

    /**
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /**
     * @return HasOne<Book, $this>
     */
    public function book(): HasOne
    {
        return $this->hasOne(Book::class);
    }

    /**
     * @return HasOne<Goods, $this>
     */
    public function goods(): HasOne
    {
        return $this->hasOne(Goods::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * @return HasMany<ShipmentItem, $this>
     */
    public function shipmentItems(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    /**
     * @return HasMany<PickingItem, $this>
     */
    public function pickingItems(): HasMany
    {
        return $this->hasMany(PickingItem::class);
    }

    /**
     * @return HasMany<GoodsReceiptItem, $this>
     */
    public function receiptItems(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    /**
     * @return HasMany<ProductPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    /**
     * Harga khusus untuk zona, atau null bila belum pernah diatur.
     */
    public function priceForZona(?int $zonaId): ?string
    {
        if ($zonaId === null) {
            return null;
        }

        $price = $this->prices()->where('zona_id', $zonaId)->value('price');

        return $price === null ? null : (string) $price;
    }
}
