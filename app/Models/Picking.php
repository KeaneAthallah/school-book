<?php

namespace App\Models;

use App\Enums\PickingStatus;
use Database\Factories\PickingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $number
 * @property int $order_id
 * @property PickingStatus $status
 */
#[Fillable([
    'number', 'order_id', 'status', 'notes',
    'created_by', 'picked_at', 'packed_at',
])]
class Picking extends Model
{
    /** @use HasFactory<PickingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PickingStatus::class,
            'picked_at' => 'datetime',
            'packed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return HasMany<PickingItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PickingItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
