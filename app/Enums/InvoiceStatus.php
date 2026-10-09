<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InvoiceStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Issued = 'issued';
    case Sent = 'sent';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Issued => 'Terbit',
            self::Sent => 'Terkirim',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
