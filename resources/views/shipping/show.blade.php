@extends('layouts.app')

@section('title', 'Pengiriman '.$shipment['number'])

@section('content')
    @php
        use App\Support\Format;

        $detail = [
            'Pesanan' => $shipment['order'] ?? '-',
            'Gudang' => $shipment['warehouse'] ?? '-',
            'Kurir / Metode' => trim(($shipment['courier'] ?? '').' / '.($shipment['shipping_method'] ?? ''), ' /') ?: '-',
            'No. Resi' => $shipment['tracking_number'] ?? '-',
            'Penerima' => $shipment['recipient_name'] ?? '-',
            'Alamat' => $shipment['address'] ?? '-',
            'Tgl Kirim' => $shipment['ship_date'] ? Format::date($shipment['ship_date']) : '-',
            'Dikonfirmasi' => $shipment['confirmed_at'] ? Format::dateTime($shipment['confirmed_at']) : '-',
        ];
    @endphp

    <x-page-header
        :title="'Pengiriman '.$shipment['number']"
        :subtitle="'Surat Jalan '.($shipment['delivery_note_number'] ?? '-').' · '.($shipment['school'] ?? '')"
    >
        <x-slot:actions>
            @if ($shipment['delivery_note_number'])
                <x-button :href="route('shipments.print', $shipment['id'])" variant="secondary" target="_blank" rel="noreferrer">
                    <x-icon name="printer" class="h-4 w-4" />
                    Cetak Surat Jalan
                </x-button>
            @endif

            @if ($can['confirm'])
                <form method="POST" action="{{ route('shipments.confirm', $shipment['id']) }}" x-data x-on:submit="if (! confirm('Konfirmasi pengiriman? Stok akan berkurang.')) $event.preventDefault()">
                    @csrf
                    <x-button type="submit">Konfirmasi Kirim</x-button>
                </form>
            @endif

            @if ($can['deliver'])
                <form method="POST" action="{{ route('shipments.deliver', $shipment['id']) }}">
                    @csrf
                    <x-button type="submit" variant="secondary">Tandai Diterima</x-button>
                </form>
            @endif

            @if ($can['invoice'])
                <form method="POST" action="{{ route('invoices.store') }}">
                    @csrf
                    <input type="hidden" name="shipment_id" value="{{ $shipment['id'] }}" />
                    <x-button type="submit" variant="secondary">Terbitkan Invoice</x-button>
                </form>
            @endif

            <x-button :href="route('shipments.index')" variant="ghost">Kembali</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <x-card-header title="Item" icon="boxes" />
            <x-data-table
                empty="Pengiriman ini belum memiliki item."
                empty-icon="boxes"
                :rows="collect($shipment['items'])->map(fn ($item, $index) => ['id' => $index] + $item)->all()"
                :columns="[
                    ['key' => 'code', 'label' => 'Kode'],
                    ['key' => 'book', 'label' => 'Buku'],
                    ['key' => 'quantity', 'label' => 'Qty', 'align' => 'right', 'class' => 'tabular-nums'],
                ]"
            />
        </x-card>

        <x-card>
            <x-card-header title="Detail" icon="truck">
                <x-slot:action>
                    <x-status-badge :value="$shipment['status']" :label="$shipment['status_label']" />
                </x-slot:action>
            </x-card-header>
            <dl class="space-y-3 p-4 text-sm">
                @foreach ($detail as $label => $value)
                    <div>
                        <dt class="text-xs text-ink-soft">{{ $label }}</dt>
                        <dd class="text-ink">{{ $value }}</dd>
                    </div>
                @endforeach
                <div>
                    <dt class="text-xs text-ink-soft">Catatan</dt>
                    <dd class="text-ink">{{ $shipment['notes'] ?? '-' }}</dd>
                </div>
            </dl>
        </x-card>
    </div>
@endsection
