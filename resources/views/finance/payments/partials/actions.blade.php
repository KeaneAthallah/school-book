
<div class="flex justify-end gap-2">
    @if ($row['status'] !== 'reversed')
        @if (count($row['allocations'] ?? []) === 1)
            <a href="{{ route('payments.receipt', ['payment' => $row['id'], 'allocation' => $row['allocations'][0]['id']]) }}" target="_blank" class="text-xs font-medium text-brand-600 transition hover:underline dark:text-brand-300">Kwitansi</a>
        @elseif (count($row['allocations'] ?? []) > 1)
            <div class="relative" x-data="{ open: false }">
                <button type="button" x-on:click="open = ! open" class="text-xs font-medium text-brand-600 transition hover:underline dark:text-brand-300">Kwitansi</button>
                <div x-show="open" x-on:click.outside="open = false" x-cloak class="absolute right-0 z-20 mt-1 w-56 rounded-control border border-line bg-surface p-1 shadow-lg">
                    @foreach ($row['allocations'] as $allocation)
                        <a href="{{ route('payments.receipt', ['payment' => $row['id'], 'allocation' => $allocation['id']]) }}" target="_blank" class="block rounded px-2 py-1 text-xs text-ink transition hover:bg-canvas">
                            {{ $allocation['receipt_number'] ?? 'Invoice #'.$allocation['invoice_id'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    @if ($row['status'] === 'draft' && auth()->user()?->hasPermission('payments.manage'))
        <a href="{{ route('payments.edit', $row['id']) }}" class="text-xs font-medium text-brand-600 transition hover:underline dark:text-brand-300">Ubah</a>
    @endif

    @if ($row['status'] === 'draft' && auth()->user()?->hasPermission('payments.verify'))
        <form method="POST" action="{{ route('payments.verify', $row['id']) }}" x-data x-on:submit="if (! confirm('Verifikasi pembayaran ini?')) $event.preventDefault()">
            @csrf
            <button type="submit" class="text-xs font-medium text-emerald-700 transition hover:underline dark:text-emerald-400">Verifikasi</button>
        </form>
    @endif

    @if ($row['status'] === 'verified' && auth()->user()?->hasPermission('payments.verify'))
        <form method="POST" action="{{ route('payments.reverse', $row['id']) }}" x-data x-on:submit="if (! confirm('Balik pembayaran ini? Invoice akan dihitung ulang.')) $event.preventDefault()">
            @csrf
            <button type="submit" class="text-xs font-medium text-rose-600 transition hover:underline dark:text-rose-400">Balik</button>
        </form>
    @endif
</div>
