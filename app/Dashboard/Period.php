<?php

namespace App\Dashboard;

use Illuminate\Support\Carbon;

/**
 * A reporting period plus the immediately preceding period of equal length.
 *
 * Both the selected range (period metrics) and the comparison window (delta
 * badges) are derived here so every dashboard uses one consistent definition.
 * A delta is only produced when the comparison window actually contains data,
 * which prevents inventing growth percentages out of nothing.
 */
final readonly class Period
{
    private function __construct(
        public Carbon $start,
        public Carbon $end,
        public Carbon $previousStart,
        public Carbon $previousEnd,
    ) {}

    public static function make(?string $from = null, ?string $to = null): self
    {
        $end = self::parse($to) ?? Carbon::today();
        $start = self::parse($from) ?? $end->copy()->startOfMonth();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }

        $days = (int) $start->diffInDays($end) + 1;
        $previousEnd = $start->copy()->subDay();
        $previousStart = $previousEnd->copy()->subDays($days - 1);

        return new self($start, $end, $previousStart, $previousEnd);
    }

    public function days(): int
    {
        return (int) $this->start->diffInDays($this->end) + 1;
    }

    public function label(): string
    {
        return $this->start->format('d/m/Y').' – '.$this->end->format('d/m/Y');
    }

    /**
     * Day/month labels shared by the dashboard charts, oldest first.
     */
    public function isMonthly(): bool
    {
        return $this->days() > 62;
    }

    private static function parse(?string $value): ?Carbon
    {
        return $value !== null && $value !== '' ? Carbon::parse($value)->startOfDay() : null;
    }
}
