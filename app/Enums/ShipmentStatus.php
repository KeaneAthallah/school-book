<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ShipmentStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case ReadyToShip = 'ready_to_ship';
    case Shipped = 'shipped';
    case Delivered = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::ReadyToShip => 'Siap Kirim',
            self::Shipped => 'Terkirim',
            self::Delivered => 'Diterima',
        };
    }
}
