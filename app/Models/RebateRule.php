<?php

namespace App\Models;

use Database\Factories\RebateRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property int|null $publisher_id
 * @property string $type
 * @property string $value
 * @property string|null $min_invoice_amount
 * @property bool $requires_full_payment
 * @property string $status
 */
#[Fillable([
    'name', 'publisher_id', 'type', 'value', 'min_invoice_amount',
    'requires_full_payment', 'status',
])]
class RebateRule extends Model
{
    /** @use HasFactory<RebateRuleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_invoice_amount' => 'decimal:2',
            'requires_full_payment' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Publisher, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    /**
     * @param  Builder<RebateRule>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', 'active');
    }
}
