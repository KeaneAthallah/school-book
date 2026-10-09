<?php

namespace App\Models;

use App\Enums\RebateStatus;
use App\Support\Money;
use Database\Factories\RebateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $number
 * @property int $school_id
 * @property int|null $invoice_id
 * @property int|null $publisher_id
 * @property string $type
 * @property string $base_amount
 * @property string $value
 * @property string $amount
 * @property RebateStatus $status
 */
#[Fillable([
    'number', 'school_id', 'invoice_id', 'publisher_id', 'rebate_rule_id', 'type',
    'base_amount', 'value', 'amount', 'status', 'requested_by', 'approved_by',
    'approved_at', 'rejection_reason', 'payment_reference', 'paid_at', 'notes',
])]
class Rebate extends Model
{
    /** @use HasFactory<RebateFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RebateStatus::class,
            'base_amount' => 'decimal:2',
            'value' => 'decimal:2',
            'amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Publisher, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    /**
     * @return BelongsTo<RebateRule, $this>
     */
    public function rebateRule(): BelongsTo
    {
        return $this->belongsTo(RebateRule::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function calculateAmount(string $type, string $base, string $value): string
    {
        return $type === 'percentage'
            ? Money::percentage($base, $value)
            : Money::add($value, '0');
    }
}
