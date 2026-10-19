@extends('layouts.app')

@section('title', 'Pesanan '.$order['number'])

@section('content')
    @php
        use App\Support\Format;
    @endphp

    <x-page-header
        :title="'Pesanan '.$order['number']"
        :subtitle="($order['school'] ?? '').' · '.Format::date($order['order_date'])"
        icon="cart"
    >
        <x-slot:actions>
            @if ($can['update'])
                <x-button :href="route('orders.edit', $order['id'])" variant="secondary">Ubah</x-button>
            @endif

            @if ($can['confirm'])
                <form method="POST" action="{{ route('orders.confirm', $order['id']) }}" x-data x-on:submit="if (! confirm('Konfirmasi pesanan ini dan cek ketersediaan stok?')) $event.preventDefault()">
                    @csrf
                    <x-button type="submit">Konfirmasi</x-button>
                </form>
            @endif

            @if ($can['ship'])
                <form method="POST" action="{{ route('pickings.store') }}">
                    @csrf
                    <input type="hidden" name="order_id" value="{{ $order['id'] }}" />
                    <x-button type="submit" variant="secondary">Buat Picking</x-button>
                </form>
                <x-button :href="route('shipments.create', ['order_id' => $order['id']])" variant="secondary">Buat Pengiriman</x-button>
            @endif

            @if ($can['invoice'])
                <form method="POST" action="{{ route('invoices.store') }}">
                    @csrf
                    <input type="hidden" name="order_id" value="{{ $order['id'] }}" />
                    <x-button type="submit" variant="secondary">Terbitkan Invoice</x-button>
                </form>
            @endif

            @if ($can['cancel'])
                <form method="POST" action="{{ route('orders.cancel', $order['id']) }}" x-data x-on:submit="if (! confirm('Batalkan pesanan ini? Reservasi stok akan dilepas.')) $event.preventDefault()">
                    @csrf
                    <x-button type="submit" variant="danger">Batalkan</x-button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <x-card-header title="Ringkasan" icon="clipboard" />
            <dl class="grid gap-3 p-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-ink-soft">Status</dt>
                    <dd class="mt-0.5"><x-status-badge :value="$order['status']" :label="$order['status_label']" /></dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-soft">Sumber</dt>
                    <dd class="text-sm text-ink">{{ $order['source'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-soft">Sekolah</dt>
                    <dd class="text-sm text-ink">{{ $order['school'] ?? '-' }} {{ $order['npsn'] ? '('.$order['npsn'].')' : '' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-soft">Operator</dt>
                    <dd class="text-sm text-ink">{{ $order['operator'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-soft">Referensi Eksternal</dt>
                    <dd class="text-sm text-ink">{{ $order['external_reference'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-soft">Catatan</dt>
                    <dd class="text-sm text-ink">{{ $order['notes'] ?? '-' }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card>
            <x-card-header title="Nilai" icon="wallet" />
            <dl class="divide-y divide-line text-sm">
                <div class="flex justify-between px-4 py-2"><dt class="text-ink-soft">Subtotal</dt><dd class="text-ink tabular-nums">{{ Format::idr($order['subtotal']) }}</dd></div>
                <div class="flex justify-between px-4 py-2"><dt class="text-ink-soft">Diskon</dt><dd class="text-ink tabular-nums">{{ Format::idr($order['discount_amount']) }}</dd></div>
                <div class="flex justify-between px-4 py-2"><dt class="text-ink-soft">Pajak</dt><dd class="text-ink tabular-nums">{{ Format::idr($order['tax_amount']) }}</dd></div>
                <div class="flex justify-between px-4 py-2 font-semibold"><dt class="text-ink">Total</dt><dd class="text-ink tabular-nums">{{ Format::idr($order['total_amount']) }}</dd></div>
            </dl>
        </x-card>
    </div>

    <div class="mt-4 grid gap-4">
        <x-card>
            <x-card-header title="Item" icon="boxes" />
            <x-data-table
                :rows="collect($order['items'])->map(fn ($item) => ['id' => $item['id']] + $item)->all()"
                empty="Pesanan ini belum memiliki item."
                empty-icon="boxes"
                :columns="[
                    ['label' => 'Barang', 'view' => 'orders.partials.item-book'],
                    ['key' => 'quantity', 'label' => 'Dipesan', 'align' => 'right', 'class' => 'tabular-nums'],
                    ['key' => 'reserved_quantity', 'label' => 'Ready', 'align' => 'right', 'class' => 'tabular-nums'],
                    ['key' => 'shipped_quantity', 'label' => 'Terkirim', 'align' => 'right', 'class' => 'tabular-nums'],
                    ['key' => 'backorder_quantity', 'label' => 'Back Order', 'align' => 'right', 'class' => 'tabular-nums'],
                    ['key' => 'subtotal', 'label' => 'Subtotal', 'type' => 'currency', 'align' => 'right'],
                    ['key' => 'status', 'label' => 'Status', 'view' => 'components.status-label'],
                ]"
            />
        </x-card>

        <div class="grid gap-4 lg:grid-cols-3">
            <x-card>
                <x-card-header title="Pengiriman" icon="truck" />
                @if (count($order['shipments']) === 0)
                    <x-empty-state message="Belum ada pengiriman." icon="truck" />
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($order['shipments'] as $shipment)
                            <li class="flex items-center justify-between px-4 py-2 text-sm">
                                <a href="{{ route('shipments.show', $shipment['id']) }}" class="text-ink hover:text-brand-600 hover:underline dark:hover:text-brand-300">{{ $shipment['number'] }}</a>
                                <span class="flex items-center gap-2">
                                    <span class="text-xs text-ink-soft">{{ Format::number($shipment['quantity']) }} barang</span>
                                    <x-status-badge :value="$shipment['status']" :label="$shipment['status_label']" />
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card>
                <x-card-header title="Invoice" icon="receipt" />
                @if (count($order['invoices']) === 0)
                    <x-empty-state message="Belum ada invoice." icon="receipt" />
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($order['invoices'] as $invoice)
                            <li class="flex items-center justify-between px-4 py-2 text-sm">
                                <a href="{{ route('invoices.show', $invoice['id']) }}" class="text-ink hover:text-brand-600 hover:underline dark:hover:text-brand-300">{{ $invoice['number'] }}</a>
                                <span class="text-ink tabular-nums">{{ Format::idr($invoice['total_amount']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card>
                <x-card-header title="Back Order" icon="clock" />
                @if (count($order['backorders']) === 0)
                    <x-empty-state message="Tidak ada back order." icon="checkCircle" />
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($order['backorders'] as $backorder)
                            <li class="flex items-center justify-between px-4 py-2 text-sm">
                                <span class="truncate pr-2 text-ink-soft">{{ $backorder['product'] }}</span>
                                <span class="flex items-center gap-2">
                                    <span class="text-xs text-ink-muted tabular-nums">{{ $backorder['fulfilled_quantity'] }}/{{ $backorder['quantity'] }}</span>
                                    <x-status-badge :value="$backorder['status']" :label="$backorder['status_label']" />
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    </div>
@endsection
