<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['backorder_order_id', 'backorder_id', 'quantity'])]
class BackorderOrderItem extends Model
{
    /**
     * @return BelongsTo<BackorderOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(BackorderOrder::class, 'backorder_order_id');
    }

    /**
     * @return BelongsTo<Backorder, $this>
     */
    public function backorder(): BelongsTo
    {
        return $this->belongsTo(Backorder::class);
    }
}