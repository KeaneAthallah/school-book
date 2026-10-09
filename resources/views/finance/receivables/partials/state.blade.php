
@php
    $state = $row['state'] ?? 'unpaid';

    [$tone, $label] = match ($state) {
        'overdue' => ['overdue', 'Lewat '.((int) ($row['days_overdue'] ?? 0)).' hari'],
        'partial' => ['partial', 'Sebagian'],
        default => ['unpaid', 'Belum bayar'],
    };
@endphp

<x-status-badge :value="$tone" :label="$label" />
