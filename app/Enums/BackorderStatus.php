<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum BackorderStatus: string
{
    use HasOptions;

    case Open = 'open';
    case Partial = 'partial';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Menunggu Stok',
            self::Partial => 'Sebagian Terpenuhi',
            self::Fulfilled => 'Terpenuhi',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
