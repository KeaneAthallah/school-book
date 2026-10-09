<?php

namespace App\Models;

use App\Enums\OrderSourceType;
use Database\Factories\OrderSourceFactory;
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
 * @property OrderSourceType $type
 * @property bool $is_active
 * @property array<string, mixed>|null $config
 */
#[Fillable(['code', 'name', 'type', 'is_active', 'config'])]
class OrderSource extends Model
{
    /** @use HasFactory<OrderSourceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => OrderSourceType::class,
            'is_active' => 'boolean',
            'config' => 'array',
        ];
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @param  Builder<OrderSource>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
