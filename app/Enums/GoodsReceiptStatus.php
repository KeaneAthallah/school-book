<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum GoodsReceiptStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Confirmed = 'confirmed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Confirmed => 'Dikonfirmasi',
        };
    }
}
