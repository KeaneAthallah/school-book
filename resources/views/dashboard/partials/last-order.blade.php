{{--
    Last order date for a school. Expects: $row
--}}
@php
    $date = $row['last_order_date'] ?? null;
@endphp

@if ($date)
    <span class="text-xs text-ink-muted">{{ \App\Support\Format::date($date) }}</span>
@else
    <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
        Belum pernah
    </span>
@endif
