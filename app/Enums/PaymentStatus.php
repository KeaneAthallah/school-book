<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PaymentStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Verified = 'verified';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Verified => 'Terverifikasi',
            self::Reversed => 'Dibalik',
        };
    }
}
