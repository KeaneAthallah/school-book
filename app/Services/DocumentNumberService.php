<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    /**
     * Generate a sequential, human-readable document number.
     *
     * The sequence is scoped per prefix and per day and is protected by an
     * atomic cache lock so concurrent requests cannot produce duplicates.
     */
    public function generate(string $prefix, string $table, CarbonInterface|string|null $date = null, string $column = 'number'): string
    {
        $datePart = ($date === null ? now() : Carbon::parse($date))->format('Ymd');
        $key = "document-number:{$prefix}:{$datePart}";

        return Cache::lock($key, 10)->block(5, function () use ($prefix, $table, $column, $datePart): string {
            $pattern = "{$prefix}-{$datePart}-%";
            $last = DB::table($table)->where($column, 'like', $pattern)->orderByDesc($column)->value($column);

            $sequence = 1;

            if (is_string($last)) {
                $sequence = (int) substr($last, strlen("{$prefix}-{$datePart}-")) + 1;
            }

            return sprintf('%s-%s-%04d', $prefix, $datePart, $sequence);
        });
    }
}
