@props([
    'name',
    'title' => '',
    'wide' => false,
])

<div
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true"
    x-on:close-modal.window="if (!$event.detail || $event.detail === '{{ $name }}') open = false"
    x-on:keydown.escape.window="open = false"
    x-cloak
>
    <div
        x-show="open"
        x-transition.opacity.duration.150ms
        x-effect="document.body.style.overflow = open ? 'hidden' : ''"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto overscroll-contain bg-navy-950/50 p-3 backdrop-blur-sm sm:p-4"
        role="dialog"
        aria-modal="true"
        aria-label="{{ $title }}"
    >
        <div
            x-show="open"
            x-transition
            class="my-4 w-full max-w-lg rounded-card border border-line bg-panel shadow-pop sm:mt-10 sm:mb-0 {{ $wide ? 'sm:max-w-3xl' : '' }}"
        >
            <div class="flex items-center justify-between border-b border-line px-4 py-3.5">
                <h3 class="text-sm font-semibold text-ink">{{ $title }}</h3>
                <button
                    type="button"
                    aria-label="Tutup"
                    x-on:click="open = false"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-control text-ink-muted transition hover:bg-panel-soft hover:text-ink"
                >
                    <x-icon name="close" class="h-4.5 w-4.5" />
                </button>
            </div>

            <div class="p-4">{{ $slot }}</div>
        </div>
    </div>
</div>
