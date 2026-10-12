<?php

namespace App\Models;

use Database\Factories\GoodsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * General goods (kipas angin, meja, ...) - the specialty side of a
 * product of type "goods".
 *
 * @property int $id
 * @property int $product_id
 * @property string|null $unit
 * @property string|null $description
 */
#[Fillable(['product_id', 'unit', 'description'])]
class Goods extends Model
{
    /** @use HasFactory<GoodsFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted(): void
    {
        static::deleted(function (Goods $goods): void {
            $goods->product()->delete();
        });
    }
}
