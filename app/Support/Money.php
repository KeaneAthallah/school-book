<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Dependency-free decimal helper for currency stored as DECIMAL(15,2).
 *
 * Uses bcmath when the extension is available and falls back to
 * float arithmetic rounded to two decimals otherwise.
 */
final class Money
{
    public const SCALE = 2;

    public static function add(string|int|float ...$values): string
    {
        if (function_exists('bcadd')) {
            /** @var numeric-string $sum */
            $sum = '0';

            foreach ($values as $value) {
                $sum = bcadd($sum, self::normalize($value), self::SCALE);
            }

            return $sum;
        }

        return self::format(array_sum(array_map(fn ($value): float => (float) $value, $values)));
    }

    public static function sub(string|int|float $left, string|int|float ...$values): string
    {
        if (function_exists('bcsub')) {
            /** @var numeric-string $result */
            $result = self::normalize($left);

            foreach ($values as $value) {
                $result = bcsub($result, self::normalize($value), self::SCALE);
            }

            return $result;
        }

        $result = (float) $left;
        foreach ($values as $value) {
            $result -= (float) $value;
        }

        return self::format($result);
    }

    public static function mul(string|int|float $left, string|int|float $right): string
    {
        if (function_exists('bcmul')) {
            return bcmul(self::normalize($left), self::normalize($right), self::SCALE);
        }

        return self::format((float) $left * (float) $right);
    }

    public static function div(string|int|float $left, string|int|float $right): string
    {
        if ((float) $right === 0.0) {
            throw new InvalidArgumentException('Division by zero.');
        }

        if (function_exists('bcdiv')) {
            return bcdiv(self::normalize($left), self::normalize($right), self::SCALE);
        }

        return self::format((float) $left / (float) $right);
    }

    /**
     * Returns -1 when left < right, 0 when equal, 1 when left > right.
     */
    public static function compare(string|int|float $left, string|int|float $right): int
    {
        if (function_exists('bccomp')) {
            return bccomp(self::normalize($left), self::normalize($right), self::SCALE);
        }

        return (float) self::normalize($left) <=> (float) self::normalize($right);
    }

    public static function isZero(string|int|float $value): bool
    {
        return self::compare($value, '0') === 0;
    }

    /**
     * Applies a percentage (for example 10 for 10%) to a base amount.
     */
    public static function percentage(string|int|float $base, string|int|float $percent): string
    {
        return self::div(self::mul($base, $percent), '100');
    }

    /**
     * @return numeric-string
     */
    private static function normalize(string|int|float $value): string
    {
        return number_format((float) $value, self::SCALE, '.', '');
    }

    /**
     * @return numeric-string
     */
    private static function format(float $value): string
    {
        return number_format(round($value, self::SCALE), self::SCALE, '.', '');
    }
}
