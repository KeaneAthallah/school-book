@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'tone' => null,
])

@php
    use App\Support\Accent;

    $chip = $icon !== null
        ? Accent::gradientByName($tone ?? Accent::toneFor($icon))
        : null;
@endphp

<div {{ $attributes->merge(['class' => 'flex items-start justify-between gap-3 border-b border-line px-4 py-3.5']) }}>
    <div class="flex min-w-0 items-start gap-2.5">
        @if ($chip)
            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-control {{ $chip }} shadow-sm">
                <x-icon :name="$icon" class="h-4.5 w-4.5" />
            </span>
        @endif
        <div class="min-w-0">
            <h2 class="truncate text-sm font-semibold text-ink">{{ $title }}</h2>
            @if ($subtitle)
                <p class="mt-0.5 text-xs text-ink-soft">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    @if (isset($action))
        <div class="shrink-0">{{ $action }}</div>
    @endif
</div>
