<?php

namespace App\Models;

use Database\Factories\WarehouseFactory;
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
#[Fillable(['code', 'name', 'address', 'status'])]
class Warehouse extends Model
{
    /** @use HasFactory<WarehouseFactory> */
    use HasFactory;

    /**
     * @return HasMany<Stock, $this>
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * @param  Builder<Warehouse>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /**
     * Resolve the warehouse used for order reservations and shipping.
     */
    public static function default(): self
    {
        $configuredId = Setting::value('inventory.default_warehouse_id');

        if ($configuredId !== null) {
            $configured = static::query()->find((int) $configuredId);

            if ($configured !== null) {
                return $configured;
            }
        }

        return static::query()->where('status', 'active')->orderBy('id')->firstOrFail();
    }
}
