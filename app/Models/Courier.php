<?php

namespace App\Models;

use Database\Factories\CourierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $status
 */
#[Fillable(['code', 'name', 'phone', 'status'])]
class Courier extends Model
{
    /** @use HasFactory<CourierFactory> */
    use HasFactory;

    /**
     * @return HasMany<ShippingMethod, $this>
     */
    public function shippingMethods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class);
    }

    /**
     * @param  Builder<Courier>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', 'active');
    }
}
