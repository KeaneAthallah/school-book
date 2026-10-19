<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['catalog_id', 'product_id', 'sort_order'])]
class ProductCatalogItem extends Model
{
    /**
     * @return BelongsTo<ProductCatalog, $this>
     */
    public function catalog(): BelongsTo
    {
        return $this->belongsTo(ProductCatalog::class, 'catalog_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
