<?php

namespace App\Models;

use Database\Factories\CashLoanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Cash loan record (pinjaman tunai).
 *
 * @property int $id
 * @property string $number
 * @property string $person_name
 * @property string|null $school_name
 * @property Carbon $loan_date
 * @property Carbon|null $due_date
 * @property string $amount
 * @property string|null $notes
 * @property string $status
 */
#[Fillable(['number', 'person_name', 'school_name', 'loan_date', 'due_date', 'amount', 'notes', 'status'])]
class CashLoan extends Model
{
    /** @use HasFactory<CashLoanFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'loan_date' => 'date',
            'due_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
