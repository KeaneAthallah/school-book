<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum OrderItemStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Reserved = 'reserved';
    case Partial = 'partial';
    case Fulfilled = 'fulfilled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Stok',
            self::Reserved => 'Ready',
            self::Partial => 'Sebagian Ready',
            self::Fulfilled => 'Terpenuhi',
        };
    }
}
