@extends('layouts.app')

@section('title', 'Penerimaan '.$receipt['number'])

@section('content')
    @php
        use App\Support\Format;
    @endphp

    <x-page-header
        :title="'Penerimaan '.$receipt['number']"
        :subtitle="($receipt['publisher'] ?? '').' · '.Format::date($receipt['receipt_date'])"
        icon="inbox"
    >
        <x-slot:actions>
            <x-button :href="route('receipts.index')" variant="secondary">Kembali</x-button>

            @if ($can['confirm'])
                <form method="POST" action="{{ route('receipts.confirm', $receipt['id']) }}" x-data x-on:submit="if (! confirm('Konfirmasi penerimaan ini? Stok akan bertambah dan back order dialokasikan.')) $event.preventDefault()">
                    @csrf
                    <x-button type="submit">Konfirmasi</x-button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <x-card-header title="Item" icon="boxes" />
            <x-data-table
                empty="Penerimaan ini belum memiliki item."
                empty-icon="boxes"
                :rows="collect($receipt['items'])->map(fn ($item, $index) => ['id' => $index] + $item)->all()"
                :columns="[
                    ['key' => 'code', 'label' => 'Kode'],
                    ['key' => 'product', 'label' => 'Barang'],
                    ['key' => 'quantity', 'label' => 'Qty', 'align' => 'right', 'class' => 'tabular-nums'],
                    ['key' => 'unit_cost', 'label' => 'Harga Pokok', 'type' => 'currency', 'align' => 'right'],
                ]"
            />
        </x-card>

        <x-card>
            <x-card-header title="Detail" icon="inbox" />
            <dl class="space-y-3 p-4 text-sm">
                <div>
                    <dt class="text-xs text-ink-soft">Status</dt>
                    <dd class="mt-0.5"><x-status-badge :value="$receipt['status']" :label="$receipt['status_label']" /></dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-soft">Gudang</dt>
                    <dd class="text-ink">{{ $receipt['warehouse'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-soft">Dikonfirmasi oleh</dt>
                    <dd class="text-ink">{{ $receipt['confirmed_by'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-soft">Waktu konfirmasi</dt>
                    <dd class="text-ink">{{ $receipt['confirmed_at'] ? Format::dateTime($receipt['confirmed_at']) : '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-soft">Catatan</dt>
                    <dd class="text-ink">{{ $receipt['notes'] ?? '-' }}</dd>
                </div>
            </dl>
        </x-card>
    </div>
@endsection
