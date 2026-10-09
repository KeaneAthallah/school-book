@extends('layouts.app')

@section('title', 'Buat Pengiriman')

@section('content')
    @if ($order === null)
        <x-page-header
            title="Buat Pengiriman"
            subtitle="Pilih pesanan yang memiliki stok siap dikirim."
        >
            <x-slot:actions>
                <x-button :href="route('shipments.index')" variant="secondary">Kembali</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-card class="max-w-xl">
            <form method="GET" action="{{ route('shipments.create') }}" class="space-y-3 p-4">
                <x-field label="Pesanan">
                    <x-select name="order_id">
                        <option value="">— Pilih Pesanan —</option>
                        @foreach ($orders as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-button type="submit">Lanjutkan</x-button>
            </form>
        </x-card>
    @else
        <x-page-header
            title="Buat Pengiriman"
            :subtitle="'Pesanan '.$order['number'].' · '.($order['school'] ?? '')"
        >
            <x-slot:actions>
                <x-button :href="route('shipments.index')" variant="secondary">Kembali</x-button>
            </x-slot:actions>
        </x-page-header>

        <form method="POST" action="{{ route('shipments.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="order_id" value="{{ $order['id'] }}" />

            <x-card>
                <x-card-header title="Informasi Pengiriman" icon="truck" />
                <div class="grid gap-3 p-4 sm:grid-cols-3">
                    <x-field label="Kurir" name="courier_id">
                        <x-select name="courier_id">
                            <option value="">— Pilih —</option>
                            @foreach ($couriers as $option)
                                <option value="{{ $option['value'] }}" @selected(old('courier_id') == $option['value'])>{{ $option['label'] }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Metode Kirim" name="shipping_method_id">
                        <x-select name="shipping_method_id">
                            <option value="">— Pilih —</option>
                            @foreach ($shippingMethods as $option)
                                <option value="{{ $option['value'] }}" @selected(old('shipping_method_id') == $option['value'])>{{ $option['label'] }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Tanggal Kirim" name="ship_date">
                        <x-input type="date" name="ship_date" value="{{ old('ship_date', $defaultDate) }}" />
                    </x-field>

                    <x-field label="No. Resi" name="tracking_number">
                        <x-input name="tracking_number" value="{{ old('tracking_number') }}" />
                    </x-field>

                    <x-field label="Penerima" name="recipient_name">
                        <x-input name="recipient_name" value="{{ old('recipient_name') }}" />
                    </x-field>

                    <x-field label="Alamat" name="address">
                        <x-input name="address" value="{{ old('address') }}" />
                    </x-field>

                    <x-field label="Catatan" name="notes" class="sm:col-span-3">
                        <x-textarea name="notes" rows="2">{{ old('notes') }}</x-textarea>
                    </x-field>
                </div>
            </x-card>

            <x-card>
                <x-card-header title="Item Dikirim" icon="boxes" />

                @error('items')
                    <p class="px-4 pt-3 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror

                @if (count($order['items']) === 0)
                    <x-empty-state message="Tidak ada kuantitas yang siap dikirim pada pesanan ini." icon="boxes" />
                @else
                    <div class="scroll-slim overflow-x-auto">
                        <table class="w-full border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-line bg-panel-soft text-left text-xs font-semibold tracking-wide text-ink-soft uppercase">
                                    <th scope="col" class="px-3 py-2.5">Buku</th>
                                    <th scope="col" class="px-3 py-2.5 text-right">Siap Kirim</th>
                                    <th scope="col" class="px-3 py-2.5 text-right">Jumlah Kirim</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order['items'] as $index => $item)
                                    <tr class="border-b border-line last:border-0">
                                        <td class="px-3 py-2.5">
                                            {{ $item['code'] }} {{ $item['book'] }}
                                            <input type="hidden" name="items[{{ $index }}][order_item_id]" value="{{ $item['order_item_id'] }}" />
                                        </td>
                                        <td class="px-3 py-2.5 text-right tabular-nums">{{ $item['shippable'] }}</td>
                                        <td class="w-32 px-3 py-2.5">
                                            <x-input
                                                type="number"
                                                name="items[{{ $index }}][quantity]"
                                                min="0"
                                                max="{{ $item['shippable'] }}"
                                                value="{{ $item['shippable'] }}"
                                                class="text-right"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>

            <div class="flex justify-end gap-2">
                <x-button :href="route('shipments.index')" variant="secondary">Batal</x-button>
                <x-button type="submit" x-bind:disabled="{{ count($order['items']) === 0 ? 'true' : 'false' }}">Simpan Pengiriman</x-button>
            </div>
        </form>
    @endif
@endsection
