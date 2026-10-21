@extends('layouts.app')
@section('title', 'Detail Pesanan Penerbit')
@section('content')
    <x-page-header
        title="Pesanan ke Penerbit #{{ $order->id }}"
        subtitle="{{ $order->publisher?->name ?? '-' }}"
        icon="fileText"
    >
        <x-slot:actions>
            <x-button :href="route('backorders.orderPrint', $order->id)" variant="secondary" target="_blank">Cetak</x-button>
            <x-button :href="route('backorders.index')" variant="secondary">Kembali</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <x-card-header title="Informasi" icon="fileText" />
            <dl class="space-y-3 p-4 text-sm">
                <div><dt class="text-xs text-ink-soft">Penerbit</dt><dd class="text-ink">{{ $order->publisher?->name ?? '-' }}</dd></div>
                <div><dt class="text-xs text-ink-soft">No. Pesanan Penerbit</dt><dd class="text-ink">{{ $order->number ?? '-' }}</dd></div>
                <div><dt class="text-xs text-ink-soft">Status</dt><dd class="text-ink">{{ $order->status === 'received' ? 'Diterima' : 'Menunggu' }}</dd></div>
                <div><dt class="text-xs text-ink-soft">Tanggal Order</dt><dd class="text-ink">{{ $order->ordered_at?->format('d/m/Y H:i') ?? '-' }}</dd></div>
                <div><dt class="text-xs text-ink-soft">Catatan</dt><dd class="text-ink">{{ $order->note ?? '-' }}</dd></div>
                <div><dt class="text-xs text-ink-soft">Dibuat oleh</dt><dd class="text-ink">{{ $order->creator?->name ?? '-' }}</dd></div>
            </dl>
        </x-card>
        <x-card class="lg:col-span-2">
            <x-card-header title="Item Back Order" icon="boxes" />
            <x-data-table
                empty="Tidak ada item."
                :rows="$order->items->map(fn ($i) => ['id' => $i->id, 'product' => $i->backorder?->product?->title, 'order_number' => $i->backorder?->orderItem?->order?->number, 'school' => $i->backorder?->orderItem?->order?->school?->name, 'quantity' => $i->quantity])->all()"
                :columns="[
                    ['key' => 'product', 'label' => 'Barang'],
                    ['key' => 'order_number', 'label' => 'No. Pesanan'],
                    ['key' => 'school', 'label' => 'Sekolah'],
                    ['key' => 'quantity', 'label' => 'Qty', 'align' => 'right', 'class' => 'tabular-nums'],
                ]"
            />
        </x-card>
    </div>
@endsection