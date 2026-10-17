<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int $zona_id
 * @property string $price
 */
#[Fillable(['product_id', 'zona_id', 'price'])]
class ProductPrice extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'product_id' => 'integer',
            'zona_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Zona, $this>
     */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }
}
