<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum OrderStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Received = 'received';
    case Processing = 'processing';
    case PartialReady = 'partial_ready';
    case Ready = 'ready';
    case PartialShipped = 'partial_shipped';
    case FullyShipped = 'fully_shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Received => 'Pesanan Diterima',
            self::Processing => 'Diproses',
            self::PartialReady => 'Sebagian Ready',
            self::Ready => 'Ready',
            self::PartialShipped => 'Sebagian Terkirim',
            self::FullyShipped => 'Terkirim Lengkap',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function isFulfilled(): bool
    {
        return in_array($this, [self::FullyShipped, self::Completed], true);
    }
}
