<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ItemAvailability: string
{
    use HasOptions;

    case Available = 'available';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Unavailable => 'Tidak tersedia (kosong)',
        };
    }
}
