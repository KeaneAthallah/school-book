{{--
    Days until an invoice falls due. Expects: $row
--}}
@php
    $days = (int) ($row['days_until_due'] ?? 0);
@endphp

@if ($days <= 0)
    <x-status-badge value="overdue" label="Hari ini" />
@elseif ($days <= 7)
    <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
        {{ $days }} hari
    </span>
@else
    <span class="text-xs text-ink-muted tabular-nums">{{ $days }} hari</span>
@endif
