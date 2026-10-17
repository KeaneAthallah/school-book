@props(['invalid' => false])

<div
    x-data="{
        q: '',
        showSearch: false,
        init() {
            this.showSearch = this.$refs.select.options.length > 20;
        },
        filter() {
            const q = this.q.toLowerCase();
            for (const opt of this.$refs.select.options) {
                if (opt.value === '') { opt.hidden = false; continue; }
                opt.hidden = q !== '' && !opt.text.toLowerCase().includes(q);
            }
        },
    }"
    class="space-y-1.5"
>
    <input
        type="search"
        x-show="showSearch"
        x-model="q"
        @input="filter()"
        placeholder="Cari..."
        class="w-full rounded-control border border-line-strong bg-panel px-3 py-1.5 text-xs text-ink outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:focus:ring-brand-500/25"
    />
    <select
        x-ref="select"
        {{ $attributes->merge([
            'class' => 'w-full rounded-control border bg-panel px-3 py-2 text-sm text-ink transition outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:focus:ring-brand-500/25 '
                . ($invalid ? 'border-rose-400' : 'border-line-strong'),
        ]) }}
    >
        {{ $slot }}
    </select>
</div>