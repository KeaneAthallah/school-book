@props([
    'message' => 'Tidak ada data.',
    'icon' => 'clipboard',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-3 px-4 py-12 text-center']) }}>
    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-panel-soft text-ink-muted">
        <x-icon :name="$icon" class="h-5 w-5" />
    </span>
    <p class="text-sm text-ink-soft">{{ $message }}</p>

    @if (isset($action))
        <div>{{ $action }}</div>
    @endif
</div>
