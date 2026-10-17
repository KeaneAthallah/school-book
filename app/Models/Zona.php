<?php

namespace App\Models;

use Database\Factories\ZonaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Geographic / market zone used for zone-specific book pricing.
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $status
 */
#[Fillable(['name', 'code', 'status'])]
class Zona extends Model
{
    /** @use HasFactory<ZonaFactory> */
    use HasFactory;

    /**
     * @return HasMany<ProductPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'zona_id');
    }

    /**
     * @param  Builder<Zona>  $query
     * @return Builder<Zona>
     */
    public function scopeActive(Builder $query): Builder
    {
        $query->where('status', 'active');

        return $query;
    }
}
