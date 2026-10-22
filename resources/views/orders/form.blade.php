@extends('layouts.app')

@section('title', isset($order) ? 'Ubah Pesanan '.$order['number'] : 'Buat Pesanan')

@section('content')
    @php
        $editing = isset($order);
        $blank = ['product_id' => '', 'quantity' => 1, 'unit_price' => '', 'discount_percent' => '0', 'discount_amount' => '0', 'availability_status' => 'available'];
        $items = $editing
            ? collect($order['items'])->map(fn (array $item): array => [
                'product_id' => (string) $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount_percent' => $item['discount_percent'] ?? '0',
                'discount_amount' => $item['discount_amount'] ?? '0',
                'availability_status' => $item['availability_status'] ?? 'available',
            ])->all()
            : [$blank];
    @endphp

    <x-page-header
        :title="$editing ? 'Ubah Pesanan '.$order['number'] : 'Buat Pesanan'"
        subtitle="Pilih sekolah, sumber pesanan, dan item barang."
        icon="cart"
    >
        <x-slot:actions>
            <x-button :href="route('orders.index')" variant="secondary">Kembali</x-button>
        </x-slot:actions>
    </x-page-header>

    <form
        method="POST"
        action="{{ $editing ? route('orders.update', $order['id']) : route('orders.store') }}"
        class="space-y-4"
        x-data="lineItems({{ json_encode([
            'products' => $products,
            'items' => $items,
            'blank' => $blank,
            'zone' => $editing && $order['zona_id'] ? (string) $order['zona_id'] : (string) ($zonas[0]['value'] ?? ''),
            'discountPercent' => old('discount_percent', $editing ? ($order['discount_percent'] ?? '0') : '0'),
        ]) }})"
        x-init="schools = {{ json_encode(collect($schools)->mapWithKeys(fn ($s) => [(string) $s['value'] => $s['rebate_percent']])->all()) }}"
    >
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <x-card>
            <x-card-header title="Informasi Pesanan" icon="cart" />

            <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-field label="Tanggal" name="order_date" required>
                    <x-input type="date" name="order_date" value="{{ old('order_date', $editing ? $order['order_date'] : $defaultDate) }}" :invalid="$errors->has('order_date')" />
                </x-field>

                <x-field label="Sekolah" name="school_id" required>
                    <x-select name="school_id" :invalid="$errors->has('school_id')" x-on:change="if (schools[$event.target.value] !== undefined && schools[$event.target.value] !== null) discountPercent = schools[$event.target.value]">
                        <option value="">— Pilih Sekolah —</option>
                        @foreach ($schools as $option)
                            <option value="{{ $option['value'] }}" @selected((string) old('school_id', $editing ? $order['school_id'] : '') === (string) $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Sumber Pesanan" name="order_source_id" required>
                    <x-select name="order_source_id" :invalid="$errors->has('order_source_id')">
                        <option value="">— Pilih Sumber —</option>
                        @foreach ($sources as $option)
                            <option value="{{ $option['value'] }}" @selected((string) old('order_source_id', $editing ? $order['order_source_id'] : '') === (string) $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Referensi Eksternal" name="external_reference" hint="Opsional, untuk pesanan hasil impor.">
                    <x-input name="external_reference" value="{{ old('external_reference', $editing ? $order['external_reference'] : '') }}" :invalid="$errors->has('external_reference')" />
                </x-field>

                <x-field label="Diskon Pesanan (%)" name="discount_percent">
                    <x-input type="number" step="any" min="0" max="100" name="discount_percent" value="{{ old('discount_percent', $editing ? ($order['discount_percent'] ?? '0') : '0') }}" :invalid="$errors->has('discount_percent')" x-model="discountPercent" />
                </x-field>

                <x-field label="Catatan" name="notes" class="sm:col-span-2 lg:col-span-3">
                    <x-textarea name="notes" rows="2">{{ old('notes', $editing ? $order['notes'] : '') }}</x-textarea>
                </x-field>
            </div>
        </x-card>

        <x-card>
            <x-card-header title="Item Pesanan" icon="boxes" />

            <div class="space-y-3 p-4">
                <div class="rounded-control border border-line p-3">
                    <x-field label="Zona Harga">
                        <select name="zona_id" x-model="zone" x-on:change="applyZone()" class="w-full rounded-control border border-line-strong bg-panel px-3 py-2 text-sm text-ink outline-none focus:border-brand-500 focus:ring-2 focus:border-brand-100 dark:focus:border-brand-500/25">
                            <option value="">— Pilih Zona —</option>
                            @foreach ($zonas as $zona)
                                <option value="{{ $zona['value'] }}" @selected((string) old('zona_id', $editing ? ($order['zona_id'] ?? '') : ($zonas[0]['value'] ?? '')) === (string) $zona['value'])>{{ $zona['label'] }}</option>
                            @endforeach
                        </select>
                    </x-field>
                </div>
                @error('items')
                    <p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror

                <template x-for="(item, index) in items" :key="index">
                    <div class="grid gap-2 rounded-control border border-line p-3 sm:grid-cols-[2fr_1fr_1fr_1fr_1fr_auto]">
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
                                <input type="hidden" :name="`items[${index}][product_id]`" x-model="item.product_id" />
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

                        <x-field label="Harga">
                            <x-input type="number" step="any" x-model="item.unit_price" x-bind:name="`items[${index}][unit_price]`" />
                        </x-field>

                        <x-field label="Diskon (%)">
                            <x-input type="number" step="any" min="0" max="100" x-model="item.discount_percent" x-bind:name="`items[${index}][discount_percent]`" />
                        </x-field>

                        <x-field label="Jumlah">
                            <div class="rounded-control border border-line bg-panel-soft px-3 py-2 text-right text-sm font-medium tabular-nums text-ink" x-text="money(lineTotal(item))"></div>
                        </x-field>

                        <div class="flex items-end gap-2">
                            <label class="flex items-center gap-1 text-xs">
                                <input type="checkbox" :checked="item.availability_status === 'unavailable'" @change="item.availability_status = $event.target.checked ? 'unavailable' : 'available'" />
                                Kosong
                            </label>
                            <input type="hidden" :name="`items[${index}][availability_status]`" x-model="item.availability_status" />
                            <x-button type="button" variant="ghost" size="sm" x-on:click="remove(index)" x-bind:disabled="items.length === 1">Hapus</x-button>
                        </div>
                    </div>
                </template>

                <div class="flex justify-end pt-2">
                    <x-button type="button" variant="primary" x-on:click="add()">+ Tambah Item</x-button>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-3 p-4">
                <div class="grid grid-cols-2 gap-x-8 gap-y-1 text-sm sm:grid-cols-3">
                    <div class="text-ink-soft">
                        Subtotal: <span class="font-medium text-ink" x-text="money(subtotal())"></span>
                    </div>
                    <div class="text-ink-soft">
                        Diskon (<span x-text="Number(discountPercent || 0)"></span>%): <span class="font-medium text-ink" x-text="'− ' + money(orderDiscount())"></span>
                    </div>
                    <div class="text-ink-soft">
                        Total: <span class="text-base font-semibold text-ink" x-text="money(grandTotal())"></span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <x-button :href="route('orders.index')" variant="secondary">Batal</x-button>
                    <x-button type="submit">Simpan Pesanan</x-button>
                </div>
            </div>
        </x-card>
    </form>
@endsection
