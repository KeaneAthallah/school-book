@props([
    'title',
    'subtitle' => null,
    'breadcrumbs' => [],
])

<div {{ $attributes->merge(['class' => 'mb-5 flex flex-wrap items-end justify-between gap-3']) }}>
    <div class="min-w-0">
        @if (! empty($breadcrumbs))
            <nav aria-label="Breadcrumb" class="mb-1">
                <ol class="flex flex-wrap items-center gap-1.5 text-xs text-ink-muted">
                    @foreach ($breadcrumbs as $label => $url)
                        <li class="flex items-center gap-1.5">
                            @if ($url)
                                <a href="{{ $url }}" class="transition hover:text-brand-600 dark:hover:text-brand-300">{{ $label }}</a>
                            @else
                                <span>{{ $label }}</span>
                            @endif
                            @if (! $loop->last)
                                <x-icon name="chevronRight" class="h-3 w-3" />
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        <h1 class="text-xl font-semibold tracking-tight text-ink">{{ $title }}</h1>

        @if ($subtitle)
            <p class="mt-1 text-sm text-ink-soft">{{ $subtitle }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</div>
