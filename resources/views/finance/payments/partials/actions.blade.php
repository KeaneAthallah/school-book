
<div class="flex justify-end gap-2">
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
