<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InvoicePaymentStatus: string
{
    use HasOptions;

    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum Bayar',
            self::Partial => 'Sebagian',
            self::Paid => 'Lunas',
            self::Overdue => 'Jatuh Tempo',
        };
    }
}
