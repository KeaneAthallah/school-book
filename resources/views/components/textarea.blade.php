@props(['invalid' => false])

<textarea
    {{ $attributes->merge([
        'class' => 'w-full rounded-control border bg-panel px-3 py-2 text-sm text-ink transition outline-none placeholder:text-ink-muted focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:focus:ring-brand-500/25 '
            . ($invalid ? 'border-rose-400' : 'border-line-strong'),
    ]) }}
>{{ $slot }}</textarea>
