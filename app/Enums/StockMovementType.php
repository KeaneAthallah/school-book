<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum StockMovementType: string
{
    use HasOptions;

    case Receipt = 'receipt';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case Reservation = 'reservation';
    case ReservationRelease = 'reservation_release';
    case Shipment = 'shipment';

    public function label(): string
    {
        return match ($this) {
            self::Receipt => 'Penerimaan',
            self::AdjustmentIn => 'Penyesuaian Masuk',
            self::AdjustmentOut => 'Penyesuaian Keluar',
            self::Reservation => 'Reservasi',
            self::ReservationRelease => 'Pelepasan Reservasi',
            self::Shipment => 'Pengiriman',
        };
    }
}
