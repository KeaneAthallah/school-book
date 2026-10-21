@extends('layouts.app')
@section('title', 'Ubah Pesanan ke Penerbit')
@section('content')
    <x-page-header title="Ubah Pesanan ke Penerbit" icon="fileText">
        <x-slot:actions>
            <x-button :href="route('backorders.index')" variant="secondary">Kembali</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="POST" action="{{ route('backorders.orderUpdate', $order->id) }}" class="space-y-3 p-4">
            @csrf
            @method('PUT')
            <div class="grid gap-3 sm:grid-cols-2">
                <x-field label="Penerbit" required>
                    <x-select name="publisher_id">
                        @foreach ($publishers as $p)
                            <option value="{{ $p->id }}" @selected($order->publisher_id == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="No. Pesanan Penerbit">
                    <x-input type="text" name="number" value="{{ $order->number }}" />
                </x-field>
                <x-field label="Catatan" class="sm:col-span-2">
                    <x-textarea name="note" rows="2">{{ $order->note }}</x-textarea>
                </x-field>
            </div>

            <div class="scroll-slim overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-line bg-panel-soft text-xs font-semibold tracking-wide text-ink-soft uppercase">
                            <th class="px-3 py-2.5">Barang</th>
                            <th class="px-3 py-2.5">No. Pesanan</th>
                            <th class="px-3 py-2.5">Sekolah</th>
                            <th class="px-3 py-2.5 text-right">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr class="border-b border-line last:border-0">
                                <td class="px-3 py-2.5">{{ $item->backorder?->product?->title ?? '-' }}</td>
                                <td class="px-3 py-2.5">{{ $item->backorder?->orderItem?->order?->number ?? '-' }}</td>
                                <td class="px-3 py-2.5">{{ $item->backorder?->orderItem?->order?->school?->name ?? '-' }}</td>
                                <td class="px-3 py-2.5">
                                    <input type="number" name="items[{{ $item->id }}]" value="{{ $item->quantity }}" min="0" class="w-20 rounded-control border border-line bg-canvas px-2 py-1 text-right text-sm" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-ink-soft">Isi 0 untuk menghapus item.</p>

            <div class="flex justify-end gap-2 pt-1">
                <x-button type="submit">Simpan</x-button>
            </div>
        </form>
    </x-card>
@endsection