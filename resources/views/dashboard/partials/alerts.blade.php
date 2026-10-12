{{--
    Operational alerts. Expects: $alerts
--}}
@if (count($alerts) === 0)
    <x-empty-state message="Tidak ada peringatan yang perlu ditangani." icon="checkCircle" />
@else
    <ul class="divide-y divide-line">
        @foreach ($alerts as $alert)
            @php
                $tone = [
                    'danger' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300',
                    'warning' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300',
                    'info' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300',
                ][$alert['tone']] ?? 'bg-panel-soft text-ink-muted';
                $icon = ['danger' => 'alert', 'warning' => 'clock'][$alert['tone']] ?? 'bell';
            @endphp

            <li>
                <a href="{{ $alert['href'] ?? '#' }}" class="flex items-start gap-3 px-4 py-3 transition hover:bg-panel-soft">
                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-control {{ $tone }}">
                        <x-icon :name="$icon" class="h-4 w-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-ink">{{ $alert['title'] }}</p>
                        <p class="mt-0.5 text-xs text-ink-soft">{{ $alert['description'] }}</p>
                    </div>
                    @if ($alert['time'])
                        <span class="hidden text-[11px] whitespace-nowrap text-ink-muted sm:block">{{ \App\Support\Format::dateTime($alert['time']) }}</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
@endif
