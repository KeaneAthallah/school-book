@props(['invalid' => false])

<select
    {{ $attributes->merge([
        'class' => 'w-full rounded-control border bg-panel px-3 py-2 text-sm text-ink transition outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:focus:ring-brand-500/25 '
            . ($invalid ? 'border-rose-400' : 'border-line-strong'),
    ]) }}
>
    {{ $slot }}
</select>
