@extends('layouts.app')

@section('title', 'Invoice '.$invoice['number'])

@section('content')
    @php
        use App\Support\Format;
    @endphp

    <x-page-header
        :title="'Invoice '.$invoice['number']"
        :subtitle="($invoice['school'] ?? '').' · Jatuh tempo '.Format::date($invoice['due_date'])"
    >
        <x-slot:actions>
            <x-button :href="route('invoices.print', $invoice['id'])" variant="secondary" target="_blank" rel="noreferrer">
                <x-icon name="printer" class="h-4 w-4" />
                Cetak
            </x-button>

            @if ($can['cancel'])
                <form method="POST" action="{{ route('invoices.cancel', $invoice['id']) }}" x-data x-on:submit="if (! confirm('Batalkan invoice ini?')) $event.preventDefault()">
                    @csrf
                    <x-button type="submit" variant="danger">Batalkan</x-button>
                </form>
            @endif

            <x-button :href="route('invoices.index')" variant="ghost">Kembali</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <x-card-header title="Item" icon="receipt" />
            <x-data-table
                empty="Invoice ini belum memiliki item."
                empty-icon="receipt"
                :rows="collect($invoice['items'])->map(fn ($item, $index) => ['id' => $index] + $item)->all()"
                :columns="[
                    ['key' => 'description', 'label' => 'Deskripsi'],
                    ['key' => 'quantity', 'label' => 'Qty', 'align' => 'right', 'class' => 'tabular-nums'],
                    ['key' => 'unit_price', 'label' => 'Harga', 'type' => 'currency', 'align' => 'right'],
                    ['key' => 'subtotal', 'label' => 'Subtotal', 'type' => 'currency', 'align' => 'right'],
                ]"
            />
        </x-card>

        <x-card>
            <x-card-header title="Total" icon="wallet" />
            <dl class="divide-y divide-line text-sm">
                <div class="flex justify-between px-4 py-2"><dt class="text-ink-soft">Subtotal</dt><dd class="text-ink tabular-nums">{{ Format::idr($invoice['subtotal']) }}</dd></div>
                <div class="flex justify-between px-4 py-2"><dt class="text-ink-soft">Diskon</dt><dd class="text-ink tabular-nums">{{ Format::idr($invoice['discount_amount']) }}</dd></div>
                <div class="flex justify-between px-4 py-2"><dt class="text-ink-soft">Pajak</dt><dd class="text-ink tabular-nums">{{ Format::idr($invoice['tax_amount']) }}</dd></div>
                <div class="flex justify-between px-4 py-2 font-semibold"><dt class="text-ink">Total</dt><dd class="text-ink tabular-nums">{{ Format::idr($invoice['total_amount']) }}</dd></div>
                <div class="flex justify-between px-4 py-2"><dt class="text-ink-soft">Dibayar</dt><dd class="text-ink tabular-nums">{{ Format::idr($invoice['paid_amount']) }}</dd></div>
                <div class="flex justify-between px-4 py-2 font-semibold"><dt class="text-ink">Sisa</dt><dd class="text-rose-600 tabular-nums dark:text-rose-400">{{ Format::idr($invoice['outstanding_amount']) }}</dd></div>
                <div class="flex items-center justify-between px-4 py-2">
                    <dt class="text-ink-soft">Status Bayar</dt>
                    <dd><x-status-badge :value="$invoice['payment_status']" :label="$invoice['payment_status_label']" /></dd>
                </div>
            </dl>
        </x-card>
    </div>

    <x-card class="mt-4">
        <x-card-header title="Referensi & Pembayaran" icon="fileText" />
        <div class="grid gap-4 p-4 sm:grid-cols-2">
            <dl class="space-y-2 text-sm">
                <div><dt class="text-xs text-ink-soft">Pesanan</dt><dd class="text-ink">{{ $invoice['order'] ?? '-' }}</dd></div>
                <div><dt class="text-xs text-ink-soft">Pengiriman</dt><dd class="text-ink">{{ $invoice['shipment'] ?? '-' }}</dd></div>
                <div>
                    <dt class="text-xs text-ink-soft">Status Dokumen</dt>
                    <dd class="mt-0.5"><x-status-badge :value="$invoice['status']" :label="$invoice['status_label']" /></dd>
                </div>
            </dl>

            <div>
                @if (count($invoice['payments']) === 0)
                    <p class="text-sm text-ink-soft">Belum ada pembayaran dialokasikan.</p>
                @else
                    <ul class="divide-y divide-line text-sm">
                        @foreach ($invoice['payments'] as $payment)
                            <li class="flex items-center justify-between py-2">
                                <span class="text-ink-soft">
                                    {{ $payment['number'] }}
                                    <span class="text-xs text-ink-muted">{{ Format::date($payment['date']) }}</span>
                                </span>
                                <span class="text-ink tabular-nums">{{ Format::idr($payment['amount']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </x-card>
@endsection
