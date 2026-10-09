{{-- Light / Dark / System switcher. State lives in the Alpine `themeSwitcher`
     component (see resources/js/app.js) so the preference and the resolved
     theme can never disagree. --}}
<div x-data="themeSwitcher" class="relative">
    <button
        type="button"
        x-on:click="open = ! open"
        class="inline-flex h-10 w-10 items-center justify-center rounded-control text-ink-soft transition hover:bg-panel-soft hover:text-ink sm:h-9 sm:w-9"
        :aria-expanded="open ? 'true' : 'false'"
        aria-label="Pilih tema tampilan"
    >
        <template x-if="preference === 'light'"><x-icon name="sun" class="h-5 w-5" /></template>
        <template x-if="preference === 'dark'"><x-icon name="moon" class="h-5 w-5" /></template>
        <template x-if="preference === 'system'"><x-icon name="monitor" class="h-5 w-5" /></template>
    </button>

    <div
        x-show="open"
        x-on:click.outside="open = false"
        x-on:keydown.escape.window="open = false"
        x-transition
        x-cloak
        class="absolute right-0 z-40 mt-2 w-[min(11rem,calc(100vw-2rem))] overflow-hidden rounded-card border border-line bg-panel p-1 shadow-pop"
        role="menu"
    >
        <p class="px-3 py-1.5 text-[11px] font-semibold tracking-wide text-ink-muted uppercase">Tema</p>

        <template x-for="option in [
            { value: 'light', label: 'Terang', icon: 'sun' },
            { value: 'dark', label: 'Gelap', icon: 'moon' },
            { value: 'system', label: 'Sistem', icon: 'monitor' },
        ]" :key="option.value">
            <button
                type="button"
                role="menuitemradio"
                x-on:click="choose(option.value); open = false"
                :aria-checked="preference === option.value ? 'true' : 'false'"
                class="flex w-full items-center gap-2 rounded-control px-3 py-2 text-sm text-ink-soft transition hover:bg-panel-soft hover:text-ink"
                :class="preference === option.value ? 'bg-panel-soft font-medium text-ink' : ''"
            >
                <template x-if="option.icon === 'sun'"><x-icon name="sun" class="h-4 w-4" /></template>
                <template x-if="option.icon === 'moon'"><x-icon name="moon" class="h-4 w-4" /></template>
                <template x-if="option.icon === 'monitor'"><x-icon name="monitor" class="h-4 w-4" /></template>
                <span x-text="option.label"></span>
                <template x-if="preference === option.value">
                    <x-icon name="check" class="ml-auto h-4 w-4 text-brand-600 dark:text-brand-300" />
                </template>
            </button>
        </template>
    </div>
</div>
