@extends('layouts.app')

@section('title', 'Tambah Penerimaan')

@section('content')
    @php
        $editing = isset($receipt);
        $blank = ['product_id' => '', 'quantity' => 1, 'unit_cost' => ''];
        $items = $editing ? $receipt['items'] : [$blank];
    @endphp

    <x-page-header
        :title="$editing ? 'Ubah Penerimaan' : 'Tambah Penerimaan'"
        subtitle="Catat barang yang diterima dari penerbit. Stok bertambah setelah konfirmasi."
        icon="inbox"
    >
        <x-slot:actions>
            <x-button :href="route('receipts.index')" variant="secondary">Kembali</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('receipts.update', $receipt['id']) : route('receipts.store') }}" class="space-y-4">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-card>
            <x-card-header title="Informasi" icon="inbox" />
            <div class="grid gap-3 p-4 sm:grid-cols-3">
                <x-field label="Penerbit" name="publisher_id" required>
                    <x-select name="publisher_id" :invalid="$errors->has('publisher_id')">
                        <option value="">— Pilih —</option>
                        @foreach ($publishers as $option)
                            <option value="{{ $option['value'] }}" @selected(old('publisher_id', $editing ? $receipt['publisher_id'] : '') == $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Tanggal" name="receipt_date" required>
                    <x-input type="date" name="receipt_date" value="{{ old('receipt_date', $editing ? $receipt['receipt_date'] : $defaultDate) }}" :invalid="$errors->has('receipt_date')" />
                </x-field>

                <x-field label="Catatan" name="notes" class="sm:col-span-3">
                    <x-textarea name="notes" rows="2">{{ old('notes', $editing ? $receipt['notes'] : '') }}</x-textarea>
                </x-field>
            </div>
        </x-card>

        <x-card x-data="lineItems({{ json_encode(['products' => $products, 'items' => $items, 'blank' => $blank, 'priceKey' => 'unit_cost', 'bookPriceKey' => 'cost_price']) }})">
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
                            <div class="relative" x-data>
                                <input
                                    type="text"
                                    x-model="item.product_search"
                                    @focus="item.showDropdown = true"
                                    @input="item.showDropdown = true; item.product_id = ''"
                                    placeholder="Cari/pilih barang..."
                                    class="w-full rounded-control border border-line-strong bg-panel px-3 py-2 text-sm text-ink outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:focus:ring-brand-500/25"
                                />
                                <input type="hidden" x-bind:name="`items[${index}][product_id]`" x-model="item.product_id" />
                                <div
                                    x-show="item.showDropdown"
                                    @click.outside="item.showDropdown = false"
                                    class="absolute z-20 mt-1 max-h-48 w-full overflow-y-auto rounded-control border border-line-strong bg-panel shadow-lg"
                                >
                                    <template x-for="product in products.filter(p => String(p.label).toLowerCase().includes(String(item.product_search || '').toLowerCase()))" :key="product.value">
                                        <div
                                            class="cursor-pointer px-3 py-2 text-sm text-ink hover:bg-panel-soft"
                                            @click="item.product_id = product.value; item.product_search = product.label; item.showDropdown = false; pick(index)"
                                            x-text="product.label"
                                        ></div>
                                    </template>
                                </div>
                            </div>
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
