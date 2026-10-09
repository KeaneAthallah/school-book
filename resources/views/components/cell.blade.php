@props([
    'value' => null,
    'type' => 'text',
])

@php
    use App\Support\Format;

    $blank = $value === null || $value === '';
@endphp

@if ($blank)
    <span class="text-ink-muted">-</span>
@elseif ($type === 'currency')
    <span class="tabular-nums">{{ Format::idr($value) }}</span>
@elseif ($type === 'number')
    <span class="tabular-nums">{{ Format::number($value) }}</span>
@elseif ($type === 'boolean')
    {{ Format::bool($value) }}
@elseif ($type === 'date')
    {{ Format::date($value) }}
@elseif ($type === 'datetime')
    {{ Format::dateTime($value) }}
@elseif ($type === 'status' || $type === 'enum')
    <x-status-badge :value="$value" />
@else
    {{ Format::label($value) }}
@endif
