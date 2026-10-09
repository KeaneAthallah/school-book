@props(['row' => []])

@php
    $status = data_get($row, 'status');
    $label = data_get($row, 'status_label');
@endphp

@if ($status)
    <x-status-badge :value="$status" :label="$label" />
@else
    <span class="text-ink-muted">-</span>
@endif
