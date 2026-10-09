@if ($paginator->hasPages())
    <nav
        role="navigation"
        aria-label="Navigasi halaman"
        class="flex flex-wrap items-center justify-between gap-2 border-t border-line px-4 py-3 text-sm"
    >
        <p class="text-xs text-ink-soft">
            Menampilkan <span class="tabular-nums">{{ $paginator->firstItem() ?? 0 }}</span>&ndash;<span class="tabular-nums">{{ $paginator->lastItem() ?? 0 }}</span>
            dari <span class="tabular-nums">{{ $paginator->total() }}</span>
        </p>

        <span class="flex flex-wrap gap-1">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="rounded-control border border-line px-2.5 py-1 text-xs text-ink-muted opacity-50">&laquo;</span>
            @else
                <a
                    href="{{ $paginator->previousPageUrl() }}"
                    rel="prev"
                    aria-label="Halaman sebelumnya"
                    class="rounded-control border border-line-strong bg-panel px-2.5 py-1 text-xs text-ink-soft transition hover:bg-panel-soft"
                >&laquo;</a>
            @endif

            {{-- Elements --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="rounded-control border border-line px-2.5 py-1 text-xs text-ink-muted">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="rounded-control border border-brand-600 bg-brand-600 px-2.5 py-1 text-xs font-medium text-white tabular-nums">{{ $page }}</span>
                        @else
                            <a
                                href="{{ $url }}"
                                class="rounded-control border border-line-strong bg-panel px-2.5 py-1 text-xs text-ink-soft transition hover:bg-panel-soft tabular-nums"
                            >{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a
                    href="{{ $paginator->nextPageUrl() }}"
                    rel="next"
                    aria-label="Halaman berikutnya"
                    class="rounded-control border border-line-strong bg-panel px-2.5 py-1 text-xs text-ink-soft transition hover:bg-panel-soft"
                >&raquo;</a>
            @else
                <span class="rounded-control border border-line px-2.5 py-1 text-xs text-ink-muted opacity-50">&raquo;</span>
            @endif
        </span>
    </nav>
@endif
