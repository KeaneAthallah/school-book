{{--
    Recent audit activity. Expects: $activity
--}}
@if (count($activity) === 0)
    <x-empty-state message="Belum ada aktivitas tercatat." icon="clock" />
@else
    <ul class="divide-y divide-line">
        @foreach ($activity as $entry)
            <li class="px-4 py-3">
                <p class="text-sm text-ink">{{ $entry['description'] ?? $entry['action'] }}</p>
                <p class="mt-0.5 text-[11px] text-ink-muted">
                    {{ $entry['user'] ?? 'Sistem' }} · {{ \App\Support\Format::dateTime($entry['time']) }}
                </p>
            </li>
        @endforeach
    </ul>
@endif
