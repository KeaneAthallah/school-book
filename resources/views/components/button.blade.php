@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $variants = [
        'primary' => 'bg-brand-600 text-white shadow-sm hover:bg-brand-700 disabled:bg-brand-300',
        'secondary' => 'border border-line-strong bg-panel text-ink-soft hover:bg-panel-soft',
        'danger' => 'bg-rose-600 text-white shadow-sm hover:bg-rose-700',
        'ghost' => 'text-ink-soft hover:bg-panel-soft hover:text-ink',
        'subtle' => 'bg-brand-50 text-brand-700 hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-300 dark:hover:bg-brand-500/25',
    ];

    $sizes = [
        'sm' => 'min-h-8 px-2.5 py-1 text-xs',
        'md' => 'min-h-10 px-3.5 py-2 text-sm',
    ];

    $classes = 'inline-flex items-center justify-center gap-1.5 rounded-control font-medium transition disabled:cursor-not-allowed disabled:opacity-60 '
        .($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
