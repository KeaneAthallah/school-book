@extends('layouts.app')

@section('title', 'Stok')

@section('content')
    <x-page-header
        title="Stok Barang"
        subtitle="Saldo stok fisik, reservasi, dan ketersediaan per gudang."
        icon="boxes"
    >
        <x-slot:actions>
            <x-button :href="route('stock.movements')" variant="secondary">
                <x-icon name="refresh" class="h-4 w-4" />
                Mutasi Stok
            </x-button>
            @if ($can['adjust'])
                <x-button x-on:click="$dispatch('open-modal', 'adjust-stock')">Penyesuaian Stok</x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <x-filter-bar method="get" :action="route('stock.index')">
            <x-field label="Cari" class="w-56">
                <x-input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Judul / kode barang…" />
            </x-field>
            <x-button type="submit" variant="secondary">Cari</x-button>
        </x-filter-bar>

        <x-data-table
            empty="Belum ada stok tercatat."
            empty-icon="boxes"
            :rows="$stocks"
            :columns="[
                ['key' => 'code', 'label' => 'Kode'],
                ['key' => 'product', 'label' => 'Barang'],
                ['key' => 'warehouse', 'label' => 'Gudang'],
                ['key' => 'quantity', 'label' => 'Fisik', 'align' => 'right', 'class' => 'tabular-nums'],
                ['key' => 'reserved_quantity', 'label' => 'Reservasi', 'align' => 'right', 'class' => 'tabular-nums'],
                ['key' => 'available_quantity', 'label' => 'Tersedia', 'align' => 'right', 'class' => 'tabular-nums'],
                ['key' => 'min_stock', 'label' => 'Stok Min', 'align' => 'right', 'class' => 'tabular-nums'],
                ['label' => 'Status', 'view' => 'inventory.stock.partials.stock-state'],
            ]"
        />
    </x-card>

    <div class="mt-3">{{ $stocks->links() }}</div>

    @if ($can['adjust'])
        <x-modal name="adjust-stock" title="Penyesuaian Stok">
            <form method="POST" action="{{ route('stock.adjust') }}" class="space-y-3">
                @csrf

                <x-field label="Barang" name="product_id" required>
                    <x-select name="product_id" :invalid="$errors->has('product_id')">
                        <option value="">— Pilih Barang —</option>
                        @foreach ($products as $option)
                            <option value="{{ $option['value'] }}" @selected(old('product_id') == $option['value'])">{{ $option['label'] }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Gudang" name="warehouse_id" required>
                    <x-select name="warehouse_id" :invalid="$errors->has('warehouse_id')">
                        @foreach ($warehouses as $option)
                            <option value="{{ $option['value'] }}" @selected(old('warehouse_id') == $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Jumlah" name="quantity" required hint="Positif menambah, negatif mengurangi.">
                    <x-input type="number" name="quantity" value="{{ old('quantity') }}" :invalid="$errors->has('quantity')" />
                </x-field>

                <x-field label="Alasan" name="reason" required>
                    <x-input name="reason" value="{{ old('reason') }}" :invalid="$errors->has('reason')" />
                </x-field>

                <div class="flex justify-end gap-2 pt-1">
                    <x-button type="button" variant="secondary" x-on:click="$dispatch('close-modal', 'adjust-stock')">Batal</x-button>
                    <x-button type="submit">Simpan</x-button>
                </div>
            </form>
        </x-modal>
    @endif
@endsection
