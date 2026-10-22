@php
    use App\Support\Format;
@endphp

<span class="font-semibold tabular-nums {{ $row['sisa'] > 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
    {{ Format::number($row['sisa']) }}
</span>
