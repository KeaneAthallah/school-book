
@php
    $status = $row['status'] ?? 'info';
    $tone = match ($status) {
        'success' => 'active',
        'skipped' => 'open',
        default => 'error',
    };
@endphp

<x-status-badge :value="$tone" :label="$status" :dot="false" />
