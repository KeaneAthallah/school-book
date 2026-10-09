@props(['tone' => 'info'])

@php
    $tones = [
        'success' => ['bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/25', 'checkCircle'],
        'error' => ['bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:border-rose-500/25', 'alert'],
        'warning' => ['bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:border-amber-500/25', 'clock'],
        'info' => ['bg-brand-50 text-brand-700 border-brand-200 dark:bg-brand-500/10 dark:text-brand-300 dark:border-brand-500/25', 'bell'],
    ];

    [$classes, $icon] = $tones[$tone] ?? $tones['info'];
@endphp

<div
    role="alert"
    {{ $attributes->merge(['class' => 'mb-4 flex items-start gap-2 rounded-card border px-3.5 py-2.5 text-sm ' . $classes]) }}
>
    <x-icon :name="$icon" class="mt-0.5 h-4 w-4 shrink-0" />
    <span>{{ $slot }}</span>
</div>
