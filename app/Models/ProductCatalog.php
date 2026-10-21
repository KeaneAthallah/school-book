<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'type', 'zona_id'])]
class ProductCatalog extends Model
{
    /**
     * @return HasMany<ProductCatalogItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProductCatalogItem::class, 'catalog_id');
    }

    /**
     * Zone whose zone-specific price is printed for every product in the
     * catalog. Null prints each product's base price.
     *
     * @return BelongsTo<Zona, $this>
     */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_id');
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_catalog_items', 'catalog_id', 'product_id')
            ->withPivot('sort_order')
            ->withTimestamps();
    }
}
