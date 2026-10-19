<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['backorder_id', 'publisher_id', 'quantity', 'ordered_at', 'number', 'note', 'status', 'created_by'])]
class BackorderOrder extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'ordered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Backorder, $this>
     */
    public function backorder(): BelongsTo
    {
        return $this->belongsTo(Backorder::class);
    }

    /**
     * @return BelongsTo<Publisher, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
