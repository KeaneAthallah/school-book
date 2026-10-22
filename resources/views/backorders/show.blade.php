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

    <div class="space-y-4">
        <x-card>
            <x-card-header title="Informasi" icon="fileText" />
            <dl class="grid gap-3 p-4 text-sm sm:grid-cols-3 lg:grid-cols-6">
                <div><dt class="text-xs text-ink-soft">Penerbit</dt><dd class="text-ink">{{ $order->publisher?->name ?? '-' }}</dd></div>
                <div><dt class="text-xs text-ink-soft">No. Pesanan Penerbit</dt><dd class="text-ink">{{ $order->number ?? '-' }}</dd></div>
                <div><dt class="text-xs text-ink-soft">Status</dt><dd class="text-ink">{{ $order->items->isNotEmpty() && $order->items->every(fn ($i) => $i->backorder?->status === \App\Enums\BackorderStatus::Fulfilled) ? 'Diterima' : ($order->items->contains(fn ($i) => $i->backorder?->status === \App\Enums\BackorderStatus::Fulfilled || $i->backorder?->status === \App\Enums\BackorderStatus::Partial) ? 'Sebagian Terpenuhi' : ($order->status === 'received' ? 'Diterima' : 'Menunggu')) }}</dd></div>
                <div><dt class="text-xs text-ink-soft">Tanggal Order</dt><dd class="text-ink">{{ $order->ordered_at?->format('d/m/Y H:i') ?? '-' }}</dd></div>
                <div><dt class="text-xs text-ink-soft">Catatan</dt><dd class="text-ink">{{ $order->note ?? '-' }}</dd></div>
                <div><dt class="text-xs text-ink-soft">Dibuat oleh</dt><dd class="text-ink">{{ $order->creator?->name ?? '-' }}</dd></div>
            </dl>
        </x-card>
        <x-card>
            <x-card-header title="Item Back Order" icon="boxes" />
            <x-data-table
                empty="Tidak ada item."
                :rows="$order->items->map(fn ($i) => [
                    'id' => $i->id,
                    'product' => $i->backorder?->product?->title,
                    'order_number' => $i->backorder?->orderItem?->order?->number,
                    'school' => $i->backorder?->orderItem?->order?->school?->name,
                    'ordered' => $i->quantity,
                    'received' => $i->backorder?->fulfilled_quantity ?? 0,
                    'outstanding' => max(0, $i->quantity - ($i->backorder?->fulfilled_quantity ?? 0)),
                    'order_date' => $i->backorder?->orderItem?->order?->order_date?->format('d/m/Y'),
                ])->all()"
                :columns="[
                    ['key' => 'product', 'label' => 'Barang'],
                    ['key' => 'order_number', 'label' => 'No. Pesanan'],
                    ['key' => 'school', 'label' => 'Sekolah'],
                    ['key' => 'order_date', 'label' => 'Tgl Pesanan'],
                    ['key' => 'ordered', 'label' => 'Jumlah Dipesan', 'align' => 'right', 'class' => 'tabular-nums'],
                    ['key' => 'received', 'label' => 'Jumlah Diterima', 'align' => 'right', 'class' => 'tabular-nums'],
                    ['key' => 'outstanding', 'label' => 'Sisa', 'align' => 'right', 'class' => 'tabular-nums font-semibold'],
                ]"
            />
        </x-card>
    </div>
@endsection