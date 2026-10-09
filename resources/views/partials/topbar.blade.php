@php
    $user = auth()->user();
    $initials = collect(explode(' ', (string) $user?->name))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<header class="sticky top-0 z-30 border-b border-line bg-panel/95 backdrop-blur">
    <div class="flex h-header items-center gap-2 px-3 sm:gap-3 sm:px-4">
        <button
            type="button"
            aria-label="Buka menu navigasi"
            x-on:click="mobileNav = true"
            class="inline-flex h-10 w-10 items-center justify-center rounded-control text-ink-soft transition hover:bg-panel-soft lg:hidden"
        >
            <x-icon name="menu" class="h-5 w-5" />
        </button>

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-ink">{{ $title ?? '' }}</p>
        </div>

        {{-- Global search --}}
        <div
            x-data="{
                open: false,
                term: '',
                groups: [],
                loading: false,
                failed: false,
                cursor: 0,
                get flat() { return this.groups.flatMap((group) => group.items); },
                init() {
                    window.addEventListener('keydown', (event) => {
                        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                            event.preventDefault();
                            this.open = true;
                            this.$nextTick(() => this.$refs.input?.focus());
                        }
                    });
                },
                async run() {
                    const query = this.term.trim();
                    if (query.length < 2) { this.groups = []; this.loading = false; return; }
                    this.loading = true; this.failed = false;
                    try {
                        const response = await fetch('/search?q=' + encodeURIComponent(query), { headers: { Accept: 'application/json' } });
                        if (!response.ok) { throw new Error('failed'); }
                        const payload = await response.json();
                        this.groups = payload.groups ?? [];
                        this.cursor = 0;
                    } catch (error) {
                        this.failed = true;
                    } finally {
                        this.loading = false;
                    }
                },
                go(item) {
                    const target = item ?? this.flat[this.cursor];
                    if (!target) { return; }
                    window.location.href = target.href;
                },
            }"
            x-on:keydown.escape.window="open = false"
            x-cloak
        >
            <button
                type="button"
                x-on:click="open = true; $nextTick(() => $refs.input?.focus())"
                class="hidden items-center gap-2 rounded-control border border-line-strong bg-panel px-3 py-1.5 text-sm text-ink-soft transition hover:bg-panel-soft sm:flex"
            >
                <x-icon name="search" class="h-4 w-4" />
                <span>Cari…</span>
                <kbd class="ml-2 rounded border border-line bg-panel-soft px-1.5 text-[10px] text-ink-muted">Ctrl K</kbd>
            </button>

            <button
                type="button"
                aria-label="Buka pencarian"
                x-on:click="open = true; $nextTick(() => $refs.input?.focus())"
                class="inline-flex h-10 w-10 items-center justify-center rounded-control text-ink-soft transition hover:bg-panel-soft sm:hidden"
            >
                <x-icon name="search" class="h-5 w-5" />
            </button>

            <div
                x-show="open"
                x-transition.opacity.duration.150ms
                x-effect="document.body.style.overflow = open ? 'hidden' : ''"
                class="fixed inset-0 z-50 flex items-start justify-center bg-navy-950/50 p-3 backdrop-blur-sm sm:p-4"
                role="dialog"
                aria-modal="true"
                aria-label="Pencarian global"
            >
                <div x-on:click.outside="open = false" class="mt-2 w-full max-w-xl overflow-hidden rounded-card border border-line bg-panel shadow-pop sm:mt-16">
                    <div class="flex items-center gap-2 border-b border-line px-3">
                        <x-icon name="search" class="h-5 w-5 text-ink-muted" />
                        <input
                            x-ref="input"
                            x-model="term"
                            x-on:input.debounce.250ms="run()"
                            x-on:keydown.arrow-down.prevent="cursor = Math.min(cursor + 1, flat.length - 1)"
                            x-on:keydown.arrow-up.prevent="cursor = Math.max(cursor - 1, 0)"
                            x-on:keydown.enter.prevent="go()"
                            type="search"
                            placeholder="Cari pesanan, invoice, sekolah, atau buku…"
                            aria-label="Kata kunci pencarian"
                            class="w-full bg-transparent py-3.5 text-sm text-ink outline-none placeholder:text-ink-muted"
                        />
                        <kbd class="rounded border border-line bg-panel-soft px-1.5 py-0.5 text-[10px] text-ink-muted">Esc</kbd>
                    </div>

                    <div class="scroll-slim max-h-96 overflow-y-auto">
                        <p x-show="term.trim().length < 2" class="px-4 py-8 text-center text-sm text-ink-soft">
                            Ketik minimal 2 karakter untuk mencari.
                        </p>
                        <p x-show="term.trim().length >= 2 && loading" class="px-4 py-8 text-center text-sm text-ink-soft">Mencari…</p>
                        <p x-show="failed" class="px-4 py-8 text-center text-sm text-rose-600 dark:text-rose-400">Pencarian gagal dimuat.</p>
                        <p x-show="! loading && ! failed && term.trim().length >= 2 && flat.length === 0" class="px-4 py-8 text-center text-sm text-ink-soft">
                            Tidak ada hasil yang cocok.
                        </p>

                        <template x-for="group in groups" :key="group.key">
                            <div class="py-1.5">
                                <p class="px-4 py-1 text-[11px] font-semibold tracking-wide text-ink-muted uppercase" x-text="group.label"></p>
                                <ul>
                                    <template x-for="item in group.items" :key="item.href">
                                        <li>
                                            <button
                                                type="button"
                                                x-on:click="go(item)"
                                                class="flex w-full flex-col items-start px-4 py-2 text-left transition hover:bg-panel-soft"
                                            >
                                                <span class="text-sm font-medium text-ink" x-text="item.title"></span>
                                                <span class="text-xs text-ink-soft" x-text="item.subtitle"></span>
                                            </button>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Notifications --}}
        <div
            x-data="{
                open: false,
                alerts: [],
                loading: false,
                failed: false,
                async load() {
                    this.loading = true; this.failed = false;
                    try {
                        const response = await fetch('/notifications', { headers: { Accept: 'application/json' } });
                        if (!response.ok) { throw new Error('failed'); }
                        const payload = await response.json();
                        this.alerts = payload.notifications ?? [];
                    } catch (error) {
                        this.failed = true;
                    } finally {
                        this.loading = false;
                    }
                },
            }"
            x-on:click.outside="open = false"
            x-on:keydown.escape.window="open = false"
            class="relative"
        >
            <button
                type="button"
                x-on:click="open = ! open; if (open && alerts.length === 0) load()"
                :aria-expanded="open ? 'true' : 'false'"
                aria-label="Notifikasi"
                class="relative inline-flex h-10 w-10 items-center justify-center rounded-control text-ink-soft transition hover:bg-panel-soft hover:text-ink sm:h-9 sm:w-9"
            >
                <x-icon name="bell" class="h-5 w-5" />
                @if (($notificationCount ?? 0) > 0)
                    <span class="absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-semibold text-white tabular-nums">
                        {{ $notificationCount > 99 ? '99+' : $notificationCount }}
                    </span>
                @endif
            </button>

            <div
                x-show="open"
                x-transition
                x-cloak
                class="absolute right-0 z-40 mt-2 w-[min(20rem,calc(100vw-2rem))] overflow-hidden rounded-card border border-line bg-panel shadow-pop"
            >
                <div class="border-b border-line px-4 py-3">
                    <p class="text-sm font-semibold text-ink">Notifikasi &amp; Peringatan</p>
                </div>

                <div class="scroll-slim max-h-80 overflow-y-auto">
                    <p x-show="loading && alerts.length === 0" class="px-4 py-6 text-center text-sm text-ink-soft">Memuat…</p>
                    <p x-show="failed" class="px-4 py-6 text-center text-sm text-rose-600 dark:text-rose-400">Gagal memuat notifikasi.</p>
                    <p x-show="! loading && ! failed && alerts.length === 0" class="px-4 py-6 text-center text-sm text-ink-soft">
                        Tidak ada peringatan yang perlu ditangani.
                    </p>

                    <ul class="divide-y divide-line">
                        <template x-for="alert in alerts" :key="alert.key">
                            <li>
                                <a :href="alert.href ?? '#'" class="block px-4 py-3 transition hover:bg-panel-soft">
                                    <p class="text-sm font-medium text-ink" x-text="alert.title"></p>
                                    <p class="mt-0.5 text-xs text-ink-soft" x-text="alert.description"></p>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>

        <x-theme-switcher />

        {{-- User menu --}}
        <div
            x-data="{ open: false }"
            x-on:click.outside="open = false"
            x-on:keydown.escape.window="open = false"
            class="relative"
        >
            <button
                type="button"
                x-on:click="open = ! open"
                :aria-expanded="open ? 'true' : 'false'"
                aria-label="Menu akun"
                class="flex items-center gap-2 rounded-control px-1.5 py-1 transition hover:bg-panel-soft"
            >
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-navy-800 text-xs font-semibold text-white">{{ $initials }}</span>
                <span class="hidden text-left sm:block">
                    <span class="block text-xs font-medium text-ink">{{ $user?->name }}</span>
                    <span class="block text-[11px] text-ink-muted">{{ $user?->roles->pluck('name')->implode(', ') }}</span>
                </span>
                <x-icon name="chevronDown" class="hidden h-3.5 w-3.5 text-ink-muted sm:block" />
            </button>

            <div
                x-show="open"
                x-transition
                x-cloak
                class="absolute right-0 z-40 mt-2 w-[min(15rem,calc(100vw-2rem))] overflow-hidden rounded-card border border-line bg-panel shadow-pop"
            >
                <div class="border-b border-line px-4 py-3">
                    <p class="text-sm font-semibold text-ink">{{ $user?->name }}</p>
                    <p class="text-xs text-ink-soft">{{ $user?->email }}</p>
                    <p class="mt-1 text-[11px] text-brand-600 dark:text-brand-300">{{ $user?->roles->pluck('name')->implode(', ') }}</p>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="p-1">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 rounded-control px-3 py-2 text-sm text-rose-600 transition hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
                        <x-icon name="logout" class="h-4 w-4" />
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
