
@php
    $user = auth()->user();
    $status = $row['status'];
    $attachments = $row['attachments'] ?? [];
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
            <form method="POST" action="{{ route('rebates.pay', $row['id']) }}" enctype="multipart/form-data" class="absolute right-0 z-20 mt-1 w-72 space-y-2 rounded-card border border-line bg-panel p-3 shadow-pop">
                @csrf
                <x-input name="reference" required placeholder="Referensi pembayaran" />
                <div x-data="photoPreview" class="space-y-1.5">
                    <input type="file" name="proof_photo" accept="image/jpeg,image/png,image/webp" required
                        aria-label="Bukti foto pembayaran rebate"
                        class="w-full rounded-control border border-line bg-canvas px-2.5 py-1.5 text-xs text-ink file:mr-1.5 file:rounded-control file:border-0 file:bg-brand-600 file:px-2 file:py-1 file:text-xs file:font-medium file:text-white"
                        @change="pick($event)">
                    <img x-show="preview" :src="preview" alt="Pratinjau bukti foto pembayaran rebate" class="h-20 w-20 rounded-control border border-line object-cover sm:h-24 sm:w-24" />
                    @error('proof_photo')
                        <p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
                <x-button type="submit" size="sm" class="w-full">Simpan</x-button>
            </form>
        </details>
    @endif

    @if ($status === 'paid' && count($attachments) > 0)
        <details class="relative">
            <summary class="cursor-pointer text-xs font-medium text-emerald-700 transition hover:underline dark:text-emerald-400">Bukti ({{ count($attachments) }})</summary>
            <div class="absolute right-0 z-20 mt-1 w-72 rounded-card border border-line bg-panel p-3 shadow-pop">
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($attachments as $attachment)
                        <figure class="group relative overflow-hidden rounded-control border border-line bg-canvas">
                            <a href="{{ $attachment['url'] }}" target="_blank" rel="noreferrer">
                                <img src="{{ $attachment['url'] }}" alt="{{ $attachment['name'] }}" loading="lazy" class="h-24 w-full object-cover" />
                            </a>
                            <figcaption class="truncate px-1.5 py-1 text-[10px] text-ink-soft">{{ $attachment['name'] }}</figcaption>
                            @if ($canPay)
                                <form method="POST" action="{{ route('attachments.destroy', $attachment['id']) }}" class="absolute right-1 top-1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" aria-label="Hapus bukti foto pembayaran rebate"
                                        class="rounded-card bg-rose-600/90 p-1 text-white opacity-0 transition hover:bg-rose-600 group-hover:opacity-100">
                                        <x-icon name="close" class="h-3.5 w-3.5" />
                                    </button>
                                </form>
                            @endif
                        </figure>
                    @endforeach
                </div>
            </div>
        </details>
    @endif
</div>
