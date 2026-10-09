<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PickingStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Picking = 'picking';
    case Picked = 'picked';
    case Packed = 'packed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Belum Ready',
            self::Picking => 'Picking',
            self::Picked => 'Packing',
            self::Packed => 'Siap Kirim',
        };
    }
}
