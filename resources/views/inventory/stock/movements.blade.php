@extends('layouts.app')

@section('title', 'Mutasi Stok')

@section('content')
    <x-page-header
        title="Mutasi Stok"
        subtitle="Riwayat pergerakan stok dari penerimaan, reservasi, pengiriman, dan penyesuaian."
        icon="refresh"
    />

    <x-card>
        <x-filter-bar method="get" :action="route('stock.movements')">
            <x-field label="Cari barang" class="w-52">
                <x-input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Judul / kode" />
            </x-field>
            <x-field label="Tipe" class="w-44">
                <x-select name="type">
                    <option value="">Semua tipe</option>
                    @foreach ($types as $option)
                        <option value="{{ $option['value'] }}" @selected($filters['type'] === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </x-select>
            </x-field>
            <x-field label="Dari" class="w-36">
                <x-input type="date" name="date_from" value="{{ $filters['date_from'] }}" />
            </x-field>
            <x-field label="Sampai" class="w-36">
                <x-input type="date" name="date_to" value="{{ $filters['date_to'] }}" />
            </x-field>
            <x-button type="submit" variant="secondary">Filter</x-button>
        </x-filter-bar>

        <x-data-table
            empty="Belum ada pergerakan stok."
            empty-icon="boxes"
            :rows="$movements"
            :columns="[
                ['key' => 'date', 'label' => 'Waktu', 'type' => 'datetime'],
                ['key' => 'code', 'label' => 'Kode'],
                ['key' => 'product', 'label' => 'Barang'],
                ['key' => 'warehouse', 'label' => 'Gudang'],
                ['key' => 'type_label', 'label' => 'Tipe'],
                ['key' => 'quantity', 'label' => 'Perubahan', 'align' => 'right', 'view' => 'inventory.stock.partials.movement-change'],
                ['key' => 'balance_after', 'label' => 'Saldo', 'align' => 'right', 'class' => 'tabular-nums'],
                ['key' => 'note', 'label' => 'Catatan'],
                ['key' => 'user', 'label' => 'Oleh'],
            ]"
        />
    </x-card>

    <div class="mt-3">{{ $movements->links() }}</div>
@endsection
