<?php

namespace App\Support;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Display formatting helpers shared by Blade views. Kept dependency-free so the
 * same output is produced regardless of the server locale.
 */
final class Format
{
    private const MONTHS = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
    ];

    public static function idr(mixed $value, ?string $fallback = null): string
    {
        if ($value === null || $value === '') {
            return $fallback ?? '-';
        }

        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }

    public static function number(mixed $value, ?string $fallback = null): string
    {
        if ($value === null || $value === '') {
            return $fallback ?? '-';
        }

        return number_format((float) $value, 0, ',', '.');
    }

    public static function compactNumber(mixed $value): string
    {
        $number = (float) $value;

        return match (true) {
            abs($number) >= 1_000_000_000 => round($number / 1_000_000_000, 1).' M',
            abs($number) >= 1_000_000 => round($number / 1_000_000, 1).' jt',
            abs($number) >= 1_000 => round($number / 1_000, 1).' rb',
            default => (string) round($number),
        };
    }

    public static function compactIdr(mixed $value): string
    {
        return 'Rp '.self::compactNumber($value);
    }

    public static function percent(mixed $value, int $digits = 0): string
    {
        return number_format((float) $value, $digits, ',', '.').'%';
    }

    public static function bool(mixed $value): string
    {
        return $value ? 'Ya' : 'Tidak';
    }

    public static function date(mixed $value): string
    {
        $date = self::toDate($value);

        return $date === null ? '-' : $date->format('d').' '.self::month($date);
    }

    public static function dateTime(mixed $value): string
    {
        $date = self::toDate($value);

        return $date === null ? '-' : $date->format('d').' '.self::month($date).' '.$date->format('H:i');
    }

    public static function dayMonth(mixed $value): string
    {
        $date = self::toDate($value);

        return $date === null ? '-' : $date->format('j').' '.self::month($date);
    }

    private static function month(Carbon $date): string
    {
        return self::MONTHS[(int) $date->format('n')].' '.$date->format('Y');
    }

    private static function toDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function label(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return method_exists($value, 'label') ? $value->label() : (string) $value->value;
        }

        return (string) $value;
    }
}
