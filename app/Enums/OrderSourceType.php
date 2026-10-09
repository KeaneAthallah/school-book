<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum OrderSourceType: string
{
    use HasOptions;

    case Siplah = 'siplah';
    case Ekatalog = 'ekatalog';
    case Manual = 'manual';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Siplah => 'SIPLah',
            self::Ekatalog => 'e-Katalog',
            self::Manual => 'Manual',
            self::Other => 'Lainnya',
        };
    }
}
