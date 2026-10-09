
@if (auth()->user()?->hasPermission('integrations.manage'))
    <form
        method="POST"
        action="{{ route('integrations.sync', $row['id']) }}"
        x-data
        x-on:submit="if (! confirm('Jalankan sinkronisasi untuk {{ $row['name'] }}?')) $event.preventDefault()"
    >
        @csrf
        <button type="submit" class="text-xs font-medium text-ink-soft transition hover:text-brand-600 hover:underline dark:hover:text-brand-300">Sinkronkan</button>
    </form>
@else
    <span class="text-ink-muted">-</span>
@endif
