@props([
    'label',
    'value',
    'icon' => null,
    'tone' => 'text-ink',
    'iconTone' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300',
    'delta' => null,
    'deltaLabel' => null,
    'goodWhenDown' => false,
    'hint' => null,
])

@php
    $hasDelta = $delta !== null && $delta !== '' && is_finite((float) $delta);
    $rising = (float) $delta > 0;
    $flat = (float) $delta === 0.0;
    $positive = $goodWhenDown ? ! $rising : $rising;

    $deltaClasses = $flat
        ? 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300'
        : ($positive
            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
            : 'bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300');
@endphp

<x-card class="p-4">
    <div class="flex items-start justify-between gap-3">
        <p class="text-xs font-medium tracking-wide text-ink-soft">{{ $label }}</p>
        @if ($icon)
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-control {{ $iconTone }}">
                <x-icon :name="$icon" class="h-4.5 w-4.5" />
            </span>
        @endif
    </div>

    <p class="mt-2 text-xl font-semibold break-words tabular-nums sm:text-2xl {{ $tone }}">{{ $value }}</p>

    <div class="mt-1.5 flex items-center gap-1.5 text-xs">
        @if ($hasDelta)
            <span class="inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 font-medium {{ $deltaClasses }}">
                @if (! $flat)
                    <x-icon :name="$rising ? 'trendUp' : 'trendDown'" class="h-3.5 w-3.5" />
                @endif
                {{ $deltaLabel ?? abs(round((float) $delta)) . '%' }}
            </span>
        @endif

        @if ($hint)
            <span class="text-ink-muted">{{ $hint }}</span>
        @endif
    </div>
</x-card>
