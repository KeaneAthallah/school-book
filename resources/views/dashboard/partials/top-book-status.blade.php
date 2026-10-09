
@php
    $map = [
        'out' => ['cancelled', 'Habis'],
        'low' => ['open', 'Menipis'],
        'ok' => ['active', 'Aman'],
    ];

    [$tone, $label] = $map[$row['state'] ?? 'ok'] ?? $map['ok'];
@endphp

<x-status-badge :value="$tone" :label="$label" />
