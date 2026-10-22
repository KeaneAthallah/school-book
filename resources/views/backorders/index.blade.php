@extends('layouts.app')
@section('title', 'Back Order')
@section('content')
    <x-page-header title="Back Order" subtitle="Daftar kekurangan stok dan pemesanan ke penerbit." icon="boxes" />

    <x-card class="mb-4">
        <x-card-header title="1. Daftar Back Order" icon="boxes" subtitle="Semua kekurangan stok dari nota pesanan. Kolom Sisa = jumlah yang masih harus dipenuhi." />
        <x-data-table
            empty="Belum ada back order."
            :rows="$backorders"
            :columns="[
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'product', 'label' => 'Barang'],
                ['key' => 'order_number', 'label' => 'No. Pesanan'],
                ['key' => 'school', 'label' => 'Sekolah'],
                ['key' => 'quantity', 'label' => 'Qty', 'align' => 'right', 'class' => 'tabular-nums'],
                ['key' => 'fulfilled_quantity', 'label' => 'Terpenuhi', 'align' => 'right', 'class' => 'tabular-nums'],
                ['key' => 'outstanding_quantity', 'label' => 'Sisa', 'align' => 'right', 'class' => 'tabular-nums font-semibold'],
                ['key' => 'status', 'label' => 'Status', 'view' => 'components.status-label'],
            ]"
        />
    </x-card>
    <div class="mt-3 mb-4">{{ $backorders->links() }}</div>

    <x-card class="mb-4">
        <x-card-header title="2. Pesan ke Penerbit" icon="fileText" subtitle="Centang back order yang mau dipesan, atur qty, pilih penerbit, lalu Simpan. Bisa beberapa sekaligus." />
        @if (empty($openBackorders))
            <div class="p-4 text-sm text-ink-soft">Tidak ada back order yang menunggu pemesanan.</div>
        @else
            <form method="POST" action="{{ route('backorders.orderStore') }}" class="space-y-3 p-4">
                @csrf
                <div class="grid gap-2 sm:grid-cols-3">
                    <x-field label="Penerbit">
                        <x-select name="publisher_id">
                            @foreach ($publishers as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                    <x-field label="No. Pesanan Penerbit (opsional)">
                        <x-input type="text" name="number" placeholder="opsional" />
                    </x-field>
                    <x-field label="Catatan (opsional)">
                        <x-input type="text" name="note" placeholder="opsional" />
                    </x-field>
                </div>
                <div class="scroll-slim overflow-x-auto">
                    <table class="w-full border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-line bg-panel-soft text-xs font-semibold tracking-wide text-ink-soft uppercase">
                                <th class="px-3 py-2.5 text-center">Pilih</th>
                                <th class="px-3 py-2.5">Barang</th>
                                <th class="px-3 py-2.5">No. Pesanan</th>
                                <th class="px-3 py-2.5">Sekolah</th>
                                <th class="px-3 py-2.5 text-right">Sisa</th>
                                <th class="px-3 py-2.5 text-right">Qty Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($openBackorders as $b)
                                <tr class="border-b border-line last:border-0 hover:bg-panel-soft">
                                    <td class="px-3 py-2.5 text-center">
                                        <input type="checkbox" name="selected[]" value="{{ $b['id'] }}" class="rounded border-line" />
                                    </td>
                                    <td class="px-3 py-2.5">{{ $b['product'] }}</td>
                                    <td class="px-3 py-2.5">{{ $b['order_number'] }}</td>
                                    <td class="px-3 py-2.5">{{ $b['school'] }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums font-semibold">{{ $b['outstanding'] }}</td>
                                    <td class="px-3 py-2.5">
                                        <input type="number" name="items[{{ $b['id'] }}]" value="{{ $b['outstanding'] }}" min="1" class="w-20 rounded-control border border-line bg-canvas px-2 py-1 text-right text-sm" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end pt-1">
                    <x-button type="submit">Simpan</x-button>
                </div>
            </form>
        @endif
    </x-card>

    <x-card>
        <x-card-header title="3. Riwayat Pesanan ke Penerbit" icon="fileText" subtitle="Pemesanan back order yang sudah ditandai ke penerbit." />
        <x-data-table
            empty="Belum ada pesanan ke penerbit."
            :rows="$backorderOrders"
            :columns="[
                ['key' => 'publisher', 'label' => 'Penerbit'],
                ['key' => 'number', 'label' => 'No. Pesanan Penerbit'],
                ['key' => 'ordered_at', 'label' => 'Tanggal'],
                ['key' => 'status', 'label' => 'Status', 'view' => 'components.status-label'],
                ['label' => 'Aksi', 'view' => 'backorders.partials.actions'],
            ]"
        />
    </x-card>
    <div class="mt-3">{{ $backorderOrders->links() }}</div>
@endsection