
@php
    $user = auth()->user();
    $status = $row['status'];
    $canApprove = $user?->hasPermission('rebates.approve') ?? false;
    $canPay = $user?->hasPermission('rebates.pay') ?? false;
@endphp

<div class="flex justify-end gap-3">
    @if ($canApprove && in_array($status, ['draft', 'submitted', 'pending_approval'], true))
        <form method="POST" action="{{ route('rebates.approve', $row['id']) }}" x-data x-on:submit="if (! confirm('Setujui rebate ini?')) $event.preventDefault()">
            @csrf
            <button type="submit" class="text-xs font-medium text-emerald-700 transition hover:underline dark:text-emerald-400">Setujui</button>
        </form>
    @endif

    @if ($canApprove && ! in_array($status, ['paid', 'rejected'], true))
        <details class="relative">
            <summary class="cursor-pointer text-xs font-medium text-rose-600 dark:text-rose-400">Tolak</summary>
            <form method="POST" action="{{ route('rebates.reject', $row['id']) }}" class="absolute right-0 z-20 mt-1 w-56 space-y-2 rounded-card border border-line bg-panel p-3 shadow-pop">
                @csrf
                <x-input name="reason" required placeholder="Alasan penolakan" />
                <x-button type="submit" variant="danger" size="sm" class="w-full">Kirim</x-button>
            </form>
        </details>
    @endif

    @if ($canPay && $status === 'approved')
        <details class="relative">
            <summary class="cursor-pointer text-xs font-medium text-ink-soft">Bayar</summary>
            <form method="POST" action="{{ route('rebates.pay', $row['id']) }}" class="absolute right-0 z-20 mt-1 w-56 space-y-2 rounded-card border border-line bg-panel p-3 shadow-pop">
                @csrf
                <x-input name="reference" required placeholder="Referensi pembayaran" />
                <x-button type="submit" size="sm" class="w-full">Simpan</x-button>
            </form>
        </details>
    @endif
</div>
