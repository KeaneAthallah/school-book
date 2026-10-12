{{--
    Timestamp for a stock movement row. Expects: $row
--}}
@php
    $time = $row['time'] ?? null;
@endphp

<span class="text-xs whitespace-nowrap text-ink-muted">
    {{ $time ? \App\Support\Format::dateTime($time) : '-' }}
</span>
