@extends('layouts.app')

@section('title', 'Tambah Penerimaan')

@section('content')
    @php
        $blank = ['product_id' => '', 'quantity' => 1, 'unit_cost' => ''];
    @endphp

    <x-page-header
        title="Tambah Penerimaan"
        subtitle="Catat barang yang diterima dari penerbit. Stok bertambah setelah konfirmasi."
        icon="inbox"
    >
        <x-slot:actions>
            <x-button :href="route('receipts.index')" variant="secondary">Kembali</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('receipts.store') }}" class="space-y-4">
        @csrf

        <x-card>
            <x-card-header title="Informasi" icon="inbox" />
            <div class="grid gap-3 p-4 sm:grid-cols-3">
                <x-field label="Penerbit" name="publisher_id" required>
                    <x-select name="publisher_id" :invalid="$errors->has('publisher_id')">
                        <option value="">— Pilih —</option>
                        @foreach ($publishers as $option)
                            <option value="{{ $option['value'] }}" @selected(old('publisher_id') == $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Gudang" name="warehouse_id" required>
                    <x-select name="warehouse_id" :invalid="$errors->has('warehouse_id')">
                        <option value="">— Pilih —</option>
                        @foreach ($warehouses as $option)
                            <option value="{{ $option['value'] }}" @selected(old('warehouse_id') == $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Tanggal" name="receipt_date" required>
                    <x-input type="date" name="receipt_date" value="{{ old('receipt_date', $defaultDate) }}" :invalid="$errors->has('receipt_date')" />
                </x-field>

                <x-field label="Catatan" name="notes" class="sm:col-span-3">
                    <x-textarea name="notes" rows="2">{{ old('notes') }}</x-textarea>
                </x-field>
            </div>
        </x-card>

        <x-card x-data="lineItems({{ json_encode(['products' => $products, 'items' => [$blank], 'blank' => $blank, 'priceKey' => 'unit_cost', 'bookPriceKey' => 'cost_price']) }})">
            <x-card-header title="Item" icon="boxes">
                <x-slot:action>
                    <x-button type="button" variant="secondary" size="sm" x-on:click="add()">Tambah Item</x-button>
                </x-slot:action>
            </x-card-header>

            <div class="space-y-3 p-4">
                @error('items')
                    <p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror

                <template x-for="(item, index) in items" :key="index">
                    <div class="grid gap-2 rounded-control border border-line p-3 sm:grid-cols-[2fr_1fr_1fr_auto]">
                        <x-field label="Barang">
                            <input
                                type="search"
                                x-model="productQuery"
                                placeholder="Cari barang..."
                                class="mb-1 w-full rounded-control border border-line-strong bg-panel px-3 py-1.5 text-xs text-ink outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:focus:ring-brand-500/25"
                            />
                            <select
                                x-bind:name="`items[${index}][product_id]`"
                                x-model="item.product_id"
                                x-on:change="pick(index)"
                                class="w-full rounded-control border border-line-strong bg-panel px-3 py-2 text-sm text-ink outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:focus:ring-brand-500/25"
                            >
                                <option value="">— Pilih Barang —</option>
                                <template x-for="product in filteredProducts()" :key="product.value">
                                    <option :value="product.value" x-text="product.label"></option>
                                </template>
                            </select>
                        </x-field>

                        <x-field label="Qty">
                            <x-input type="number" min="1" x-model="item.quantity" x-bind:name="`items[${index}][quantity]`" />
                        </x-field>

                        <x-field label="Harga Pokok">
                            <x-input type="number" step="any" x-model="item.unit_cost" x-bind:name="`items[${index}][unit_cost]`" />
                        </x-field>

                        <div class="flex items-end">
                            <x-button type="button" variant="ghost" size="sm" x-on:click="remove(index)" x-bind:disabled="items.length === 1">Hapus</x-button>
                        </div>
                    </div>
                </template>
            </div>
        </x-card>

        <div class="flex justify-end gap-2">
            <x-button :href="route('receipts.index')" variant="secondary">Batal</x-button>
            <x-button type="submit">Simpan</x-button>
        </div>
    </form>
@endsection
